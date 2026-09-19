@extends('layouts.app')

@section('title', 'تسجيل الدخول | موثوق')

@section('content')
    <section class="relative isolate min-h-[calc(100vh-4.5rem)] overflow-hidden bg-ink-950 px-4 py-12 sm:px-6 lg:px-8">
        <div class="absolute inset-0 -z-10 opacity-30 [background-image:linear-gradient(rgba(255,255,255,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.06)_1px,transparent_1px)] [background-size:48px_48px]"></div>
        <div class="mx-auto grid max-w-5xl items-center gap-10 lg:grid-cols-[1fr_.85fr]">
            <div class="text-white">
                <span class="inline-flex rounded-full border border-brand-300/20 bg-brand-400/10 px-4 py-2 text-sm font-bold text-brand-200">حساب واحد لإدارة كل شيء</span>
                <h1 class="mt-6 max-w-xl text-4xl font-black leading-tight sm:text-5xl">أهلاً بعودتك إلى <span class="text-brand-300">موثوق</span></h1>
                <p class="mt-5 max-w-lg text-lg leading-8 text-white/65">ادخل إلى حسابك لمتابعة بيانات مركزك وإدارة حضوره على المنصة بأمان.</p>
                <div class="mt-8 grid max-w-lg gap-3 text-sm text-white/70 sm:grid-cols-2"><div class="rounded-2xl border border-white/10 bg-white/5 p-4">✓ دخول محمي بالـSession</div><div class="rounded-2xl border border-white/10 bg-white/5 p-4">✓ توجيه من Laravel حسب دورك</div></div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-2xl shadow-black/25 sm:p-8">
                <p class="text-sm font-bold text-brand-700">تسجيل الدخول</p>
                <h2 class="mt-2 text-3xl font-black text-ink-950">ادخل بيانات حسابك</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">استخدم البريد الإلكتروني وكلمة المرور المسجلين لدينا.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-7 grid gap-5">
                    @csrf
                    <label class="grid gap-2 text-sm font-bold text-ink-950">البريد الإلكتروني
                        <input name="email" value="{{ old('email') }}" type="email" autocomplete="email" required autofocus class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @error('email')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-bold text-ink-950">كلمة المرور
                        <input name="password" type="password" autocomplete="current-password" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @error('password')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-600"><input name="remember" value="1" type="checkbox" class="size-4 rounded border-slate-300 text-brand-600"> تذكرني</label>
                    <button type="submit" class="mt-1 h-13 rounded-2xl bg-brand-600 px-5 font-black text-white transition hover:bg-brand-500">تسجيل الدخول</button>
                </form>
            </div>
        </div>
    </section>
@endsection
