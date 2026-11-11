<x-mail::message>
# حجز جديد

مرحبًا {{ $details->ownerName }}،

وصل طلب حجز جديد إلى **{{ $details->centerName }}**.

- **العميل:** {{ $details->customerName }}
- **رقم الحجز:** {{ $details->bookingId }}
- **الخدمة:** {{ $details->serviceName }}
- **الموعد المطلوب:** {{ $details->scheduleLabel }}
- **رقم التواصل:** {{ $details->customerPhone }}

@if ($details->notes)
**ملاحظات العميل:** {{ $details->notes }}
@endif

<x-mail::button :url="route('owner.bookings.index')">
عرض الحجوزات
</x-mail::button>

شكرًا،<br>
{{ config('app.name') }}
</x-mail::message>
