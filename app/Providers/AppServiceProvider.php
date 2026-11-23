<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
