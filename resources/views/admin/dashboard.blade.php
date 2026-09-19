@extends('layouts.app')

@section('title', 'لوحة الإدارة | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <header class="rounded-[2rem] bg-ink-950 p-7 text-white sm:p-10"><p class="text-sm font-bold text-brand-300">لوحة الإدارة</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">مرحبًا، {{ auth()->user()->name }}</h1><p class="mt-3 max-w-2xl text-white/60">تم تسجيل دخولك بصلاحية مدير. أدوات إدارة المراكز ستكون الخطوة التالية.</p></header>
            <div class="mt-8 grid gap-5 md:grid-cols-3">
                @foreach ([['⌁', 'مراكز الصيانة', 'مراجعة بيانات المراكز وإدارة حالة النشر.'], ['✓', 'التوثيق', 'متابعة طلبات توثيق المراكز واعتمادها.'], ['★', 'التقييمات', 'مراجعة التقييمات والمحافظة على جودة المنصة.']] as $item)
                    <article class="rounded-3xl bg-white p-6 surface-shadow"><span class="grid size-11 place-items-center rounded-2xl bg-brand-50 text-xl text-brand-700">{{ $item[0] }}</span><h2 class="mt-5 text-xl font-black text-ink-950">{{ $item[1] }}</h2><p class="mt-2 text-sm leading-6 text-slate-500">{{ $item[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
