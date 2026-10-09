<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_can_view_recovery_and_reset_forms(): void
    {
        $this->get(route('password.request'))->assertSee('إرسال رابط الاسترجاع');
        $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'user@example.com']))
            ->assertSee('sample-token')->assertSee('user@example.com');
    }

    public function test_recovery_sends_arabic_notification_with_reset_link_on_configured_host(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);
        config(['app.url' => 'https://mothoq.example']);

        $this->from(route('password.request'))->post(route('password.email'), ['email' => ' USER@example.com '])
            ->assertRedirect(route('password.request'))->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);
            $this->assertSame('استرجاع كلمة المرور | موثوق', $mail->subject);
            $this->assertSame('https://mothoq.example'.route('password.reset', [
                'token' => $notification->token, 'email' => $user->email,
            ], false), $mail->actionUrl);
            $this->assertTrue(Password::tokenExists($user, $notification->token));

            return true;
        });
    }

    public function test_unknown_email_gets_same_status_without_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);
        $status = session('status');
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertSessionHas('status', $status);
        Notification::assertNothingSent();
    }

    public function test_repeated_recovery_request_does_not_send_duplicate_mail(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_recovery_rejects_invalid_email_without_sending_mail(): void
    {
        Notification::fake();
        $this->post(route('password.email'), ['email' => 'invalid'])->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_valid_reset_changes_password_rotates_remember_token_and_consumes_link(): void
    {
        $user = User::factory()->create(['password' => 'old-password', 'remember_token' => 'old-token']);
        $token = Password::createToken($user);
        Event::fake([PasswordReset::class]);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'new-password', 'password_confirmation' => 'new-password'];

        $this->post(route('password.store'), $payload)->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotSame('old-token', $user->fresh()->remember_token);
        $this->assertGuest();
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));
        $this->post(route('password.store'), $payload)->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'new-password'])->assertRedirect(route('bookings.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_link_does_not_change_password(): void
    {
        $this->freezeTime();
        $user = User::factory()->create(['password' => 'old-password']);
        $token = Password::createToken($user);
        $this->travel(61)->minutes();

        $this->post(route('password.store'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_token_for_another_user_cannot_reset_password(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create(['password' => 'old-password']);
        $this->post(route('password.store'), [
            'email' => $target->email, 'token' => Password::createToken($owner),
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $target->fresh()->password));
    }

    public function test_reset_rejects_unconfirmed_password_and_preserves_token(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = Password::createToken($user);
        $this->post(route('password.store'), [
            'email' => $user->email, 'token' => $token,
            'password' => 'new-password', 'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_signed_in_user_cannot_request_recovery_or_reset_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('bookings.index'));
        $this->post(route('password.store'))->assertRedirect(route('bookings.index'));
        Notification::assertNothingSent();
    }

    public function test_recovery_requests_are_rate_limited(): void
    {
        Notification::fake();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertTooManyRequests();
        Notification::assertNothingSent();
    }

    public function test_reset_requires_token_email_and_password(): void
    {
        $this->post(route('password.store'))->assertSessionHasErrors(['token', 'email', 'password']);
        $this->assertGuest();
    }

    public function test_reset_rejects_short_password_without_changing_it(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->post(route('password.store'), [
            'email' => $user->email, 'token' => Password::createToken($user),
            'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
