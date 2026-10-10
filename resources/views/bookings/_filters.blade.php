<form method="GET" action="{{ $action }}" class="mt-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-[1fr_13rem_13rem_auto]">
    <label class="grid gap-2 text-sm font-bold text-slate-700">بحث
        <input name="search" value="{{ request('search') }}" placeholder="العميل أو الهاتف أو الخدمة" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500">
    </label>
    <label class="grid gap-2 text-sm font-bold text-slate-700">الحالة
        <select name="status" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-3"><option value="">كل الحالات</option>@foreach (App\Enums\BookingStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
    </label>
    <label class="grid gap-2 text-sm font-bold text-slate-700">تاريخ الحجز بتوقيت مصر
        <input type="date" name="date" value="{{ request('date') }}" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-3">
    </label>
    <div class="flex items-end gap-2"><button class="h-12 flex-1 rounded-xl bg-brand-600 px-5 text-sm font-black text-white">تطبيق</button><a href="{{ $action }}" class="grid h-12 place-items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600">مسح</a></div>
</form>
