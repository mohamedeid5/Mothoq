@php
    $selectedServiceIds = collect(old('service_ids', $serviceCenter->services->modelKeys()))
        ->map(fn ($id): int => (int) $id)
        ->all();
    $selectedCarBrandIds = collect(old('car_brand_ids', $serviceCenter->carBrands->modelKeys()))
        ->map(fn ($id): int => (int) $id)
        ->all();
@endphp

<input type="hidden" name="sync_services" value="1">
<input type="hidden" name="sync_car_brands" value="1">

<fieldset class="grid gap-3 border-t border-slate-100 pt-5 sm:col-span-2">
    <legend class="px-2 text-sm font-black text-ink-950">الخدمات التي يقدمها المركز</legend>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($services as $service)
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 hover:border-brand-300">
                <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, $selectedServiceIds, true)) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>{{ $service->name }}</span>
            </label>
        @empty
            <p class="text-sm text-slate-500">لا توجد خدمات نشطة للاختيار.</p>
        @endforelse
    </div>
    @error('service_ids')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    @error('service_ids.*')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
</fieldset>

<fieldset class="grid gap-3 border-t border-slate-100 pt-5 sm:col-span-2">
    <legend class="px-2 text-sm font-black text-ink-950">ماركات السيارات التي يخدمها المركز</legend>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($carBrands as $carBrand)
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 hover:border-brand-300">
                <input type="checkbox" name="car_brand_ids[]" value="{{ $carBrand->id }}" @checked(in_array($carBrand->id, $selectedCarBrandIds, true)) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>{{ $carBrand->name }}</span>
            </label>
        @empty
            <p class="text-sm text-slate-500">لا توجد ماركات نشطة للاختيار.</p>
        @endforelse
    </div>
    @error('car_brand_ids')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    @error('car_brand_ids.*')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
</fieldset>
