@extends('layouts.app')

@section('title', 'لوحة الإدارة | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <header class="rounded-[2rem] bg-ink-950 p-7 text-white sm:p-10"><p class="text-sm font-bold text-brand-300">لوحة الإدارة</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">مرحبًا، {{ auth()->user()->name }}</h1><p class="mt-3 max-w-2xl text-white/60">من هنا تقدر تدير مراكز الصيانة وتراجع تقييمات العملاء.</p></header>
            <div class="mt-8 grid gap-5 md:grid-cols-3">
                <a href="{{ route('admin.service-centers.index') }}" class="rounded-3xl bg-white p-6 surface-shadow transition hover:-translate-y-1"><span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-700">⌁</span><h2 class="mt-5 text-xl font-black text-ink-950">مراكز الصيانة</h2><p class="mt-2 text-sm leading-6 text-slate-500">مراجعة بيانات المراكز وإدارة حالة النشر والتوثيق.</p><span class="mt-5 inline-flex text-sm font-black text-brand-700">فتح الإدارة ←</span></a>
                <article class="rounded-3xl bg-white p-6 surface-shadow"><span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-700">✓</span><h2 class="mt-5 text-xl font-black text-ink-950">التوثيق</h2><p class="mt-2 text-sm leading-6 text-slate-500">تابع حالة التوثيق من صفحة إدارة المراكز.</p></article>
                <a href="{{ route('admin.reviews.index') }}" class="rounded-3xl bg-white p-6 surface-shadow transition hover:-translate-y-1"><span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-700">★</span><h2 class="mt-5 text-xl font-black text-ink-950">التقييمات</h2><p class="mt-2 text-sm leading-6 text-slate-500">راجع تقييمات العملاء وانشر المناسب منها أو ارفضه.</p><span class="mt-5 inline-flex text-sm font-black text-brand-700">فتح المراجعة ←</span></a>
            </div>
        </div>
    </section>
@endsection
