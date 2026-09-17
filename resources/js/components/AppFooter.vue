<template>
    <footer class="bg-ink-950 text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.5fr_1fr_1fr] lg:px-8">
            <div class="max-w-md">
                <div class="mb-4 flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-2xl bg-brand-500">
                        <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M5 15.5V9.8c0-.8.3-1.5.8-2.1l2.1-2.3c.5-.6 1.3-.9 2.1-.9h4c.8 0 1.6.3 2.1.9l2.1 2.3c.5.6.8 1.3.8 2.1v5.7M3.5 12.5h17v5h-17z" />
                        </svg>
                    </span>
                    <span class="text-2xl font-black">موثوق</span>
                </div>
                <p class="text-sm leading-7 text-white/60">منصة مصرية تساعدك تختار مركز الصيانة المناسب لسيارتك بناءً على بيانات واضحة وتقييمات حقيقية.</p>
            </div>
            <div>
                <h2 class="mb-4 text-sm font-black">استكشف</h2>
                <div class="flex flex-col gap-3 text-sm text-white/55">
                    <RouterLink to="/" class="hover:text-white">مراكز الصيانة</RouterLink>
                    <a href="/#how-it-works" class="hover:text-white">كيف يعمل موثوق؟</a>
                    <span>المدونة قريبًا</span>
                </div>
            </div>
            <div>
                <h2 class="mb-4 text-sm font-black">لأصحاب المراكز</h2>
                <div class="flex flex-col gap-3 text-sm text-white/55">
                    <span>سجّل مركزك</span>
                    <span>إدارة بيانات المركز</span>
                    <span>تواصل معنا</span>
                </div>
            </div>
        </div>
        <div class="border-t border-white/8 px-4 py-5 text-center text-xs text-white/40">
            © {{ new Date().getFullYear() }} موثوق. كل الحقوق محفوظة.
        </div>
    </footer>
</template>
