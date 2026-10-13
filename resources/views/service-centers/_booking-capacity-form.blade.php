<section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7" aria-labelledby="booking-capacity-heading">
    <h2 id="booking-capacity-heading" class="text-xl font-black text-ink-950">سعة الحجوزات</h2>
    <p id="booking-capacity-help" class="mt-2 text-sm leading-7 text-slate-600">عدد الحجوزات التي يستطيع المركز خدمتها في نفس الوقت، لجميع الخدمات وطوال الأسبوع. مثلًا: سعة 3 تسمح بثلاثة حجوزات متزامنة، وليست حدًا يوميًا.</p>

    @if ($serviceCenter->booking_capacity === null)
        <p role="status" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">السعة غير محددة. لن يستقبل المركز حجوزات جديدة حتى تحددها.</p>
    @else
        <p class="mt-4 text-sm font-bold text-brand-700">السعة الحالية: {{ $serviceCenter->booking_capacity }} حجوزات متزامنة</p>
    @endif

    <form method="POST" action="{{ route($routePrefix.'.service-centers.booking-capacity.update', $serviceCenter) }}" class="mt-5 grid gap-4 sm:max-w-lg">
        @csrf
        @method('PATCH')
        <label for="booking-capacity" class="text-sm font-bold text-ink-950">عدد الحجوزات المتزامنة</label>
        <input id="booking-capacity" name="booking_capacity" type="number" min="1" max="2147483647" step="1" required inputmode="numeric" value="{{ old('booking_capacity', $serviceCenter->booking_capacity) }}" aria-describedby="booking-capacity-help booking-capacity-error" aria-invalid="{{ $errors->has('booking_capacity') ? 'true' : 'false' }}" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
        <span id="booking-capacity-error" class="text-sm font-bold text-red-600">@error('booking_capacity'){{ $message }}@enderror</span>
        <p class="text-xs leading-6 text-slate-500">الحجوزات قيد المراجعة والمقبولة تحجز مكانًا. لا يمكن خفض السعة بما يتعارض مع حجوزات قائمة، والحجوزات القديمة ذات المدة غير المحددة تحتاج مراجعة الإدارة أولًا.</p>
        <button type="submit" class="rounded-2xl bg-brand-600 px-6 py-3 text-sm font-black text-white hover:bg-brand-500">حفظ سعة الحجوزات</button>
    </form>
</section>
