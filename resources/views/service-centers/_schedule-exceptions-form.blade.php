<section id="schedule-exceptions" class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
    <h2 class="text-xl font-black text-ink-950">المواعيد الاستثنائية</h2>
    <p class="mt-2 text-sm leading-6 text-slate-600">حدد إجازة أو مواعيد مختلفة ليوم معين بتوقيت مصر. الموعد الاستثنائي يحل محل المواعيد الأسبوعية لهذا اليوم فقط. حفظ تاريخ موجود يعدّل موعده.</p>

    @error('schedule')
        <p role="alert" class="mt-4 text-sm font-bold text-red-700">{{ $message }}</p>
    @enderror

    <form method="POST" action="{{ route($routePrefix.'.service-centers.schedule-exceptions.store', $serviceCenter) }}" data-schedule-exception-form class="mt-5 grid gap-4 sm:grid-cols-2">
        @csrf
        @method('PUT')
        <label class="grid gap-2 text-sm font-bold text-ink-950">التاريخ
            <input type="date" name="date" value="{{ old('date') }}" required class="h-12 rounded-xl border border-slate-200 px-4">
            @error('date')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="grid gap-2 text-sm font-bold text-ink-950">حالة اليوم
            <select name="is_closed" class="h-12 rounded-xl border border-slate-200 px-4">
                <option value="0" @selected((string) old('is_closed', '0') === '0')>مواعيد خاصة</option>
                <option value="1" @selected((string) old('is_closed', '0') === '1')>مغلق طوال اليوم</option>
            </select>
            @error('is_closed')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="grid gap-2 text-sm font-bold text-ink-950">وقت الفتح
            <input type="time" name="opens_at" value="{{ old('opens_at') }}" class="h-12 rounded-xl border border-slate-200 px-4 disabled:bg-slate-100">
            @error('opens_at')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <label class="grid gap-2 text-sm font-bold text-ink-950">وقت الإغلاق
            <input type="time" name="closes_at" value="{{ old('closes_at') }}" class="h-12 rounded-xl border border-slate-200 px-4 disabled:bg-slate-100">
            @error('closes_at')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
        </label>
        <p class="text-xs text-slate-500 sm:col-span-2">عند إغلاق اليوم اترك وقت الفتح والإغلاق فارغين. لا يمكن حفظ تغيير يتعارض مع حجز نشط.</p>
        <button type="submit" class="rounded-2xl bg-brand-600 px-6 py-3 text-sm font-black text-white hover:bg-brand-500 sm:col-span-2">حفظ الموعد الاستثنائي</button>
    </form>

    <div class="mt-6 grid gap-3">
        @forelse ($scheduleExceptions as $exception)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4">
                <div>
                    <p class="font-bold text-ink-950"><time datetime="{{ $exception->date->toDateString() }}">{{ $exception->date->toDateString() }}</time></p>
                    <p class="mt-1 text-sm text-slate-600">{{ $exception->is_closed ? 'مغلق طوال اليوم' : 'من '.substr($exception->opens_at, 0, 5).' إلى '.substr($exception->closes_at, 0, 5) }}</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" data-edit-schedule-exception data-date="{{ $exception->date->toDateString() }}" data-closed="{{ (int) $exception->is_closed }}" data-opens="{{ substr($exception->opens_at ?? '', 0, 5) }}" data-closes="{{ substr($exception->closes_at ?? '', 0, 5) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold text-brand-700">تعديل</button>
                    <form method="POST" action="{{ route($routePrefix.'.service-centers.schedule-exceptions.destroy', [$serviceCenter, $exception]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-bold text-red-700" aria-label="حذف الموعد الاستثنائي {{ $exception->date->toDateString() }}">حذف</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">لا توجد مواعيد استثنائية لهذا المركز بعد.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $scheduleExceptions->withQueryString()->fragment('schedule-exceptions')->links() }}</div>
</section>
