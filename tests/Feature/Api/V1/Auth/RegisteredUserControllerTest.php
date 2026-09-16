<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisteredUserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_payload_registers_customer_and_returns_bearer_token(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => '  Mohamed Eid  ',
            'email' => '  MOHAMED@example.com ',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'device_name' => 'iPhone 17',
            'role' => UserRole::Admin->value,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.user.name', 'Mohamed Eid')
            ->assertJsonPath('data.user.email', 'mohamed@example.com')
            ->assertJsonPath('data.user.role', UserRole::Customer->value)
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'role'], 'token']]);

        $user = User::query()->sole();

        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertSame(1, $user->tokens()->count());

        $this->withToken($response->json('data.token'))
            ->getJson(route('api.v1.auth.me'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_returns_422_when_required_registration_fields_are_missing(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'));

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'device_name',
            ])
            ->assertJsonPath('errors.email.0', 'The email field is required.');

        $this->assertSame(0, User::count());
    }

    public function test_returns_422_when_email_is_already_registered(): void
    {
        User::factory()->create(['email' => 'mohamed@example.com']);

        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Mohamed Eid',
            'email' => 'mohamed@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'device_name' => 'Web browser',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email')
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');

        $this->assertSame(1, User::count());
    }

    public function test_returns_422_when_password_confirmation_does_not_match(): void
    {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Mohamed Eid',
            'email' => 'mohamed@example.com',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
            'device_name' => 'Web browser',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password')
            ->assertJsonPath('errors.password.0', 'The password field confirmation does not match.');

        $this->assertSame(0, User::count());
    }
}
