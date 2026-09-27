@extends('layouts.app')

@section('title', 'طلبات الحجز | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-8 sm:px-6 lg:px-8"><div class="mx-auto max-w-7xl"><nav class="flex items-center gap-2 text-sm font-bold text-slate-500"><a href="{{ route('owner.dashboard') }}" class="hover:text-brand-700">لوحة صاحب المركز</a><span>/</span><span class="text-ink-950">الحجوزات</span></nav><header class="mt-6 rounded-[2rem] bg-ink-950 p-7 text-white sm:p-9"><p class="text-sm font-bold text-brand-300">إدارة المواعيد</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">طلبات الحجز</h1><p class="mt-3 text-sm text-white/60">راجع طلبات مراكزك واقبلها أو ارفضها ثم سجّل اكتمال الخدمة.</p></header>

        @if (session('success'))<div role="status" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
        @if ($errors->any())<div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

        @include('bookings._filters', ['action' => route('owner.bookings.index')])
        @include('bookings._management-list', ['bookings' => $bookings, 'updateRouteName' => 'owner.bookings.update', 'showCenter' => true])
    </div></section>
@endsection
