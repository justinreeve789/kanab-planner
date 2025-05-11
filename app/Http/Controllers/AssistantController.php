<?php

namespace App\Http\Controllers;

use App\Models\Assistant\Assistant;
use App\Models\Assistant\Message;
use App\Models\Assistant\Session;
use App\Models\Assistant\Thread;
use App\Models\Trail;
use App\Services\AssistantService;
use App\Services\OpenAIService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    const MAX_TOKENS = 2500;
    const OPENAI_RESPONSES_TYPE = 'openai_responses';

    public function getMessages(Request $request)
    {
        $resourceTable = $request->get('resource_table');
        $resourceId = $request->get('resource_id');
        $messages = Message::whereHas('assistant_thread', function ($query) use ($resourceTable, $resourceId) {
            $query->where([
                'resource_table' => $resourceTable,
                'resource_id' => $resourceId,
            ])->whereHas('assistant_session', function ($q) {
                $q->where('session_key', Cookie::get(Session::COOKIE_NAME));
            });
        })->with(['assistant_thread.assistant_session'])
            ->orderBy('created_at')
            ->get();
        return $messages;
    }

    public function deleteMessages(Request $request)
    {
        $resourceTable = $request->get('resource_table') ?? null;
        $resourceId = $request->get('resource_id') ?? null;
        $sessionKey = Cookie::get(Session::COOKIE_NAME);
        if ($sessionKey && trim($sessionKey) !== '') {
            if ($resourceTable === null && $resourceId === null) {
                Thread::whereHas('assistant_session', function($query) use ($sessionKey) {
                    $query->where('session_key', $sessionKey);
                })->where([
                    'resource_table' => $resourceTable,
                    'resource_id' => $resourceId,
                ])->delete();
            }
        }
    }

    public function sendMessage(Request $request)
    {
        $assistantService = new AssistantService();
        $openAIService = new OpenAIService();
        $message = trim($request->get('message'));
        $resourceType = $request->get('resource_type');
        $resourceId = $request->get('resource_id');
        if ($resourceType && $resourceId) {
            $resource = $assistantService->getResourceFromTypeAndId($resourceType, $resourceId);
        } else {
            $resource = null;
        }

        $assistantSession = Session::where('session_key', Cookie::get(Session::COOKIE_NAME))->first();
        if (!$assistantSession) {
            // TODO: Add user id.
            $assistantSession = Session::create([
                'session_key' => Cookie::get(Session::COOKIE_NAME),
                'user_id' => null,
            ]);
        }

        // Do a query analysis to determine if we're referencing a resource, even if we already have one.
        $newResource = $assistantService->analyzeQueryForResource($message);
        if ($newResource && $resource !== $newResource) {
            // TODO: Handle a change in topic focus.
            $resource = $newResource;
        }

        // Make sure we're working in the right thread.
        $assistantThread = Thread::updateOrCreate([
            'assistant_session_id' => $assistantSession->id,
            'resource_table' => $resource ? $resource->getTable() : null,
            'resource_id' => $resource ? $resource->id : null,
        ]);

        // Save the user's message.
        $newMessages[] = Message::create([
            'assistant_thread_id' => $assistantThread->id,
            'is_assistant' => false,
            'content' => $message,
        ]);

        // Now we can determine how to respond. Check if there's any instant responses we can give to the prompt, e.g.
        // if the user says "thank you" we can instantly respond with a "you're welcome" message without using OpenAI.
        $instantResponseMessage = $assistantService->analyzeQueryForInstantResponse($message, $assistantThread->id, $resource ?? null);
        if ($instantResponseMessage) {
            $instantResponseMessage->save();
            $newMessages[] = $instantResponseMessage;
            $assistantThread->assistant_messages = collect($newMessages);
            return $assistantThread->toJson();
        }

        // Create an assistant if needed.
        $assistant = Assistant::get($resource, $openAIService);

        if ($assistantSession) {
            $newMessages = [];

            if ($message && $message !== '') {
                // Make a general query.
                $response = $openAIService->query($message, self::MAX_TOKENS, Config::get('openai.finetune_model'), $assistant, $assistantThread->thread_key);
                if ($response) {
                    // Save the assistant's message.
                    $assistantThread = Thread::updateOrCreate([
                        'assistant_session_id' => $assistantSession->id,
                        'resource_table' => $resource ? $resource->getTable() : null,
                        'resource_id' => $resource ? $resource->id : null,
                    ], [
                        'thread_key' => $response->thread_id,
                        'thread_type' => self::OPENAI_RESPONSES_TYPE,
                    ]);

                    $newMessage = Message::create([
                        'assistant_thread_id' => $assistantThread->id,
                        'is_assistant' => true,
                        'content' => $response->content,
                    ]);

                    $newMessages[] = $newMessage;
                    $assistantThread->assistant_messages = collect($newMessages);
                    return $assistantThread->toJson();
                } else {
                    return (object) [
                        'error' => 'Could not retrieve response. Please try again.',
                    ];
                }
            }
        } else {
            throw new Exception('Session not created.');
        }
    }

    /**
     * Create a specialized itinerary with AI.
     */
    public function createItinerary(Request $request)
    {}

    /**
     * Update the existing itinerary with specialized parameters. We'll just retrieve resources and tasks that can be
     * added to days, or replace existing resources on those days.
     */
    public function updateItinerary(Request $request, string $itineraryId)
    {}
}
