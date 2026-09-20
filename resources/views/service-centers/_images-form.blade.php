<section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-black text-brand-700">معرض المركز</p>
            <h2 class="mt-1 text-xl font-black text-ink-950">صور مركز الصيانة</h2>
            <p class="mt-2 text-sm text-slate-500">ارفع حتى 10 صور بصيغة JPG أو PNG أو WebP، وبحجم أقصى 5MB للصورة.</p>
        </div>
        <span class="text-sm font-bold text-slate-500">{{ $serviceCenter->images->count() }} / {{ App\Models\CenterImage::MAX_PER_SERVICE_CENTER }}</span>
    </div>

    @if ($serviceCenter->images->count() < App\Models\CenterImage::MAX_PER_SERVICE_CENTER)
        <form method="POST" action="{{ route($routePrefix.'.service-centers.images.store', $serviceCenter) }}" enctype="multipart/form-data" class="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-4 sm:grid-cols-2">
            @csrf
            <label class="grid gap-2 text-sm font-bold text-ink-950">الصورة
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                @error('image')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
            <label class="grid gap-2 text-sm font-bold text-ink-950">وصف الصورة
                <input name="alt_text" value="{{ old('alt_text') }}" maxlength="255" placeholder="مثال: واجهة مركز الصيانة" class="h-12 rounded-xl border border-slate-200 bg-white px-4 outline-none focus:border-brand-500">
                @error('alt_text')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
            </label>
            <label class="flex items-center gap-3 text-sm font-bold text-slate-700">
                <input type="hidden" name="is_cover" value="0">
                <input type="checkbox" name="is_cover" value="1" @checked(old('is_cover')) class="size-5 rounded border-slate-300 text-brand-600">
                اجعلها صورة الغلاف
            </label>
            <div class="flex justify-end"><button class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-black text-white hover:bg-brand-500">رفع الصورة</button></div>
        </form>
    @endif

    @if ($serviceCenter->images->isNotEmpty())
        <div class="mt-6 grid gap-5 md:grid-cols-2">
            @foreach ($serviceCenter->images as $centerImage)
                <article class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="relative aspect-video bg-slate-100">
                        <img src="{{ Storage::disk('public')->url($centerImage->path) }}" alt="{{ $centerImage->alt_text ?: $serviceCenter->name }}" class="h-full w-full object-cover">
                        @if ($centerImage->is_cover)<span class="absolute right-3 top-3 rounded-full bg-brand-600 px-3 py-1 text-xs font-black text-white">صورة الغلاف</span>@endif
                    </div>
                    <div class="grid gap-3 p-4">
                        <form method="POST" action="{{ route($routePrefix.'.service-centers.images.update', [$serviceCenter, $centerImage]) }}" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <input name="alt_text" value="{{ $centerImage->alt_text }}" maxlength="255" aria-label="وصف الصورة" placeholder="وصف الصورة" class="h-11 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-brand-500">
                            <button class="rounded-xl border border-slate-200 px-4 text-xs font-black text-slate-700 hover:bg-slate-50">حفظ الوصف</button>
                        </form>
                        <div class="flex flex-wrap gap-2">
                            @unless ($centerImage->is_cover)
                                <form method="POST" action="{{ route($routePrefix.'.service-centers.images.cover.update', [$serviceCenter, $centerImage]) }}">
                                    @csrf
                                    @method('PUT')
                                    <button class="rounded-xl bg-ink-950 px-4 py-2 text-xs font-black text-white hover:bg-brand-700">تعيين كغلاف</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route($routePrefix.'.service-centers.images.destroy', [$serviceCenter, $centerImage]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-xl border border-red-200 px-4 py-2 text-xs font-black text-red-700 hover:bg-red-50">حذف الصورة</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($serviceCenter->images->count() > 1)
            <form method="POST" action="{{ route($routePrefix.'.service-centers.images.reorder', $serviceCenter) }}" class="mt-6 rounded-2xl border border-slate-200 p-4">
                @csrf
                @method('PATCH')
                <h3 class="font-black text-ink-950">ترتيب الصور</h3>
                <p class="mt-1 text-xs text-slate-500">استخدم أرقامًا مختلفة من 0 إلى 9؛ الرقم الأصغر يظهر أولًا.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($serviceCenter->images as $index => $centerImage)
                        <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-sm font-bold text-slate-700">
                            <input type="hidden" name="images[{{ $index }}][id]" value="{{ $centerImage->id }}">
                            <img src="{{ Storage::disk('public')->url($centerImage->path) }}" alt="" class="size-12 rounded-lg object-cover">
                            <span class="min-w-0 flex-1 truncate">{{ $centerImage->alt_text ?: 'صورة بدون وصف' }}</span>
                            <input type="number" name="images[{{ $index }}][sort_order]" value="{{ $centerImage->sort_order }}" min="0" max="9" required class="h-10 w-16 rounded-lg border border-slate-200 bg-white px-2 text-center">
                        </label>
                    @endforeach
                </div>
                @error('images')<p class="mt-3 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
                <div class="mt-4 flex justify-end"><button class="rounded-xl border border-brand-200 px-5 py-2.5 text-sm font-black text-brand-700 hover:bg-brand-50">حفظ الترتيب</button></div>
            </form>
        @endif
    @else
        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لم تتم إضافة صور للمركز بعد.</div>
    @endif
</section>
