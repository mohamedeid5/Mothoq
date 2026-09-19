@extends('layouts.app')

@section('title', 'لوحة صاحب المركز | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <header class="rounded-[2rem] bg-ink-950 p-7 text-white sm:p-10">
                <p class="text-sm font-bold text-brand-300">لوحة صاحب المركز</p>
                <h1 class="mt-2 text-3xl font-black sm:text-4xl">أهلاً، {{ auth()->user()->name }}</h1>
                <p class="mt-3 max-w-2xl text-white/60">راجع مراكزك وحدّث البيانات التي تظهر للعملاء.</p>
            </header>

            <div class="mt-8 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
                <div class="mb-6"><p class="text-xs font-black text-brand-700">مراكزك</p><h2 class="mt-1 text-2xl font-black text-ink-950">إدارة مراكز الصيانة</h2></div>
                <div class="grid gap-4">
                    @forelse ($serviceCenters as $center)
                        <article class="flex flex-col justify-between gap-5 rounded-2xl border border-slate-200 p-5 sm:flex-row sm:items-center">
                            <div><div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-black text-ink-950">{{ $center->name }}</h3><span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $center->status->label() }}</span></div><p class="mt-2 text-sm text-slate-500">{{ $center->address }}، {{ $center->city->name }}</p></div>
                            <div class="flex gap-4 text-sm"><a href="{{ route('owner.service-centers.edit', $center) }}" class="font-black text-brand-700 hover:text-brand-600">تعديل البيانات</a>@if ($center->status === App\Enums\ServiceCenterStatus::Published)<a href="{{ route('service-centers.show', $center->slug) }}" class="font-black text-ink-950 hover:text-brand-700">عرض الصفحة</a>@endif</div>
                        </article>
                    @empty
                        <div class="rounded-2xl bg-slate-50 p-8 text-center text-slate-500">لا توجد مراكز مرتبطة بحسابك حاليًا.</div>
                    @endforelse
                </div>
                <div class="mt-6">{{ $serviceCenters->links() }}</div>
            </div>
        </div>
    </section>
@endsection
