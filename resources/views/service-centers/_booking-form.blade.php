<section class="mt-4 rounded-3xl border border-[#dce6e2] bg-white p-6 surface-shadow">
    <p class="text-xs font-black text-brand-700">طلب حجز</p>
    <h2 class="mt-2 text-xl font-black text-ink-950">احجز موعدك مع المركز</h2>
    <p class="mt-2 text-sm leading-6 text-[#718489]">اختر الخدمة والموعد المناسب، والمركز هيراجع طلبك.</p>

    <details class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4" open>
        <summary class="cursor-pointer text-sm font-bold text-ink-950">مواعيد الحجز بتوقيت {{ $serviceCenter->timezone }}</summary>
        <dl class="mt-3 grid gap-2 text-sm">
            @foreach (\App\Enums\DayOfWeek::cases() as $day)
                @php($hours = $serviceCenter->openingHours->firstWhere('day_of_week', $day))
                <div class="flex items-center justify-between gap-4">
                    <dt>{{ $day->label() }}</dt>
                    <dd>
                        @if ($hours === null || (! $hours->is_closed && ($hours->opens_at === null || $hours->closes_at === null)))
                            غير محدد — غير متاح للحجز
                        @elseif ($hours->is_closed)
                            مغلق
                        @else
                            <span dir="ltr">{{ substr($hours->opens_at, 0, 5) }} – {{ substr($hours->closes_at, 0, 5) }}</span>
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-3 text-xs leading-6 text-slate-600">يجب أن تنتهي الخدمة قبل الإغلاق أو عنده. قد تختلف المواعيد في التواريخ الاستثنائية، ويؤكد الخادم صلاحية الموعد.</p>
    </details>

    @if ($errors->hasAny(['service_id', 'customer_phone', 'scheduled_at', 'notes', 'schedule']))
        <div role="alert" class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('bookings.store', $serviceCenter->slug) }}" class="mt-5 grid gap-4">
        @csrf

        <label class="grid gap-2 text-sm font-black text-slate-700">الخدمة المطلوبة
            <select name="service_id" required class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-3 font-normal outline-none focus:border-brand-500">
                <option value="">اختر الخدمة</option>
                @foreach ($serviceCenter->services as $service)
                    <option value="{{ $service->id }}" @disabled($service->pivot->duration_minutes === null) @selected((int) old('service_id') === $service->id)>{{ $service->name }} — {{ $service->pivot->duration_minutes === null ? 'المدة غير محددة — غير متاح للحجز' : $service->pivot->duration_minutes.' دقيقة' }}</option>
                @endforeach
            </select>
        </label>

        <label class="grid gap-2 text-sm font-black text-slate-700">رقم الموبايل
            <input name="customer_phone" value="{{ old('customer_phone') }}" inputmode="tel" dir="ltr" placeholder="01xxxxxxxxx" required class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-right font-normal outline-none focus:border-brand-500">
        </label>

        <label class="grid gap-2 text-sm font-black text-slate-700">الموعد المطلوب
            <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" min="{{ now($serviceCenter->timezone)->addMinute()->startOfMinute()->format('Y-m-d\TH:i') }}" required class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 font-normal outline-none focus:border-brand-500">
        </label>

        <label class="grid gap-2 text-sm font-black text-slate-700">ملاحظات <span class="font-medium text-slate-400">(اختياري)</span>
            <textarea name="notes" rows="3" maxlength="2000" placeholder="اكتب تفاصيل المشكلة أو طلبك..." class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal leading-7 outline-none focus:border-brand-500">{{ old('notes') }}</textarea>
        </label>

        <button type="submit" class="h-12 rounded-xl bg-brand-600 px-5 text-sm font-black text-white hover:bg-brand-500">إرسال طلب الحجز</button>
    </form>
</section>
