<x-mail::message>
# تم استلام طلب حجزك

مرحبًا {{ $details->customerName }}،

تم استلام طلب حجزك لدى **{{ $details->centerName }}**، وكانت حالته وقت الطلب **{{ $details->statusLabel }}**.

- **رقم الحجز:** {{ $details->bookingId }}
- **الخدمة:** {{ $details->serviceName }}
- **الموعد المطلوب:** {{ $details->scheduleLabel }}
- **رقم التواصل:** {{ $details->customerPhone }}

@if ($details->notes)
**ملاحظاتك:** {{ $details->notes }}
@endif

سيصلك تحديث جديد عند قبول المركز للحجز أو رفضه.

<x-mail::button :url="route('bookings.index')">
عرض حجوزاتي
</x-mail::button>

شكرًا،<br>
{{ config('app.name') }}
</x-mail::message>
