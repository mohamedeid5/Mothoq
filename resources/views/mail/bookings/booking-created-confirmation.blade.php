<x-mail::message>
# تم استلام طلب حجزك

مرحبًا {{ $booking->customer->name }}،

تم استلام طلب حجزك لدى **{{ $booking->serviceCenter->name }}**، وحالته الآن **{{ $booking->status->label() }}**.

- **الخدمة:** {{ $booking->service->name }}
- **الموعد المطلوب:** {{ $booking->scheduled_at->format('Y-m-d H:i') }}
- **رقم التواصل:** {{ $booking->customer_phone }}

@if ($booking->notes)
**ملاحظاتك:** {{ $booking->notes }}
@endif

سيصلك تحديث جديد عند قبول المركز للحجز أو رفضه.

<x-mail::button :url="route('bookings.index')">
عرض حجوزاتي
</x-mail::button>

شكرًا،<br>
{{ config('app.name') }}
</x-mail::message>
