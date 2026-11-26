<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    private function verificationUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('api.v1.verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->email),
        ]);
    }

    public function test_registration_sends_verification_email(): void
    {
        Notification::fake();

        $this->postJson(route('api.v1.register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated();

        Notification::assertSentTo(User::firstWhere('email', 'jane@example.com'), VerifyEmail::class);
    }

    public function test_signed_link_verifies_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson($this->verificationUrl($user))->assertOk();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_unsigned_or_tampered_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->getJson(route('api.v1.verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();
        $this->getJson($this->verificationUrl($user, sha1('other@example.com')))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = Sanctum::actingAs(User::factory()->unverified()->create());

        $this->postJson(route('api.v1.verification.send'))->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_cannot_create_property(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->postJson(route('api.v1.properties.store'), [])
            ->assertForbidden()
            ->assertJsonPath('message', 'Your email address is not verified.');
    }
}
