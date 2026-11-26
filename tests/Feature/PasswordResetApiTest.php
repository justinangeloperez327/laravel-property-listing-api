<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_is_emailed_with_frontend_url(): void
    {
        Notification::fake();
        config(['app.frontend_url' => 'https://app.example.com']);
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson(route('api.v1.password.email'), ['email' => 'jane@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://app.example.com/reset-password?token=')
                && str_contains($url, 'email=jane%40example.com');
        });
    }

    public function test_unknown_email_gets_same_response_and_no_email(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'jane@example.com']);

        $known = $this->postJson(route('api.v1.password.email'), ['email' => 'jane@example.com'])->json();
        $unknown = $this->postJson(route('api.v1.password.email'), ['email' => 'nobody@example.com'])
            ->assertOk()
            ->json();

        $this->assertSame($known, $unknown);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_password_can_be_reset_and_old_tokens_are_revoked(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $user->createToken('api');
        $token = Password::createToken($user);

        $this->postJson(route('api.v1.password.update'), [
            'token' => $token,
            'email' => 'jane@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));
        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson(route('api.v1.password.update'), [
            'token' => 'not-a-real-token',
            'email' => 'jane@example.com',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'This password reset token is invalid.']);

        $this->assertFalse(Hash::check('new-secret-password', $user->fresh()->password));
    }
}
