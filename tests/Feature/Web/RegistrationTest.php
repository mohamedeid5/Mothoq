<?php

namespace Tests\Feature\Web;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_can_open_registration_from_login(): void
    {
        $this->get(route('login'))->assertSee(route('register'))->assertSee(route('password.request'));
        $this->get(route('register'))->assertSee('إنشاء حساب')->assertSee('password_confirmation');
    }

    public function test_registration_normalizes_details_and_logs_in_as_customer_despite_supplied_role(): void
    {
        Event::fake([Registered::class]);

        $this->post(route('register.store'), [
            'name' => ' Ahmed ',
            'email' => ' AHMED@example.com ',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'role' => 'admin',
            'email_verified_at' => now(),
        ])->assertRedirect(route('bookings.index'));

        $user = User::where('email', 'ahmed@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Ahmed', $user->name);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('new-password', $user->password));
        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
    }

    public function test_registration_returns_customer_to_original_destination(): void
    {
        $this->withSession(['url.intended' => route('home')])->post(route('register.store'), [
            'name' => 'Ahmed', 'email' => 'ahmed@example.com',
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    public function test_registration_rejects_duplicate_email_and_mismatched_password_without_creating_user(): void
    {
        User::factory()->create(['email' => 'ahmed@example.com']);

        $this->post(route('register.store'), [
            'name' => 'Ahmed', 'email' => 'AHMED@example.com',
            'password' => 'new-password', 'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors([
            'email' => 'البريد الإلكتروني مستخدم بالفعل.',
            'password' => 'تأكيد كلمة المرور غير مطابق.',
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_registration_requires_name_email_and_password(): void
    {
        $this->post(route('register.store'))->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_short_password(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Ahmed', 'email' => 'ahmed@example.com',
            'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_signed_in_user_cannot_register_another_account(): void
    {
        $this->actingAs(User::factory()->create())->post(route('register.store'))
            ->assertRedirect(route('bookings.index'));
        $this->assertDatabaseCount('users', 1);
    }
}
