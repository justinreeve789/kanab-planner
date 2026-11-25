<?php

namespace Tests\Feature\Chat;

use App\Chat\Models\ChatModel;
use App\Chat\Models\ScriptedModel;
use App\Models\Area;
use App\Models\Assistant\Thread;
use Illuminate\Support\Facades\Http;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class ChatEndpointTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->seedKanabFixtures();
        $this->withCredentials(); // JSON test requests only send cookies with credentials
    }

    private function useModel(array $steps): ScriptedModel
    {
        $model = new ScriptedModel($steps);
        $this->app->instance(ChatModel::class, $model);
        return $model;
    }

    public function test_message_round_trip_stores_the_thread_and_replays_it(): void
    {
        $this->useModel([
            ScriptedModel::call('search_trails', ['area_id' => Area::KANAB, 'max_miles' => 3]),
            ScriptedModel::say('Try Kanab Trail 1.'),
        ]);
        $first = $this->withUnencryptedCookie('assistant_session_key', 'abc')
            ->postJson('/api/v1/chat/message', ['message' => 'Short hike near Kanab?'])
            ->assertOk()
            ->assertJsonPath('assistant_messages.0.content', 'Try Kanab Trail 1.')
            ->assertJsonPath('assistant_messages.0.sources.0.kind', 'our_data');

        $thread = Thread::find($first->json('thread_id'));
        $this->assertSame(['user', 'tool_call', 'tool_result', 'assistant'], array_column(json_decode($thread->transcript, true), 'type'));
        $this->assertSame(2, $thread->assistant_messages()->count());

        $second = $this->useModel([ScriptedModel::say('About an hour.')]);
        $this->withUnencryptedCookie('assistant_session_key', 'abc')
            ->postJson('/api/v1/chat/message', ['message' => 'How long does it take?'])->assertOk();
        $this->assertSame(['user', 'tool_call', 'tool_result', 'assistant', 'user'], array_column($second->requests[0]['transcript'], 'type'));

        $this->withUnencryptedCookie('assistant_session_key', 'abc')->getJson('/api/v1/chat/thread')->assertOk()->assertJsonCount(4);
        $this->withUnencryptedCookie('assistant_session_key', 'abc')->deleteJson('/api/v1/chat/thread')->assertNoContent();
        $this->assertNull($thread->fresh()->transcript);
    }

    public function test_choices_come_back_as_a_component(): void
    {
        $this->useModel([ScriptedModel::call('present_options', ['question' => 'Which one?', 'options' => [['label' => 'A'], ['label' => 'B']]])]);
        $this->postJson('/api/v1/assistant/message', ['message' => 'The overlook trail?'])
            ->assertOk()
            ->assertJsonPath('assistant_messages.0.component', 'choices')
            ->assertJsonPath('assistant_messages.0.component_params.options.1.label', 'B');
    }

    public function test_thanks_is_answered_without_the_model(): void
    {
        $model = $this->useModel([]);
        $this->postJson('/api/v1/chat/message', ['message' => 'Thank you!'])
            ->assertOk()
            ->assertJsonPath('assistant_messages.0.content', "You're welcome! Ask me anything else about your trip.");
        $this->assertSame([], $model->requests);
    }

    public function test_old_assistant_route_and_validation(): void
    {
        $this->useModel([]);
        $this->postJson('/api/v1/assistant/message', [])->assertStatus(422);
        $this->postJson('/api/v1/chat/message', ['message' => str_repeat('a', 2001)])->assertStatus(422);
    }

    public function test_a_failed_model_returns_503_with_a_message(): void
    {
        $this->useModel([fn() => throw new \App\Chat\Models\ModelError('down')]);
        $this->postJson('/api/v1/chat/message', ['message' => 'hi'])
            ->assertStatus(503)
            ->assertJsonPath('failed', true);
    }

    public function test_chat_is_rate_limited(): void
    {
        $this->useModel(array_fill(0, 12, ScriptedModel::say('ok')));
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/chat/message', ['message' => "q{$i}"])->assertOk();
        }
        $this->postJson('/api/v1/chat/message', ['message' => 'one too many'])->assertStatus(429);
    }
}
