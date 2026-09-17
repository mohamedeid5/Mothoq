<script setup>
import { auth } from '../auth';
</script>

<template>
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="rounded-[2rem] bg-ink-950 p-7 text-white sm:p-10">
                <p class="text-sm font-bold text-brand-300">لوحة الإدارة</p>
                <h1 class="mt-2 text-3xl font-black sm:text-4xl">مرحبًا، {{ auth.state.user?.name }}</h1>
                <p class="mt-3 max-w-2xl text-white/60">تم تسجيل دخولك بصلاحية مدير. أدوات إدارة المراكز ستكون الخطوة التالية.</p>
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-3">
                <article class="rounded-3xl bg-white p-6 surface-shadow">
                    <span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-700">⌁</span>
                    <h2 class="mt-5 text-xl font-black text-ink-950">مراكز الصيانة</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">مراجعة بيانات المراكز وإدارة حالة النشر.</p>
                </article>
                <article class="rounded-3xl bg-white p-6 surface-shadow">
                    <span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-700">✓</span>
                    <h2 class="mt-5 text-xl font-black text-ink-950">التوثيق</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">متابعة طلبات توثيق المراكز واعتمادها.</p>
                </article>
                <article class="rounded-3xl bg-white p-6 surface-shadow">
                    <span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-700">★</span>
                    <h2 class="mt-5 text-xl font-black text-ink-950">التقييمات</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">مراجعة التقييمات والمحافظة على جودة المنصة.</p>
                </article>
            </div>
        </div>
    </section>
</template>
