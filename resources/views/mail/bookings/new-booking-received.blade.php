<x-mail::message>
# حجز جديد

مرحبًا {{ $booking->serviceCenter->owner->name }}،

وصل طلب حجز جديد إلى **{{ $booking->serviceCenter->name }}**.

- **العميل:** {{ $booking->customer->name }}
- **الخدمة:** {{ $booking->service->name }}
- **الموعد المطلوب:** {{ $booking->scheduled_at->format('Y-m-d H:i') }}
- **رقم التواصل:** {{ $booking->customer_phone }}

@if ($booking->notes)
**ملاحظات العميل:** {{ $booking->notes }}
@endif

<x-mail::button :url="route('owner.bookings.index')">
عرض الحجوزات
</x-mail::button>

شكرًا،<br>
{{ config('app.name') }}
</x-mail::message>
