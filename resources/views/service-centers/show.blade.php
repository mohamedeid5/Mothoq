@extends('layouts.app')

@section('title', $serviceCenter->name.' | موثوق')
@section('description', $serviceCenter->description ?: 'بيانات وخدمات مركز '.$serviceCenter->name)

@section('content')
    <section class="border-b border-[#e0e8e5] bg-white">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs font-bold text-[#789095]" aria-label="مسار الصفحة"><a href="{{ route('home') }}" class="hover:text-brand-700">الرئيسية</a><span>/</span><a href="{{ route('home') }}#centers" class="hover:text-brand-700">مراكز الصيانة</a><span>/</span><span class="truncate text-[#435e64]">{{ $serviceCenter->name }}</span></nav>
        </div>
    </section>

    <section class="py-8 sm:py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-7 lg:grid-cols-[1fr_360px]">
                <div>
                    <div class="relative min-h-80 overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#d9eee7] to-[#cbd9d5] sm:min-h-105">
                        <div class="absolute inset-0 grid place-items-center text-brand-800/55"><svg viewBox="0 0 180 100" class="w-52 sm:w-72" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M25 70V45l14-25h80l25 25v25M12 70h156M42 70a14 14 0 1 0 28 0M111 70a14 14 0 1 0 28 0M44 33h72M79 21v27M68 10h34v11H68z" /></svg></div>
                        @if ($serviceCenter->coverImage)
                            <img src="{{ Storage::url($serviceCenter->coverImage->path) }}" alt="{{ $serviceCenter->coverImage->alt_text ?: $serviceCenter->name }}" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                        @endif
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-950/90 via-ink-950/40 to-transparent px-6 pt-24 pb-6 text-white sm:px-8 sm:pb-8">
                            <div class="mb-3 flex flex-wrap gap-2">
                                @if ($serviceCenter->verified_at)<span class="rounded-full bg-brand-500 px-3 py-1.5 text-xs font-black">✓ مركز موثق</span>@endif
                                <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold"><span class="text-gold-400">★</span> {{ $serviceCenter->published_reviews_avg_rating ? round((float) $serviceCenter->published_reviews_avg_rating, 1) : 'جديد' }} @if ($serviceCenter->published_reviews_count)({{ $serviceCenter->published_reviews_count }} تقييم)@endif</span>
                            </div>
                            <h1 class="text-2xl font-black leading-tight sm:text-4xl">{{ $serviceCenter->name }}</h1>
                            <p class="mt-3 text-sm text-white/75 sm:text-base">⌖ {{ $serviceCenter->address }}، {{ $serviceCenter->city->name }}، {{ $serviceCenter->city->governorate->name }}</p>
                        </div>
                    </div>

                    @if ($serviceCenter->images->count() > 1)
                        <section class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3" aria-label="صور مركز الصيانة">
                            @foreach ($serviceCenter->images as $centerImage)
                                <img src="{{ Storage::disk('public')->url($centerImage->path) }}" alt="{{ $centerImage->alt_text ?: $serviceCenter->name }}" class="aspect-video w-full rounded-2xl border border-[#e0e8e5] object-cover" loading="lazy">
                            @endforeach
                        </section>
                    @endif

                    <div class="mt-6 grid gap-6">
                        <section class="rounded-3xl border border-[#e0e8e5] bg-white p-6 sm:p-7"><h2 class="text-xl font-black text-ink-950">عن المركز</h2><p class="mt-3 text-sm leading-8 text-[#60777c] sm:text-base">{{ $serviceCenter->description ?: 'مركز متخصص يقدم خدمات صيانة وفحص السيارات.' }}</p></section>
                        <section class="grid gap-6 md:grid-cols-2">
                            <div class="rounded-3xl border border-[#e0e8e5] bg-white p-6"><h2 class="mb-5 text-lg font-black text-ink-950">⚙ الخدمات المتاحة</h2><div class="flex flex-wrap gap-2">@forelse ($serviceCenter->services as $service)<span class="rounded-xl border border-brand-100 bg-brand-50 px-3 py-2 text-xs font-bold text-brand-800">{{ $service->name }}</span>@empty<span class="text-sm text-slate-500">لم تضاف خدمات بعد.</span>@endforelse</div></div>
                            <div class="rounded-3xl border border-[#e0e8e5] bg-white p-6"><h2 class="mb-5 text-lg font-black text-ink-950">◆ ماركات السيارات</h2><div class="flex flex-wrap gap-2">@forelse ($serviceCenter->carBrands as $brand)<span class="rounded-xl border border-[#efe5ce] bg-[#fffbf3] px-3 py-2 text-xs font-bold text-[#6d5930]">{{ $brand->name }}</span>@empty<span class="text-sm text-slate-500">لم تضاف ماركات بعد.</span>@endforelse</div></div>
                        </section>

                        @if ($serviceCenter->openingHours->isNotEmpty())
                            <section class="rounded-3xl border border-[#e0e8e5] bg-white p-6 sm:p-7"><h2 class="text-xl font-black text-ink-950">مواعيد العمل</h2><div class="mt-5 grid gap-x-8 gap-y-1 sm:grid-cols-2">@foreach ($serviceCenter->openingHours->sortBy(fn ($hours) => $hours->day_of_week->sortOrder()) as $hours)<div class="flex items-center justify-between border-b border-[#edf1ef] py-3 text-sm"><span class="font-bold text-[#405c62]">{{ $hours->day_of_week->label() }}</span><span class="{{ $hours->is_closed ? 'text-red-600' : 'text-[#6e8287]' }}">{{ $hours->is_closed ? 'مغلق' : substr($hours->opens_at, 0, 5).' - '.substr($hours->closes_at, 0, 5) }}</span></div>@endforeach</div></section>
                        @endif

                        @if ($serviceCenter->publishedReviews->isNotEmpty())
                            <section class="rounded-3xl border border-[#e0e8e5] bg-white p-6 sm:p-7"><h2 class="text-xl font-black text-ink-950">أحدث التقييمات</h2><div class="mt-5 grid gap-4 sm:grid-cols-2">@foreach ($serviceCenter->publishedReviews as $review)<article class="rounded-2xl bg-[#f7f9f8] p-4"><div class="flex items-center justify-between gap-3"><strong class="text-sm text-ink-950">{{ $review->user->name }}</strong><span class="text-sm text-gold-400">★ {{ $review->rating }}</span></div><p class="mt-3 text-sm leading-7 text-[#687d82]">{{ $review->comment }}</p></article>@endforeach</div></section>
                        @endif
                    </div>
                </div>

                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <div class="rounded-3xl border border-[#dce6e2] bg-white p-6 surface-shadow">
                        <p class="text-xs font-black text-brand-700">تواصل مباشرة</p><h2 class="mt-2 text-xl font-black text-ink-950">جاهز لصيانة سيارتك؟</h2><p class="mt-2 text-sm leading-6 text-[#718489]">اتصل بالمركز أو ابعت رسالة واتساب للاستفسار والحجز.</p>
                        <div class="mt-6 grid gap-3"><a href="tel:{{ $serviceCenter->phone }}" class="flex h-13 items-center justify-center rounded-xl bg-ink-950 text-sm font-black text-white hover:bg-brand-700">اتصل الآن</a>@if ($serviceCenter->whatsapp)<a href="https://wa.me/20{{ ltrim(preg_replace('/\D+/', '', $serviceCenter->whatsapp), '0') }}" target="_blank" rel="noopener noreferrer" class="flex h-13 items-center justify-center rounded-xl bg-[#25a866] text-sm font-black text-white">واتساب</a>@endif</div>
                        <div class="mt-6 grid gap-3 border-t border-[#edf1ef] pt-5 text-sm"><div class="flex justify-between gap-4"><span class="text-[#7a8d91]">رقم الهاتف</span><a href="tel:{{ $serviceCenter->phone }}" dir="ltr" class="font-bold text-ink-950">{{ $serviceCenter->phone }}</a></div><div class="flex justify-between"><span class="text-[#7a8d91]">المدينة</span><strong>{{ $serviceCenter->city->name }}</strong></div><div class="flex justify-between"><span class="text-[#7a8d91]">حالة التوثيق</span><strong class="{{ $serviceCenter->verified_at ? 'text-brand-700' : 'text-[#7a8d91]' }}">{{ $serviceCenter->verified_at ? 'موثق' : 'غير موثق' }}</strong></div></div>
                    </div>
                    <div class="mt-4 rounded-2xl border border-[#e4e9e7] bg-[#f0f5f3] p-4 text-xs leading-6 text-[#61777c]"><strong class="text-ink-950">نصيحة موثوق:</strong> اتأكد من تفاصيل الخدمة والسعر مع المركز قبل الزيارة.</div>
                </aside>
            </div>
        </div>
    </section>
@endsection
