<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CurrentUserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_request_returns_current_user(): void
    {
        $user = User::factory()->admin()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.com',
        ]);
        $token = $user->createToken('Web browser')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson(route('api.v1.auth.me'));

        $response
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'System Admin',
                    'email' => 'admin@example.com',
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_returns_401_without_bearer_token(): void
    {
        $this->getJson(route('api.v1.auth.me'))
            ->assertUnauthorized();
    }

    public function test_returns_401_for_invalid_bearer_token(): void
    {
        $this->withToken('invalid-token')
            ->getJson(route('api.v1.auth.me'))
            ->assertUnauthorized();
    }

    public function test_accepts_bearer_token_created_less_than_30_days_ago(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Web browser');
        $token->accessToken->forceFill(['created_at' => now()->subDays(29)])->save();

        $this->withToken($token->plainTextToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertOk();
    }

    public function test_returns_401_for_bearer_token_created_more_than_30_days_ago(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Web browser');
        $token->accessToken->forceFill(['created_at' => now()->subDays(31)])->save();

        $this->withToken($token->plainTextToken)
            ->getJson(route('api.v1.auth.me'))
            ->assertUnauthorized();
    }
}
