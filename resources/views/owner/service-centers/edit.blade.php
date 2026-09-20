@extends('layouts.app')

@section('title', 'تعديل '.$serviceCenter->name.' | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <nav class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500" aria-label="مسار الصفحة"><a href="{{ route('owner.dashboard') }}" class="hover:text-brand-700">لوحة التحكم</a><span>/</span><span class="text-ink-950">تعديل بيانات المركز</span></nav>

            <header class="mt-6 rounded-[2rem] bg-ink-950 p-6 text-white sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="text-sm font-bold text-brand-300">تعديل مركز الصيانة</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">{{ $serviceCenter->name }}</h1><p class="mt-3 text-sm text-white/55">حدّث البيانات التي تظهر للعملاء على صفحة المركز.</p></div>
                    <div class="flex flex-wrap gap-2 text-xs font-black"><span class="rounded-full bg-white/10 px-4 py-2">{{ $serviceCenter->status->label() }}</span><span class="rounded-full px-4 py-2 {{ $serviceCenter->verified_at ? 'bg-brand-400/15 text-brand-200' : 'bg-white/10 text-white/60' }}">{{ $serviceCenter->verified_at ? '✓ مركز موثق' : 'بانتظار التوثيق' }}</span></div>
                </div>
            </header>

            @if (session('success'))
                <div role="status" class="mt-6 flex items-center justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800"><span>✓ {{ session('success') }}</span>@if ($serviceCenter->status === App\Enums\ServiceCenterStatus::Published)<a href="{{ route('service-centers.show', $serviceCenter->slug) }}" class="shrink-0 underline underline-offset-4">عرض الصفحة</a>@endif</div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">راجع الحقول الموضحة ثم حاول مرة أخرى.</div>
            @endif

            <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <form method="POST" action="{{ route('owner.service-centers.update', $serviceCenter) }}" class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-bold text-ink-950">اسم المركز
                            <input name="name" value="{{ old('name', $serviceCenter->name) }}" required maxlength="255" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
                            @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-ink-950">رقم الهاتف
                            <input name="phone" value="{{ old('phone', $serviceCenter->phone) }}" required maxlength="30" dir="ltr" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-right outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
                            @error('phone')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-ink-950">المحافظة
                            <select name="governorate" data-governorate-select class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4">
                                @foreach ($governorates as $governorate)
                                    <option value="{{ $governorate->slug }}" @selected(old('governorate', $serviceCenter->city->governorate->slug) === $governorate->slug)>{{ $governorate->name }}</option>
                                @endforeach
                            </select>
                            @error('governorate')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-ink-950">المدينة
                            <select name="city_id" data-city-select data-selected="{{ old('city_id', $serviceCenter->city_id) }}" required class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4">
                                <option value="">اختر المدينة</option>
                                @foreach ($governorates as $governorate)
                                    @foreach ($governorate->cities as $city)
                                        <option value="{{ $city->id }}" data-governorate="{{ $governorate->slug }}" @selected((int) old('city_id', $serviceCenter->city_id) === $city->id)>{{ $city->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                            @error('city_id')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-ink-950 sm:col-span-2">العنوان
                            <input name="address" value="{{ old('address', $serviceCenter->address) }}" required maxlength="500" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
                            @error('address')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-ink-950 sm:col-span-2">وصف المركز
                            <textarea name="description" rows="5" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">{{ old('description', $serviceCenter->description) }}</textarea>
                            @error('description')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-ink-950">واتساب
                            <input name="whatsapp" value="{{ old('whatsapp', $serviceCenter->whatsapp) }}" maxlength="30" dir="ltr" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-right outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
                            @error('whatsapp')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <div></div>
                        <label class="grid gap-2 text-sm font-bold text-ink-950">خط العرض
                            <input name="latitude" value="{{ old('latitude', $serviceCenter->latitude) }}" type="number" step="any" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
                            @error('latitude')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="grid gap-2 text-sm font-bold text-ink-950">خط الطول
                            <input name="longitude" value="{{ old('longitude', $serviceCenter->longitude) }}" type="number" step="any" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
                            @error('longitude')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        @include('service-centers._catalog-fields', [
                            'serviceCenter' => $serviceCenter,
                            'services' => $services,
                            'carBrands' => $carBrands,
                        ])
                    </div>

                    <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end"><a href="{{ route('owner.dashboard') }}" class="rounded-2xl border border-slate-200 px-6 py-3 text-center text-sm font-black text-slate-600 hover:bg-slate-50">إلغاء</a><button type="submit" class="rounded-2xl bg-brand-600 px-7 py-3 text-sm font-black text-white hover:bg-brand-500">حفظ التعديلات</button></div>
                </form>

                <aside class="order-first h-fit rounded-3xl border border-slate-200 bg-white p-5 lg:order-last lg:sticky lg:top-24"><p class="text-xs font-black text-brand-700">قبل الحفظ</p><h2 class="mt-1 text-lg font-black text-ink-950">راجع البيانات بعناية</h2><ul class="mt-4 grid gap-3 text-sm leading-6 text-slate-600"><li>✓ الاسم والعنوان يظهران في نتائج البحث.</li><li>✓ الخدمات والماركات تحدد ظهور المركز في نتائج الفلترة.</li><li>✓ حالة النشر والتوثيق لا يمكن تغييرهما من هنا.</li></ul></aside>
            </div>

            @include('service-centers._opening-hours-form', [
                'serviceCenter' => $serviceCenter,
                'action' => route('owner.service-centers.opening-hours.update', $serviceCenter),
            ])

            @include('service-centers._images-form', [
                'serviceCenter' => $serviceCenter,
                'routePrefix' => 'owner',
            ])
        </div>
    </section>
@endsection
