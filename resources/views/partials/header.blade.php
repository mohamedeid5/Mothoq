<header class="sticky top-0 z-50 border-b border-white/10 bg-ink-950/95 text-white backdrop-blur-xl">
    <div class="mx-auto flex h-18 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="group flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-950/30 transition group-hover:-rotate-3">
                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M5 15.5V9.8c0-.8.3-1.5.8-2.1l2.1-2.3c.5-.6 1.3-.9 2.1-.9h4c.8 0 1.6.3 2.1.9l2.1 2.3c.5.6.8 1.3.8 2.1v5.7" />
                    <path d="M3.5 12.5h17v5h-17zM6 17.5v2M18 17.5v2M7.5 9h9" />
                    <circle cx="7" cy="15" r="1" fill="currentColor" stroke="none" />
                    <circle cx="17" cy="15" r="1" fill="currentColor" stroke="none" />
                </svg>
            </span>
            <span>
                <span class="block text-xl font-black tracking-tight">موثوق</span>
                <span class="block text-[10px] font-medium tracking-wide text-brand-200">صيانة سيارتك بثقة</span>
            </span>
        </a>

        <nav class="hidden items-center gap-8 text-sm font-semibold text-white/75 md:flex" aria-label="التنقل الرئيسي">
            <a href="{{ route('home') }}" class="transition hover:text-white">الرئيسية</a>
            <a href="{{ route('home') }}#centers" class="transition hover:text-white">مراكز الصيانة</a>
            <a href="{{ route('home') }}#how-it-works" class="transition hover:text-white">كيف يعمل؟</a>
        </nav>

        <div class="hidden items-center gap-3 md:flex">
            @auth
                <a href="{{ route(auth()->user()->role->dashboardRouteName()) }}" class="rounded-xl px-4 py-2.5 text-sm font-bold text-white/80 transition hover:bg-white/8 hover:text-white">
                    {{ auth()->user()->name }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-xl bg-white/8 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/12">تسجيل الخروج</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-xl px-4 py-2.5 text-sm font-bold text-white/80 transition hover:bg-white/8 hover:text-white">تسجيل الدخول</a>
                <a href="{{ route('login') }}" class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-brand-400">أضف مركزك</a>
            @endauth
        </div>

        <button type="button" data-menu-button class="grid size-11 place-items-center rounded-xl border border-white/10 bg-white/5 md:hidden" aria-expanded="false" aria-controls="mobile-menu" aria-label="فتح القائمة">
            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>
    </div>

    <div id="mobile-menu" data-mobile-menu class="hidden border-t border-white/10 px-4 py-4 md:hidden">
        <nav class="mx-auto flex max-w-7xl flex-col gap-1 text-sm font-bold">
            <a href="{{ route('home') }}" class="rounded-xl px-4 py-3 hover:bg-white/8">الرئيسية</a>
            <a href="{{ route('home') }}#centers" class="rounded-xl px-4 py-3 hover:bg-white/8">مراكز الصيانة</a>
            <a href="{{ route('home') }}#how-it-works" class="rounded-xl px-4 py-3 hover:bg-white/8">كيف يعمل؟</a>
            <div class="mt-2 grid grid-cols-2 gap-2 border-t border-white/10 pt-4">
                @auth
                    <a href="{{ route(auth()->user()->role->dashboardRouteName()) }}" class="rounded-xl border border-white/15 px-3 py-3 text-center">حسابي</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="h-full w-full rounded-xl bg-brand-500 px-3 py-3">خروج</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl border border-white/15 px-3 py-3 text-center">دخول</a>
                    <a href="{{ route('login') }}" class="rounded-xl bg-brand-500 px-3 py-3 text-center">أضف مركزك</a>
                @endauth
            </div>
        </nav>
    </div>
</header>
