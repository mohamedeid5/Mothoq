@extends('layouts.app')

@section('title', 'تعديل '.$serviceCenter->name.' | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            <nav class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-500" aria-label="مسار الصفحة"><a href="{{ route('admin.dashboard') }}" class="hover:text-brand-700">لوحة الإدارة</a><span>/</span><a href="{{ route('admin.service-centers.show', $serviceCenter) }}" class="hover:text-brand-700">{{ $serviceCenter->name }}</a><span>/</span><span class="text-ink-950">تعديل البيانات</span></nav>

            <header class="mt-6 rounded-[2rem] bg-ink-950 p-6 text-white sm:p-8">
                <p class="text-sm font-bold text-brand-300">إدارة مركز الصيانة</p>
                <h1 class="mt-2 text-3xl font-black sm:text-4xl">تعديل {{ $serviceCenter->name }}</h1>
                <p class="mt-3 text-sm text-white/55">يمكنك تعديل البيانات الأساسية للمركز. النشر والتوثيق لهما أدوات مستقلة في صفحة المراجعة.</p>
            </header>

            @if (session('success'))
                <div role="status" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">✓ {{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700">راجع الحقول الموضحة ثم حاول مرة أخرى.</div>
            @endif

            <form method="POST" action="{{ route('admin.service-centers.update', $serviceCenter) }}" class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
                @csrf
                @method('PATCH')

                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-bold text-ink-950">اسم المركز
                        <input name="name" value="{{ old('name', $serviceCenter->name) }}" required maxlength="255" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500">
                        @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-bold text-ink-950">رقم الهاتف
                        <input name="phone" value="{{ old('phone', $serviceCenter->phone) }}" required maxlength="30" dir="ltr" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-right outline-none focus:border-brand-500">
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
                        <input name="address" value="{{ old('address', $serviceCenter->address) }}" required maxlength="500" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500">
                        @error('address')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-bold text-ink-950 sm:col-span-2">وصف المركز
                        <textarea name="description" rows="5" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-brand-500">{{ old('description', $serviceCenter->description) }}</textarea>
                        @error('description')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-bold text-ink-950">واتساب
                        <input name="whatsapp" value="{{ old('whatsapp', $serviceCenter->whatsapp) }}" maxlength="30" dir="ltr" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 text-right outline-none focus:border-brand-500">
                        @error('whatsapp')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <div></div>
                    <label class="grid gap-2 text-sm font-bold text-ink-950">خط العرض
                        <input name="latitude" value="{{ old('latitude', $serviceCenter->latitude) }}" type="number" step="any" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500">
                        @error('latitude')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-bold text-ink-950">خط الطول
                        <input name="longitude" value="{{ old('longitude', $serviceCenter->longitude) }}" type="number" step="any" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500">
                        @error('longitude')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>

                <div class="mt-7 flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end"><a href="{{ route('admin.service-centers.show', $serviceCenter) }}" class="rounded-2xl border border-slate-200 px-6 py-3 text-center text-sm font-black text-slate-600 hover:bg-slate-50">إلغاء</a><button type="submit" class="rounded-2xl bg-brand-600 px-7 py-3 text-sm font-black text-white hover:bg-brand-500">حفظ التعديلات</button></div>
            </form>
        </div>
    </section>
@endsection
