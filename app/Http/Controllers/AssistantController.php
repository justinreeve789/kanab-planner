<?php

namespace App\Http\Controllers;

use App\Models\Assistant\Message;
use App\Models\Assistant\Session;
use App\Services\AssistantService;
use App\Services\OpenAIService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    const MAX_TOKENS = 200;

    public function getMessages(Request $request)
    {
        $messages = Message::with('session', function($query) {
            $query->where('session_key', Cookie::get(Session::COOKIE_NAME));
        })->orderBy('created_at')->get();
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
        if ($resource !== $newResource) {
            // TODO: Handle a change in topic focus.
            $resource = $newResource;
        }

        // Save the user's message.
        $newMessages[] = Message::create([
            'assistant_session_id' => $assistantSession->id,
            'is_assistant' => false,
            'content' => $message,
            'resource_table' => $resource ? $resource->getTable() : null,
            'resource_id' => $resource ? $resource->id : null,
        ]);

        // TODO: Analyze the message for requests for trail maps or other content we can retrieve without using OpenAI.
        if ($resource) {
            if ($assistantService->isMapRequested($message, $resource)) {
                $newMessages[] = Message::create([
                    'assistant_session_id' => $assistantSession->id,
                    'is_assistant' => true,
                    'content' => '',
                    'resource_table' => $resource->getTable(),
                    'resource_id' => $resource->id,
                    'show_in_chat_bubble' => false,
                    'method_executed' => 'show_trail_map',
                    'method_params' => 'id=' . $resource->id,
                ]);

                return collect($newMessages)->toJson();
            }
        }

        if ($assistantSession) {
            $newMessages = [];
            if ($message && $message !== '') {
                $response = $openAIService->query($message, self::MAX_TOKENS, Config::get('oopenai.finetune_model'));
                if ($response) {
                    // Save the assistant's message.
                    $newMessages[] = Message::create([
                        'assistant_session_id' => $assistantSession->id,
                        'is_assistant' => true,
                        'content' => $response,
                        'resource_table' => $resource ? $resource->getTable() : null,
                        'resource_id' => $resource ? $resource->id : null,
                    ]);

                    return collect($newMessages)->toJson();
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
}
