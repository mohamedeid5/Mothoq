@extends('layouts.app')

@section('title', 'إدارة مراكز الصيانة | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <nav class="flex items-center gap-2 text-sm font-bold text-slate-500"><a href="{{ route('admin.dashboard') }}" class="hover:text-brand-700">لوحة الإدارة</a><span>/</span><span class="text-ink-950">مراكز الصيانة</span></nav>
            <header class="mt-6 rounded-[2rem] bg-ink-950 p-7 text-white sm:p-9"><p class="text-sm font-bold text-brand-300">إدارة المحتوى</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">مراكز الصيانة</h1><p class="mt-3 text-sm text-white/60">راجع بيانات المراكز وتحكم في النشر والتوثيق من مكان واحد.</p></header>

            <form method="GET" action="{{ route('admin.service-centers.index') }}" class="mt-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-[1fr_13rem_13rem_auto]">
                <label class="grid gap-2 text-sm font-bold text-slate-700">بحث
                    <input name="search" value="{{ request('search') }}" placeholder="اسم المركز أو المالك أو الهاتف" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500">
                </label>
                <label class="grid gap-2 text-sm font-bold text-slate-700">حالة النشر
                    <select name="status" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-3"><option value="">كل الحالات</option>@foreach (App\Enums\ServiceCenterStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
                </label>
                <label class="grid gap-2 text-sm font-bold text-slate-700">التوثيق
                    <select name="verified" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-3"><option value="">الكل</option><option value="1" @selected(request('verified') === '1')>موثق</option><option value="0" @selected(request('verified') === '0')>غير موثق</option></select>
                </label>
                <div class="flex items-end gap-2"><button class="h-12 flex-1 rounded-xl bg-brand-600 px-5 text-sm font-black text-white hover:bg-brand-500">تطبيق</button><a href="{{ route('admin.service-centers.index') }}" class="grid h-12 place-items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600">مسح</a></div>
            </form>

            @if ($errors->any())<div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif

            <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h2 class="font-black text-ink-950">النتائج</h2><span class="text-sm font-bold text-slate-500">{{ $serviceCenters->total() }} مركز</span></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="px-5 py-4">المركز</th><th class="px-5 py-4">المالك</th><th class="px-5 py-4">الموقع</th><th class="px-5 py-4">الحالة</th><th class="px-5 py-4">التوثيق</th><th class="px-5 py-4"></th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($serviceCenters as $center)
                                <tr class="hover:bg-slate-50/70"><td class="px-5 py-4"><strong class="block text-ink-950">{{ $center->name }}</strong><span dir="ltr" class="mt-1 block text-xs text-slate-400">{{ $center->phone }}</span></td><td class="px-5 py-4"><span class="block font-bold text-slate-700">{{ $center->owner?->name ?: 'بدون مالك' }}</span><span class="text-xs text-slate-400">{{ $center->owner?->email }}</span></td><td class="px-5 py-4 text-slate-600">{{ $center->city->name }}، {{ $center->city->governorate->name }}</td><td class="px-5 py-4"><span @class(['rounded-full px-3 py-1 text-xs font-black', 'bg-amber-50 text-amber-700' => $center->status === App\Enums\ServiceCenterStatus::Draft, 'bg-emerald-50 text-emerald-700' => $center->status === App\Enums\ServiceCenterStatus::Published, 'bg-red-50 text-red-700' => $center->status === App\Enums\ServiceCenterStatus::Suspended])>{{ $center->status->label() }}</span></td><td class="px-5 py-4"><span class="font-bold {{ $center->verified_at ? 'text-brand-700' : 'text-slate-400' }}">{{ $center->verified_at ? 'موثق' : 'غير موثق' }}</span></td><td class="px-5 py-4"><a href="{{ route('admin.service-centers.show', $center) }}" class="font-black text-brand-700 hover:text-brand-600">مراجعة</a></td></tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">لا توجد مراكز مطابقة للفلاتر.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($serviceCenters->hasPages())<div class="border-t border-slate-100 p-5">{{ $serviceCenters->links() }}</div>@endif
            </div>
        </div>
    </section>
@endsection
