<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated()->assertJsonStructure(['user' => ['id', 'email'], 'token']);
    }

    public function test_user_can_login_and_use_token(): void
    {
        User::factory()->create(['email' => 'jane@example.com', 'password' => 'secret-password']);

        $token = $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('email', 'jane@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'jane@example.com']);

        $this->postJson('/api/v1/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_expired_token_is_rejected(): void
    {
        $token = User::factory()->create()->createToken('api')->plainTextToken;

        $this->travel(config('sanctum.expiration') + 1)->minutes();

        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/logout')->assertNoContent();
        $this->assertCount(0, $user->tokens);
    }
}
