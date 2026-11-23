<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    /**
     * Several /api/v1 routes pointed at controller methods that didn't exist, so they could only ever 500.
     */
    public function test_every_controller_route_points_at_a_real_method(): void
    {
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (!str_contains($action, '@')) {
                continue; // closures
            }
            [$class, $method] = explode('@', $action);
            if (!class_exists($class) || !method_exists($class, $method)) {
                $missing[] = implode('|', $route->methods()) . ' /' . $route->uri() . ' → ' . $action;
            }
        }

        $this->assertSame([], $missing, "Routes without a controller method:\n" . implode("\n", $missing));
    }
}
