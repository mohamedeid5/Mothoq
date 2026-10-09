@extends('layouts.app')

@section('title', 'إنشاء حساب | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-ink-950 px-4 py-12 sm:px-6">
        <div class="mx-auto max-w-lg rounded-[2rem] bg-white p-6 shadow-2xl sm:p-8">
            <p class="text-sm font-bold text-brand-700">حسابك على موثوق</p>
            <h1 class="mt-2 text-3xl font-black text-ink-950">إنشاء حساب</h1>
            <p class="mt-3 text-sm leading-6 text-slate-500">أنشئ حسابك لحجز الصيانة ومتابعة حجوزاتك وتقييم تجربتك.</p>
            @if (session('status'))
                <p role="status" class="mt-5 rounded-2xl bg-brand-50 p-4 text-sm leading-6 text-brand-700">{{ session('status') }}</p>
            @endif
            <form method="POST" action="{{ route('register.store') }}" class="mt-7 grid gap-5">
                @csrf
                <label class="grid gap-2 text-sm font-bold text-ink-950">الاسم
                    <input name="name" type="text" autocomplete="name" value="{{ old('name') }}" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @error('name')<span role="alert" class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
<label class="grid gap-2 text-sm font-bold text-ink-950">البريد الإلكتروني
                    <input name="email" type="email" autocomplete="email" value="{{ old('email') }}" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @error('email')<span role="alert" class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
<label class="grid gap-2 text-sm font-bold text-ink-950">كلمة المرور (8 أحرف على الأقل)
                    <input name="password" type="password" autocomplete="new-password" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @error('password')<span role="alert" class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
<label class="grid gap-2 text-sm font-bold text-ink-950">تأكيد كلمة المرور
                    <input name="password_confirmation" type="password" autocomplete="new-password" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    @error('password_confirmation')<span role="alert" class="text-xs text-red-600">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="h-13 rounded-2xl bg-brand-600 px-5 font-black text-white transition hover:bg-brand-500">إنشاء الحساب</button>
            </form>
            <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-bold text-brand-700 hover:underline">العودة لتسجيل الدخول</a>
        </div>
    </section>
@endsection
