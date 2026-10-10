<x-mail::message>
# تحديث حالة الحجز

مرحبًا {{ $booking->customer->name }}،

تم تحديث حالة حجزك لدى **{{ $booking->serviceCenter->name }}** إلى **{{ $booking->status->label() }}**.

- **الخدمة:** {{ $booking->service->name }}
- **الموعد:** {{ $booking->scheduleLabel() }}

@if ($booking->status_note)
**ملاحظة المركز:** {{ $booking->status_note }}
@endif

<x-mail::button :url="route('bookings.index')">
عرض حجوزاتي
</x-mail::button>

شكرًا،<br>
{{ config('app.name') }}
</x-mail::message>
