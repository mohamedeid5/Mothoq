<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_credentials_return_bearer_token(): void
    {
        $user = User::factory()->centerOwner()->create([
            'email' => 'owner@example.com',
            'password' => 'secure-password',
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => ' OWNER@example.com ',
            'password' => 'secure-password',
            'device_name' => 'Web browser',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'center_owner')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertSame(1, $user->tokens()->count());

        $this->withToken($response->json('data.token'))
            ->getJson(route('api.v1.auth.me'))
            ->assertOk();
    }

    public function test_returns_422_for_incorrect_password_without_creating_token(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => 'secure-password',
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'customer@example.com',
            'password' => 'incorrect-password',
            'device_name' => 'Web browser',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_unknown_email_returns_same_error_as_incorrect_password(): void
    {
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'unknown@example.com',
            'password' => 'secure-password',
            'device_name' => 'Web browser',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');
    }

    public function test_returns_422_when_login_fields_are_missing(): void
    {
        $response = $this->postJson(route('api.v1.auth.login'));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_returns_429_after_five_failed_attempts_for_same_email_and_ip(): void
    {
        $payload = [
            'email' => 'unknown@example.com',
            'password' => 'incorrect-password',
            'device_name' => 'Web browser',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('api.v1.auth.login'), $payload)->assertUnprocessable();
        }

        $this->postJson(route('api.v1.auth.login'), $payload)
            ->assertTooManyRequests();
    }

    public function test_logout_revokes_only_current_bearer_token(): void
    {
        $user = User::factory()->create();
        $currentToken = $user->createToken('Current device')->plainTextToken;
        $otherToken = $user->createToken('Other device')->plainTextToken;

        $this->withToken($currentToken)
            ->deleteJson(route('api.v1.auth.logout'))
            ->assertNoContent();

        $this->assertSame(1, $user->tokens()->count());
        $this->app['auth']->forgetGuards();

        $this->withToken($currentToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertUnauthorized();

        $this->app['auth']->forgetGuards();

        $this->withToken($otherToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertOk();
    }

    public function test_logout_returns_401_without_bearer_token(): void
    {
        $this->deleteJson(route('api.v1.auth.logout'))
            ->assertUnauthorized();

    }
}
