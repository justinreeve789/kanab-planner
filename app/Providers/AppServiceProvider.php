<?php

namespace App\Providers;

use App\Chat\ChatAgent;
use App\Chat\Models\AnthropicMessagesModel;
use App\Chat\Models\ChatModel;
use App\Chat\Models\OpenAIResponsesModel;
use App\Chat\SystemPrompt;
use App\Chat\Tools\ToolRegistry;
use App\Models\User;
use App\Services\Routing\EstimateRouter;
use App\Services\Routing\Router;
use App\Services\Routing\TravelTimes;
use App\Services\Routing\ValhallaRouter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EstimateRouter::class, fn() => new EstimateRouter(
            config('routing.estimate.road_factor'), config('routing.estimate.mph')));
        $this->app->bind(Router::class, fn($app) => match (config('routing.driver')) {
            'valhalla' => new ValhallaRouter(config('routing.valhalla.url'), config('routing.valhalla.timeout')),
            default => $app->make(EstimateRouter::class),
        });
        $this->app->bind(TravelTimes::class, fn($app) => new TravelTimes($app->make(Router::class), $app->make(EstimateRouter::class)));

        // The trip assistant (app/Chat). Tests swap ChatModel for a ScriptedModel.
        $this->app->bind(ChatModel::class, fn() => match (config('chat.driver')) {
            'anthropic' => new AnthropicMessagesModel(
                (string) config('chat.anthropic.api_key'), config('chat.anthropic.model'), config('chat.anthropic.base_url'),
                config('chat.anthropic.version'), (int) config('chat.timeout_seconds')),
            default => new OpenAIResponsesModel(
                (string) config('chat.openai.api_key'), config('chat.openai.model'), config('chat.openai.base_url'),
                config('chat.openai.reasoning_effort') ?: null, (int) config('chat.timeout_seconds')),
        });
        $this->app->bind(ToolRegistry::class, fn() => ToolRegistry::default());
        $this->app->bind(ChatAgent::class, fn($app) => new ChatAgent($app->make(ChatModel::class), $app->make(ToolRegistry::class), $app->make(SystemPrompt::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
