<footer class="bg-ink-950 text-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.5fr_1fr_1fr] lg:px-8">
        <div class="max-w-md">
            <div class="mb-4 flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-2xl bg-brand-500">⌁</span>
                <span class="text-2xl font-black">موثوق</span>
            </div>
            <p class="text-sm leading-7 text-white/60">منصة مصرية تساعدك تختار مركز الصيانة المناسب لسيارتك بناءً على بيانات واضحة وتقييمات حقيقية.</p>
        </div>
        <div>
            <h2 class="mb-4 text-sm font-black">استكشف</h2>
            <div class="flex flex-col gap-3 text-sm text-white/55">
                <a href="{{ route('home') }}#centers" class="hover:text-white">مراكز الصيانة</a>
                <a href="{{ route('home') }}#how-it-works" class="hover:text-white">كيف يعمل موثوق؟</a>
                <span>المدونة قريبًا</span>
            </div>
        </div>
        <div>
            <h2 class="mb-4 text-sm font-black">لأصحاب المراكز</h2>
            <div class="flex flex-col gap-3 text-sm text-white/55">
                <a href="{{ route('login') }}" class="hover:text-white">إدارة بيانات المركز</a>
                <span>تواصل معنا</span>
            </div>
        </div>
    </div>
    <div class="border-t border-white/8 px-4 py-5 text-center text-xs text-white/40">© {{ now()->year }} موثوق. كل الحقوق محفوظة.</div>
</footer>
