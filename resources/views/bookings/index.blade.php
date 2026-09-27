@extends('layouts.app')

@section('title', 'حجوزاتي | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <header class="rounded-[2rem] bg-ink-950 p-7 text-white sm:p-9"><p class="text-sm font-bold text-brand-300">حساب العميل</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">حجوزاتي</h1><p class="mt-3 text-sm text-white/60">تابع طلبات الحجز وحالة رد مراكز الصيانة.</p></header>

            @if (session('success'))
                <div role="status" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
            @endif

            @if ($errors->has('booking'))
                <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first('booking') }}</div>
            @endif

            <div class="mt-6 grid gap-4">
                @forelse ($bookings as $booking)
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-3"><h2 class="text-lg font-black text-ink-950">{{ $booking->serviceCenter->name }}</h2><span @class(['rounded-full px-3 py-1 text-xs font-black', 'bg-amber-50 text-amber-700' => $booking->status === App\Enums\BookingStatus::Pending, 'bg-blue-50 text-blue-700' => $booking->status === App\Enums\BookingStatus::Accepted, 'bg-red-50 text-red-700' => $booking->status === App\Enums\BookingStatus::Rejected, 'bg-slate-100 text-slate-600' => $booking->status === App\Enums\BookingStatus::Cancelled, 'bg-emerald-50 text-emerald-700' => $booking->status === App\Enums\BookingStatus::Completed])>{{ $booking->status->label() }}</span></div>
                                <p class="mt-2 text-sm text-slate-500">{{ $booking->service->name }} · {{ $booking->scheduled_at->format('Y-m-d H:i') }}</p>
                            </div>
                            @if (! $booking->serviceCenter->trashed() && $booking->serviceCenter->status === App\Enums\ServiceCenterStatus::Published)
                                <a href="{{ route('service-centers.show', $booking->serviceCenter->slug) }}" class="text-sm font-black text-brand-700">صفحة المركز</a>
                            @endif
                        </div>

                        <div class="mt-4 grid gap-2 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 sm:grid-cols-2"><p><strong class="text-slate-800">الهاتف:</strong> <span dir="ltr">{{ $booking->customer_phone }}</span></p><p><strong class="text-slate-800">تاريخ الطلب:</strong> {{ $booking->created_at->format('Y-m-d') }}</p>@if ($booking->notes)<p class="sm:col-span-2"><strong class="text-slate-800">ملاحظاتك:</strong> {{ $booking->notes }}</p>@endif @if ($booking->status_note)<p class="sm:col-span-2"><strong class="text-slate-800">رد المركز:</strong> {{ $booking->status_note }}</p>@endif</div>

                        @if ($booking->status === App\Enums\BookingStatus::Pending)
                            <form method="POST" action="{{ route('bookings.destroy', $booking) }}" class="mt-4" onsubmit="return confirm('هل تريد إلغاء طلب الحجز؟')">@csrf @method('DELETE')<button class="text-sm font-black text-red-600 hover:text-red-500">إلغاء الحجز</button></form>
                        @endif
                    </article>
                @empty
                    <div class="rounded-3xl border border-slate-200 bg-white px-5 py-14 text-center"><p class="text-slate-500">لم ترسل أي طلبات حجز حتى الآن.</p><a href="{{ route('home') }}#centers" class="mt-4 inline-flex rounded-xl bg-brand-600 px-5 py-3 text-sm font-black text-white">استعرض مراكز الصيانة</a></div>
                @endforelse
            </div>

            @if ($bookings->hasPages())<div class="mt-6">{{ $bookings->links() }}</div>@endif
        </div>
    </section>
@endsection
