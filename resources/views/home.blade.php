@extends('layouts.app')

@section('content')
    <section class="relative isolate overflow-hidden bg-ink-950 text-white">
        <div class="absolute inset-0 -z-10 opacity-70" aria-hidden="true">
            <div class="absolute -right-32 top-10 size-96 rounded-full bg-brand-500/25 blur-3xl"></div>
            <div class="absolute -left-40 bottom-0 size-[28rem] rounded-full bg-[#d9a43b]/12 blur-3xl"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.025)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.025)_1px,transparent_1px)] bg-[size:42px_42px]"></div>
        </div>

        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.08fr_.92fr] lg:px-8 lg:py-24">
            <div class="max-w-2xl">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-brand-400/25 bg-brand-400/10 px-3.5 py-2 text-xs font-bold text-brand-200">
                    <span class="size-2 rounded-full bg-brand-400"></span>
                    بيانات واضحة. اختيار أذكى. صيانة بثقة.
                </div>
                <h1 class="text-balance text-4xl font-black leading-[1.35] tracking-tight sm:text-5xl lg:text-[3.65rem]">
                    مركز الصيانة المناسب <span class="text-brand-300">لسيارتك</span> أقرب مما تتخيل
                </h1>
                <p class="mt-5 max-w-xl text-base leading-8 text-white/65 sm:text-lg">قارن بين مراكز الصيانة، اعرف خدماتهم وماركات السيارات التي يدعمونها، واختر بناءً على تقييمات وتجارب حقيقية.</p>

                <form method="GET" action="{{ route('home') }}#centers" class="mt-8 grid gap-3 rounded-3xl border border-white/10 bg-white/7 p-3 backdrop-blur-lg sm:grid-cols-[1fr_1fr_auto]">
                    <select name="governorate" class="h-14 w-full rounded-2xl border border-white/10 bg-white/10 px-4 text-sm font-bold text-white outline-none focus:border-brand-400">
                        <option class="text-ink-950" value="">اختر المحافظة</option>
                        @foreach ($governorates as $governorate)
                            <option class="text-ink-950" value="{{ $governorate->slug }}" @selected(request('governorate') === $governorate->slug)>{{ $governorate->name }}</option>
                        @endforeach
                    </select>
                    <select name="service" class="h-14 w-full rounded-2xl border border-white/10 bg-white/10 px-4 text-sm font-bold text-white outline-none focus:border-brand-400">
                        <option class="text-ink-950" value="">نوع الخدمة</option>
                        @foreach ($services as $service)
                            <option class="text-ink-950" value="{{ $service->slug }}" @selected(request('service') === $service->slug)>{{ $service->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="h-14 rounded-2xl bg-brand-500 px-7 text-sm font-black text-white shadow-lg shadow-black/15 transition hover:bg-brand-400">ابحث الآن</button>
                </form>

                <div class="mt-7 flex flex-wrap gap-x-8 gap-y-3 text-sm text-white/55">
                    <span><span class="text-brand-300">✓</span> بدون رسوم بحث</span>
                    <span><span class="text-brand-300">✓</span> مراكز موثقة</span>
                    <span><span class="text-brand-300">✓</span> تقييمات حقيقية</span>
                </div>
            </div>

            <div class="relative mx-auto hidden w-full max-w-lg lg:block" aria-hidden="true">
                <div class="rounded-[2.5rem] border border-white/12 bg-white/7 p-5 shadow-2xl shadow-black/25 backdrop-blur-xl">
                    <div class="rounded-[2rem] bg-gradient-to-br from-[#eff8f5] to-[#d9ebe5] p-8 text-ink-950">
                        <div class="flex items-center justify-between"><span class="rounded-full bg-white px-3 py-1.5 text-xs font-black text-brand-700">اختيار موثوق</span><span class="text-gold-400">★★★★★</span></div>
                        <svg viewBox="0 0 520 260" class="mt-5 w-full drop-shadow-xl" fill="none"><path d="M80 178V121c0-16 8-31 22-40l63-41c12-8 26-12 41-12h113c26 0 50 12 65 33l44 60c8 11 12 24 12 37v20" fill="#fff" stroke="#123038" stroke-width="7" /><path d="M62 178h396v38H62z" fill="#16806b" stroke="#123038" stroke-width="7" /><path d="M170 62h148c17 0 33 8 43 22l27 37H129l41-59Z" fill="#b2ead9" stroke="#123038" stroke-width="7" /><circle cx="153" cy="211" r="35" fill="#0a2026" /><circle cx="153" cy="211" r="14" fill="#eefbf7" /><circle cx="371" cy="211" r="35" fill="#0a2026" /><circle cx="371" cy="211" r="14" fill="#eefbf7" /><path d="M250 33v88M218 63h64" stroke="#16806b" stroke-width="9" stroke-linecap="round" /></svg>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="centers" class="scroll-mt-24 py-14 sm:py-18">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-sm font-black text-brand-700">اختيارات قريبة منك</p>
                <h2 class="mt-2 text-2xl font-black text-ink-950 sm:text-3xl">اكتشف مراكز الصيانة</h2>
                <p class="mt-2 text-sm text-[#718489]">وجدنا {{ $serviceCenters->total() }} مركزًا مطابقًا لاختيارك</p>
            </div>

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid items-start gap-7 lg:grid-cols-[280px_1fr]">
                <aside>
                    <form method="GET" action="{{ route('home') }}#centers" class="rounded-3xl border border-[#dfe7e4] bg-white p-5 surface-shadow">
                        <div class="mb-5"><p class="text-xs font-bold text-brand-700">خصص بحثك</p><h2 class="mt-1 text-lg font-black text-ink-950">فلترة النتائج</h2></div>
                        <div class="grid gap-4">
                            <label class="grid gap-2 text-sm font-bold text-[#29464d]">المحافظة
                                <select name="governorate" data-governorate-select class="h-12 rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3">
                                    <option value="">كل المحافظات</option>
                                    @foreach ($governorates as $governorate)
                                        <option value="{{ $governorate->slug }}" @selected(request('governorate') === $governorate->slug)>{{ $governorate->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm font-bold text-[#29464d]">المدينة
                                <select name="city" data-city-select data-selected="{{ request('city') }}" class="h-12 rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3">
                                    <option value="">كل المدن</option>
                                    @foreach ($governorates as $governorate)
                                        @foreach ($governorate->cities as $city)
                                            <option value="{{ $city->slug }}" data-governorate="{{ $governorate->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2 text-sm font-bold text-[#29464d]">نوع الخدمة
                                <select name="service" class="h-12 rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3"><option value="">كل الخدمات</option>@foreach ($services as $service)<option value="{{ $service->slug }}" @selected(request('service') === $service->slug)>{{ $service->name }}</option>@endforeach</select>
                            </label>
                            <label class="grid gap-2 text-sm font-bold text-[#29464d]">ماركة السيارة
                                <select name="car_brand" class="h-12 rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3"><option value="">كل الماركات</option>@foreach ($carBrands as $brand)<option value="{{ $brand->slug }}" @selected(request('car_brand') === $brand->slug)>{{ $brand->name }}</option>@endforeach</select>
                            </label>
                            <label class="grid gap-2 text-sm font-bold text-[#29464d]">التوثيق
                                <select name="verified" class="h-12 rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3"><option value="">الكل</option><option value="1" @selected(request('verified') === '1')>موثق</option><option value="0" @selected(request('verified') === '0')>غير موثق</option></select>
                            </label>
                            <button class="h-12 rounded-xl bg-ink-950 text-sm font-black text-white hover:bg-brand-700">تطبيق الفلاتر</button>
                            <a href="{{ route('home') }}#centers" class="text-center text-sm font-bold text-[#60777c] underline">مسح الفلاتر</a>
                        </div>
                    </form>
                </aside>

                <div>
                    @if ($serviceCenters->count())
                        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($serviceCenters as $center)
                                <x-service-center-card :center="$center" />
                            @endforeach
                        </div>
                        <div class="mt-8">{{ $serviceCenters->links() }}</div>
                    @else
                        <div class="rounded-3xl border border-dashed border-[#ccd9d5] bg-white px-6 py-16 text-center">
                            <h3 class="text-xl font-black text-ink-950">مفيش نتائج مطابقة حاليًا</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-[#718489]">جرّب توسّع نطاق البحث أو امسح بعض الفلاتر.</p>
                            <a href="{{ route('home') }}#centers" class="mt-5 inline-flex rounded-xl bg-ink-950 px-5 py-3 text-sm font-black text-white">عرض كل المراكز</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center"><p class="text-sm font-black text-brand-700">رحلة بسيطة</p><h2 class="mt-2 text-3xl font-black text-ink-950">من المشكلة للحل في 3 خطوات</h2></div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                @foreach ([['حدد احتياجك', 'اختار محافظتك ونوع الخدمة وماركة سيارتك.'], ['قارن بثقة', 'راجع بيانات المراكز والخدمات والتقييمات في مكان واحد.'], ['تواصل مباشرة', 'اتصل بالمركز المناسب واحجز خدمتك بسهولة.']] as $index => $step)
                    <article class="rounded-3xl border border-[#e3e9e7] bg-[#fbfcfb] p-6"><span class="text-5xl font-black text-brand-100">0{{ $index + 1 }}</span><h3 class="mt-5 text-lg font-black text-ink-950">{{ $step[0] }}</h3><p class="mt-3 text-sm leading-7 text-[#6d8186]">{{ $step[1] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
