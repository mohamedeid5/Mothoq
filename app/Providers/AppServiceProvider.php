<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            $url = rtrim(config('app.url'), '/').route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], false);

            return (new MailMessage)
                ->subject('استرجاع كلمة المرور | موثوق')
                ->greeting('أهلاً بك!')
                ->line('وصلنا طلب لاسترجاع كلمة المرور لحسابك على موثوق.')
                ->action('تغيير كلمة المرور', $url)
                ->line('الرابط صالح لمدة '.config('auth.passwords.users.expire').' دقيقة.')
                ->line('لو لم تطلب تغيير كلمة المرور، تجاهل هذه الرسالة.')
                ->salutation('فريق موثوق');
        });

        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('login', function (Request $request): Limit {
            $key = Str::transliterate(
                Str::lower($request->string('email')->trim()->toString()).'|'.$request->ip(),
            );

            return Limit::perMinute(5)->by($key);
        });
    }
}
