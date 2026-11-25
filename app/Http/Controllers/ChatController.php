<?php

namespace App\Http\Controllers;

use App\Chat\ChatAgent;
use App\Chat\ChatContext;
use App\Chat\ChatReply;
use App\Chat\History;
use App\Models\Assistant\Message;
use App\Models\Assistant\Session;
use App\Models\Assistant\Thread;
use App\Models\Itinerary\Itinerary;
use App\Policies\ItineraryPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The trip assistant's chat. One thread per browser session and itinerary; the record the traveler has open is
 * context for each message, not a separate thread.
 */
class ChatController extends Controller
{
    public const THREAD_TYPE = 'chat';

    public function send(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'itinerary_id' => 'nullable|string|max:40',
            'resource_type' => 'nullable|string|max:20',
            'resource_table' => 'nullable|string|max:20',
            'resource_id' => 'nullable|integer',
        ]);

        [$itinerary, $canEdit] = $this->itinerary($request, $data['itinerary_id'] ?? null);
        $thread = $this->thread($request, $itinerary, true);
        $history = json_decode((string) $thread->transcript, true) ?: [];

        $table = $data['resource_table'] ?? self::tableFor($data['resource_type'] ?? null);
        $context = new ChatContext(
            ChatContext::now(),
            $request->user('sanctum'),
            $itinerary,
            $canEdit,
            $request->header(ItineraryPolicy::TOKEN_HEADER),
            $table && !empty($data['resource_id']) ? ['table' => $table, 'id' => (int) $data['resource_id']] : null,
            $history,
        );

        $agent = app(ChatAgent::class);
        $reply = self::instantReply($data['message']) ?? $agent->reply($context, trim($data['message']));
        Log::info('Chat reply', [
            'thread' => $thread->id,
            'model' => $agent->model()->name(),
            'tools' => array_map(fn($t) => $t['name'] . ($t['ok'] ? '' : ' (failed)'), $reply->toolTrace),
            'usage' => $reply->usage,
        ]);

        $thread->transcript = json_encode(History::forStorage([...$history, ...$reply->transcript]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $thread->save();

        Message::create(['assistant_thread_id' => $thread->id, 'is_assistant' => false, 'content' => $data['message']]);
        $message = Message::create([
            'assistant_thread_id' => $thread->id,
            'is_assistant' => true,
            'content' => $reply->text,
            'component' => $reply->component,
            'component_params' => $reply->componentParams ?: null,
            'sources' => $reply->sources ?: null,
        ]);

        return response()->json([
            'thread_id' => $thread->id,
            'assistant_messages' => [$message->fresh()],
            'failed' => $reply->failed,
        ], $reply->failed ? 503 : 200);
    }

    public function messages(Request $request)
    {
        [$itinerary] = $this->itinerary($request, $request->query('itinerary_id'));
        $thread = $this->thread($request, $itinerary, false);
        return $thread ? $thread->assistant_messages()->orderBy('id')->get() : [];
    }

    public function clear(Request $request)
    {
        [$itinerary] = $this->itinerary($request, $request->query('itinerary_id'));
        $thread = $this->thread($request, $itinerary, false);
        if ($thread) {
            $thread->assistant_messages()->delete();
            $thread->transcript = null;
            $thread->save();
        }
        return response()->noContent();
    }

    /** Thanks and other pleasantries don't need the model. */
    private static function instantReply(string $message): ?ChatReply
    {
        $stripped = strtolower(preg_replace('/[^a-z ]/i', '', $message));
        if (!preg_match('/^\s*(thanks|thank you|thank you so much|thanks so much|thx|ty|ok thanks|great thanks)\s*$/', $stripped)) {
            return null;
        }
        $text = "You're welcome! Ask me anything else about your trip.";
        return new ChatReply($text, [], null, [], [], [['type' => 'user', 'text' => $message], ['type' => 'assistant', 'text' => $text]]);
    }

    /** @return array{0: ?Itinerary, 1: bool} */
    private function itinerary(Request $request, ?string $id): array
    {
        $itinerary = $id ? Itinerary::find($id) : null;
        if (!$itinerary) {
            return [null, false];
        }
        $canEdit = Gate::forUser($request->user('sanctum'))->allows('update', [$itinerary, $request->header(ItineraryPolicy::TOKEN_HEADER)]);
        return [$itinerary, $canEdit];
    }

    private function thread(Request $request, ?Itinerary $itinerary, bool $create): ?Thread
    {
        $key = Cookie::get(Session::COOKIE_NAME);
        if (!$key) {
            if (!$create) {
                return null;
            }
            $key = hash('sha256', Str::random(40));
            Cookie::queue(Session::COOKIE_NAME, $key, 60 * 24 * Session::DAYS_TO_SAVE);
        }
        $session = Session::firstOrCreate(['session_key' => $key], ['user_id' => $request->user('sanctum')?->id]);
        $attributes = ['assistant_session_id' => $session->id, 'thread_type' => self::THREAD_TYPE, 'itinerary_id' => $itinerary?->id];
        return $create ? Thread::firstOrCreate($attributes) : Thread::where($attributes)->first();
    }

    private static function tableFor(?string $type): ?string
    {
        return match ($type) {
            'trail', 'trails' => 'trails',
            'restaurant', 'restaurants' => 'restaurants',
            'location', 'locations' => 'locations',
            'event', 'events' => 'events',
            'accommodation', 'accommodations' => 'accommodations',
            default => null,
        };
    }
}
