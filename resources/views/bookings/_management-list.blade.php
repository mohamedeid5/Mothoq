<div class="mt-6 grid gap-4">
    @forelse ($bookings as $booking)
        <article class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div><div class="flex flex-wrap items-center gap-3"><strong class="text-ink-950">{{ $booking->customer->name }}</strong><span @class(['rounded-full px-3 py-1 text-xs font-black', 'bg-amber-50 text-amber-700' => $booking->status === App\Enums\BookingStatus::Pending, 'bg-blue-50 text-blue-700' => $booking->status === App\Enums\BookingStatus::Accepted, 'bg-red-50 text-red-700' => $booking->status === App\Enums\BookingStatus::Rejected, 'bg-slate-100 text-slate-600' => $booking->status === App\Enums\BookingStatus::Cancelled, 'bg-emerald-50 text-emerald-700' => $booking->status === App\Enums\BookingStatus::Completed])>{{ $booking->status->label() }}</span></div><p class="mt-2 text-sm text-slate-500">{{ $booking->service->name }} · {{ $booking->scheduled_at->format('Y-m-d H:i') }}@if ($showCenter) · {{ $booking->serviceCenter->name }}@endif</p></div>
                <div class="text-left text-sm"><a href="tel:{{ $booking->customer_phone }}" dir="ltr" class="font-black text-brand-700">{{ $booking->customer_phone }}</a><span class="mt-1 block text-xs text-slate-400">{{ $booking->customer->email }}</span></div>
            </div>

            <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-7 text-slate-700"><p><strong>ملاحظات العميل:</strong> {{ $booking->notes ?: 'لا توجد ملاحظات.' }}</p>@if ($booking->status_note)<p class="mt-2"><strong>ملاحظة الحالة:</strong> {{ $booking->status_note }}</p>@endif</div>

            @if (in_array($booking->status, [App\Enums\BookingStatus::Pending, App\Enums\BookingStatus::Accepted], true))
                <form method="POST" action="{{ route($updateRouteName, $booking) }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">@csrf @method('PATCH')<input name="status_note" maxlength="1000" placeholder="ملاحظة للعميل (اختياري)" class="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm outline-none focus:border-brand-500"><div class="flex flex-wrap gap-2">@if ($booking->status === App\Enums\BookingStatus::Pending)<button name="status" value="{{ App\Enums\BookingStatus::Accepted->value }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-black text-white">قبول</button><button name="status" value="{{ App\Enums\BookingStatus::Rejected->value }}" class="rounded-xl bg-red-50 px-4 py-2 text-sm font-black text-red-700">رفض</button>@else<button name="status" value="{{ App\Enums\BookingStatus::Completed->value }}" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-black text-white">تمت الخدمة</button>@endif</div></form>
            @endif
        </article>
    @empty
        <div class="rounded-3xl border border-slate-200 bg-white px-5 py-14 text-center text-slate-500">لا توجد حجوزات مطابقة للفلاتر.</div>
    @endforelse
</div>

@if ($bookings->hasPages())<div class="mt-6">{{ $bookings->links() }}</div>@endif
