<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\Support\RefreshTestDatabase;
use Tests\TestCase;

class AuthAndDocsTest extends TestCase
{
    use RefreshTestDatabase;

    /** Requests from the app's own pages are stateful (session + CSRF) under Sanctum. */
    private const FIRST_PARTY = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    public function test_register_sign_in_and_out(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Hiker', 'email' => 'ada@example.test', 'password' => 'correct-horse-9', 'password_confirmation' => 'correct-horse-9',
        ], self::FIRST_PARTY)->assertCreated();
        $this->assertAuthenticated();
        $this->getJson('/api/v1/auth/user', self::FIRST_PARTY)->assertOk()->assertJsonPath('email', 'ada@example.test');

        $this->postJson('/api/v1/auth/logout', [], self::FIRST_PARTY)->assertNoContent();
        $this->assertGuest();

        $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.test', 'password' => 'wrong'], self::FIRST_PARTY)
            ->assertStatus(422);
        $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.test', 'password' => 'correct-horse-9'], self::FIRST_PARTY)
            ->assertOk();
        $this->assertAuthenticatedAs(User::where('email', 'ada@example.test')->first());
    }

    public function test_current_user_is_null_when_signed_out(): void
    {
        $this->getJson('/api/v1/auth/user')->assertOk()->assertExactJson([]);
    }

    public function test_docs_are_served_from_the_built_site(): void
    {
        $root = storage_path('framework/testing/docs');
        File::deleteDirectory($root);
        File::ensureDirectoryExists($root . '/planning/_astro');
        File::put($root . '/index.html', '<h1>Guide</h1>');
        File::put($root . '/planning/index.html', '<h1>Planning</h1>');
        File::put($root . '/planning/_astro/app.css', 'body{}');
        File::put($root . '/404.html', '<h1>Missing</h1>');
        File::put(storage_path('framework/testing/secret.txt'), 'nope');
        config(['app.docs_path' => $root]);

        $this->get('/docs')->assertOk()->assertSee('Guide', false);
        $this->get('/docs/planning')->assertOk()->assertSee('Planning', false);
        $css = $this->get('/docs/planning/_astro/app.css')->assertOk();
        $this->assertStringStartsWith('text/css', $css->headers->get('Content-Type'));
        $this->assertStringContainsString('immutable', $css->headers->get('Cache-Control'));
        $this->get('/docs/nope/')->assertNotFound()->assertSee('Missing', false);
        $this->get('/docs/../secret.txt')->assertNotFound();
        $this->get('/docs/..%2Fsecret.txt')->assertNotFound();

        File::deleteDirectory($root);
    }

    public function test_docs_404_when_not_built(): void
    {
        config(['app.docs_path' => storage_path('framework/testing/no-docs-here')]);
        $this->get('/docs')->assertNotFound();
    }

    public function test_index_endpoints_are_paginated(): void
    {
        $this->getJson('/api/v1/trail?per_page=5')->assertOk()->assertJsonStructure(['data', 'current_page', 'per_page', 'total'])
            ->assertJsonPath('per_page', 5);
        $this->getJson('/api/v1/restaurant?per_page=500')->assertOk()->assertJsonPath('per_page', 100);
    }
}
