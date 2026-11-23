<?php

namespace Tests\Feature;

use App\Http\Responses\PasswordResetLinkResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Support\RefreshTestDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshTestDatabase;

    private const FIRST_PARTY = ['Referer' => 'http://localhost/', 'Accept' => 'application/json'];

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create(['name' => 'Ada Hiker', 'email' => 'ada@example.test', 'password' => Hash::make('old-password-1')]);
    }

    public function test_a_reset_link_is_emailed_and_points_at_the_app(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ada@example.test'], self::FIRST_PARTY)
            ->assertOk()->assertJsonPath('message', PasswordResetLinkResponse::MESSAGE);

        Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification) {
            $mail = $notification->toMail($this->user);
            $this->assertStringContainsString('Reset your', $mail->subject);
            $this->assertSame(
                url('/reset-password/' . $notification->token) . '?email=' . urlencode('ada@example.test'),
                $mail->actionUrl,
            );
            return true;
        });
    }

    public function test_unknown_addresses_get_the_same_answer(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.test'], self::FIRST_PARTY)
            ->assertOk()->assertJsonPath('message', PasswordResetLinkResponse::MESSAGE);
        Notification::assertNothingSent();
    }

    public function test_asking_again_straight_away_is_throttled(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ada@example.test'], self::FIRST_PARTY)->assertOk();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ada@example.test'], self::FIRST_PARTY)
            ->assertStatus(422)->assertJsonValidationErrors('email');
        Notification::assertSentToTimes($this->user, ResetPassword::class, 1);
    }

    public function test_the_link_resets_the_password_once(): void
    {
        Notification::fake();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'ada@example.test'], self::FIRST_PARTY);
        $token = null;
        Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;
            return true;
        });

        $this->get('/reset-password/' . $token . '?email=ada%40example.test')->assertOk()->assertViewIs('app');

        $reset = fn(string $password, string $confirmation) => $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token, 'email' => 'ada@example.test', 'password' => $password, 'password_confirmation' => $confirmation,
        ], self::FIRST_PARTY);

        $reset('new-password-2', 'does-not-match')->assertStatus(422)->assertJsonValidationErrors('password');
        $reset('new-password-2', 'new-password-2')->assertOk();
        $this->assertTrue(Hash::check('new-password-2', $this->user->fresh()->password));

        $reset('another-password-3', 'another-password-3')->assertStatus(422)->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.test', 'password' => 'new-password-2'], self::FIRST_PARTY)
            ->assertOk();
    }

    public function test_a_made_up_token_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token', 'email' => 'ada@example.test', 'password' => 'new-password-2', 'password_confirmation' => 'new-password-2',
        ], self::FIRST_PARTY)->assertStatus(422)->assertJsonValidationErrors('email');
        $this->assertTrue(Hash::check('old-password-1', $this->user->fresh()->password));
    }

    public function test_google_only_accounts_can_set_a_password(): void
    {
        Notification::fake();
        $google = User::create(['name' => 'Gia', 'email' => 'gia@example.test', 'provider' => 'google', 'provider_id' => '42']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'gia@example.test'], self::FIRST_PARTY)->assertOk();
        Notification::assertSentTo($google, ResetPassword::class);
    }
}
