@if ($booking->status === \App\Enums\BookingStatus::Pending)
    @if ($booking->duration_minutes !== null && $booking->duration_minutes > 0 && $booking->end_at !== null)
        @php($hasRescheduleErrors = (int) old('reschedule_booking_id') === $booking->id && $errors->hasAny(['scheduled_at', 'booking_capacity', 'booking']))
        <details class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4" @if ($hasRescheduleErrors) open @endif>
            <summary class="cursor-pointer text-sm font-black text-brand-700">تغيير موعد الحجز</summary>
            <p class="mt-3 text-xs leading-6 text-slate-600">اختر موعدًا جديدًا بتوقيت مصر. يجب أن تنتهي الخدمة خلال مواعيد عمل المركز، وقد تختلف المواعيد في أيام العطلات.</p>

            @if ($hasRescheduleErrors)
                <p role="alert" class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route($rescheduleRouteName, $booking) }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="reschedule_booking_id" value="{{ $booking->id }}">
                <label class="grid min-w-0 flex-1 gap-2 text-sm font-bold text-slate-700">الموعد الجديد
                    <input type="datetime-local" name="scheduled_at" value="{{ $hasRescheduleErrors ? old('scheduled_at') : $booking->scheduled_at->format('Y-m-d\TH:i') }}" min="{{ now()->addMinute()->startOfMinute()->format('Y-m-d\TH:i') }}" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 font-normal outline-none focus:border-brand-500">
                </label>
                <button type="submit" class="h-11 rounded-xl bg-brand-600 px-5 text-sm font-black text-white hover:bg-brand-500">حفظ الموعد الجديد</button>
            </form>
        </details>
    @else
        <p class="mt-4 text-xs font-bold text-amber-700">لا يمكن تغيير الموعد قبل مراجعة مدة هذا الحجز.</p>
    @endif
@endif
