@php
    $openingHoursByDay = $serviceCenter->openingHours->keyBy(
        fn ($openingHour) => $openingHour->day_of_week->value,
    );
@endphp

<section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
    <div class="flex flex-col gap-2 border-b border-slate-100 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-black text-brand-700">الجدول الأسبوعي</p>
            <h2 class="mt-1 text-xl font-black text-ink-950">مواعيد العمل</h2>
        </div>
        <p class="text-sm text-slate-500">حدد الأيام المغلقة، وأوقات الفتح والإغلاق لباقي الأيام.</p>
    </div>

    <form method="POST" action="{{ $action }}" class="mt-5">
        @csrf
        @method('PATCH')

        <div class="grid gap-3">
            @foreach (App\Enums\DayOfWeek::cases() as $day)
                @php
                    $index = $loop->index;
                    $openingHour = $openingHoursByDay->get($day->value);
                    $isClosed = (bool) old(
                        "opening_hours.{$index}.is_closed",
                        $openingHour?->is_closed ?? $day === App\Enums\DayOfWeek::Friday,
                    );
                    $opensAt = old(
                        "opening_hours.{$index}.opens_at",
                        $openingHour?->opens_at ? substr($openingHour->opens_at, 0, 5) : '09:00',
                    );
                    $closesAt = old(
                        "opening_hours.{$index}.closes_at",
                        $openingHour?->closes_at ? substr($openingHour->closes_at, 0, 5) : '18:00',
                    );
                @endphp

                <div class="grid items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-[8rem_1fr_1fr_7rem]">
                    <input type="hidden" name="opening_hours[{{ $index }}][day]" value="{{ $day->value }}">
                    <strong class="text-sm text-ink-950">{{ $day->label() }}</strong>
                    <label class="grid gap-1 text-xs font-bold text-slate-600">يفتح الساعة
                        <input type="time" name="opening_hours[{{ $index }}][opens_at]" value="{{ $opensAt }}" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-brand-500">
                        @error("opening_hours.{$index}.opens_at")<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-1 text-xs font-bold text-slate-600">يغلق الساعة
                        <input type="time" name="opening_hours[{{ $index }}][closes_at]" value="{{ $closesAt }}" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-brand-500">
                        @error("opening_hours.{$index}.closes_at")<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="hidden" name="opening_hours[{{ $index }}][is_closed]" value="0">
                        <input type="checkbox" name="opening_hours[{{ $index }}][is_closed]" value="1" @checked($isClosed) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        مغلق
                    </label>
                    @error("opening_hours.{$index}.day")<span class="text-xs text-red-600 sm:col-span-4">{{ $message }}</span>@enderror
                </div>
            @endforeach
        </div>

        @error('opening_hours')<p class="mt-3 text-sm font-bold text-red-600">{{ $message }}</p>@enderror

        <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
            <button type="submit" class="rounded-2xl bg-ink-950 px-7 py-3 text-sm font-black text-white hover:bg-brand-700">حفظ مواعيد العمل</button>
        </div>
    </form>
</section>
