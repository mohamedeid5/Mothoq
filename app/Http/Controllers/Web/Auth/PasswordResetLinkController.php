<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Auth\PasswordResetLinkRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(PasswordResetLinkRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        return back()->with('status', 'لو البريد الإلكتروني مسجل عندنا، هيوصلك رابط لاسترجاع كلمة المرور. لو طلبت رابط من لحظات، راجع بريدك أو حاول بعد دقيقة.');
    }
}
