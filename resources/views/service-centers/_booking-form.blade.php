<section class="mt-4 rounded-3xl border border-[#dce6e2] bg-white p-6 surface-shadow">
    <p class="text-xs font-black text-brand-700">طلب حجز</p>
    <h2 class="mt-2 text-xl font-black text-ink-950">احجز موعدك مع المركز</h2>
    <p class="mt-2 text-sm leading-6 text-[#718489]">اختر الخدمة والموعد المناسب، والمركز هيراجع طلبك.</p>

    @if ($errors->hasAny(['service_id', 'customer_phone', 'scheduled_at', 'notes']))
        <div role="alert" class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('bookings.store', $serviceCenter->slug) }}" class="mt-5 grid gap-4">
        @csrf

        <label class="grid gap-2 text-sm font-black text-slate-700">الخدمة المطلوبة
            <select name="service_id" required class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-3 font-normal outline-none focus:border-brand-500">
                <option value="">اختر الخدمة</option>
                @foreach ($serviceCenter->services as $service)
                    <option value="{{ $service->id }}" @selected((int) old('service_id') === $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </label>

        <label class="grid gap-2 text-sm font-black text-slate-700">رقم الموبايل
            <input name="customer_phone" value="{{ old('customer_phone') }}" inputmode="tel" dir="ltr" placeholder="01xxxxxxxxx" required class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-right font-normal outline-none focus:border-brand-500">
        </label>

        <label class="grid gap-2 text-sm font-black text-slate-700">الموعد المطلوب
            <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" min="{{ now()->addHour()->format('Y-m-d\TH:i') }}" required class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 font-normal outline-none focus:border-brand-500">
        </label>

        <label class="grid gap-2 text-sm font-black text-slate-700">ملاحظات <span class="font-medium text-slate-400">(اختياري)</span>
            <textarea name="notes" rows="3" maxlength="2000" placeholder="اكتب تفاصيل المشكلة أو طلبك..." class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal leading-7 outline-none focus:border-brand-500">{{ old('notes') }}</textarea>
        </label>

        <button type="submit" class="h-12 rounded-xl bg-brand-600 px-5 text-sm font-black text-white hover:bg-brand-500">إرسال طلب الحجز</button>
    </form>
</section>
