<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';
import { catalogApi, serviceCenterApi } from '../api';
import FilterPanel from '../components/FilterPanel.vue';
import ServiceCenterCard from '../components/ServiceCenterCard.vue';

const centers = ref([]);
const meta = ref(null);
const governorates = ref([]);
const cities = ref([]);
const services = ref([]);
const carBrands = ref([]);
const loading = ref(true);
const loadingCities = ref(false);
const catalogLoading = ref(true);
const errorMessage = ref('');
const filtersOpen = ref(false);
const filters = ref(emptyFilters());

function emptyFilters() {
    return {
        governorate: '',
        city: '',
        service: '',
        car_brand: '',
        verified: '',
        page: 1,
        per_page: 9,
    };
}

async function loadCatalog() {
    catalogLoading.value = true;

    try {
        const [governorateResponse, serviceResponse, brandResponse] = await Promise.all([
            catalogApi.governorates(),
            catalogApi.services(),
            catalogApi.carBrands(),
        ]);

        governorates.value = governorateResponse.data;
        services.value = serviceResponse.data;
        carBrands.value = brandResponse.data;
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        catalogLoading.value = false;
    }
}

async function loadCities(governorate) {
    cities.value = [];
    if (! governorate) return;

    loadingCities.value = true;
    try {
        const response = await catalogApi.cities(governorate);
        cities.value = response.data;
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        loadingCities.value = false;
    }
}

async function loadCenters({ scroll = false } = {}) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await serviceCenterApi.index(filters.value);
        centers.value = response.data;
        meta.value = response.meta;
        filtersOpen.value = false;

        if (scroll) {
            await nextTick();
            document.querySelector('#centers')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (error) {
        errorMessage.value = error.message;
        centers.value = [];
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    filters.value.page = 1;
    loadCenters({ scroll: true });
}

function resetFilters() {
    filters.value = emptyFilters();
    cities.value = [];
    loadCenters({ scroll: true });
}

function changePage(page) {
    if (page < 1 || page > (meta.value?.last_page ?? 1)) return;
    filters.value.page = page;
    loadCenters({ scroll: true });
}

watch(() => filters.value.governorate, loadCities);

onMounted(async () => {
    await Promise.all([loadCatalog(), loadCenters()]);
});
</script>

<template>
    <section class="relative isolate overflow-hidden bg-ink-950 text-white">
        <div class="absolute inset-0 -z-10 opacity-70" aria-hidden="true">
            <div class="absolute -right-32 top-10 size-96 rounded-full bg-brand-500/25 blur-3xl"></div>
            <div class="absolute -left-40 bottom-0 size-[28rem] rounded-full bg-[#d9a43b]/12 blur-3xl"></div>
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,.025)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.025)_1px,transparent_1px)] bg-[size:42px_42px]"></div>
        </div>

        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 md:py-22 lg:grid-cols-[1.08fr_.92fr] lg:px-8 lg:py-24">
            <div class="max-w-2xl">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-brand-400/25 bg-brand-400/10 px-3.5 py-2 text-xs font-bold text-brand-200">
                    <span class="size-2 rounded-full bg-brand-400 shadow-[0_0_0_5px_rgba(69,190,161,.12)]"></span>
                    بيانات واضحة. اختيار أذكى. صيانة بثقة.
                </div>
                <h1 class="text-balance text-4xl font-black leading-[1.35] tracking-tight sm:text-5xl lg:text-[3.65rem]">
                    مركز الصيانة المناسب
                    <span class="text-brand-300">لسيارتك</span> أقرب مما تتخيل
                </h1>
                <p class="mt-5 max-w-xl text-base leading-8 text-white/65 sm:text-lg">قارن بين مراكز الصيانة، اعرف خدماتهم وماركات السيارات التي يدعمونها، واختر بناءً على تقييمات وتجارب حقيقية.</p>

                <div class="mt-8 grid gap-3 rounded-3xl border border-white/10 bg-white/7 p-3 backdrop-blur-lg sm:grid-cols-[1fr_1fr_auto]">
                    <label class="relative">
                        <span class="sr-only">المحافظة</span>
                        <svg viewBox="0 0 24 24" class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-brand-300" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></svg>
                        <select v-model="filters.governorate" :disabled="catalogLoading" class="h-14 w-full appearance-none rounded-2xl border border-white/10 bg-white/10 pr-12 pl-4 text-sm font-bold text-white outline-none focus:border-brand-400">
                            <option class="text-ink-950" value="">اختر المحافظة</option>
                            <option v-for="item in governorates" :key="item.id" class="text-ink-950" :value="item.slug">{{ item.name }}</option>
                        </select>
                    </label>
                    <label class="relative">
                        <span class="sr-only">الخدمة</span>
                        <svg viewBox="0 0 24 24" class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-brand-300" fill="none" stroke="currentColor" stroke-width="2"><path d="m14.7 6.3 3-3a7 7 0 0 1-9.2 9.2l-5.4 5.4a2.1 2.1 0 0 0 3 3l5.4-5.4a7 7 0 0 0 9.2-9.2l-3 3-3-3Z" /></svg>
                        <select v-model="filters.service" :disabled="catalogLoading" class="h-14 w-full appearance-none rounded-2xl border border-white/10 bg-white/10 pr-12 pl-4 text-sm font-bold text-white outline-none focus:border-brand-400">
                            <option class="text-ink-950" value="">نوع الخدمة</option>
                            <option v-for="item in services" :key="item.id" class="text-ink-950" :value="item.slug">{{ item.name }}</option>
                        </select>
                    </label>
                    <button type="button" class="h-14 rounded-2xl bg-brand-500 px-7 text-sm font-black text-white shadow-lg shadow-black/15 transition hover:bg-brand-400" @click="applyFilters">
                        ابحث الآن
                    </button>
                </div>

                <div class="mt-7 flex flex-wrap gap-x-8 gap-y-3 text-sm text-white/55">
                    <span class="flex items-center gap-2"><span class="text-brand-300">✓</span> بدون رسوم بحث</span>
                    <span class="flex items-center gap-2"><span class="text-brand-300">✓</span> مراكز موثقة</span>
                    <span class="flex items-center gap-2"><span class="text-brand-300">✓</span> تقييمات حقيقية</span>
                </div>
            </div>

            <div class="relative mx-auto hidden w-full max-w-lg lg:block" aria-hidden="true">
                <div class="absolute inset-10 rounded-full bg-brand-400/15 blur-3xl"></div>
                <div class="relative rounded-[2.5rem] border border-white/12 bg-white/7 p-5 shadow-2xl shadow-black/25 backdrop-blur-xl">
                    <div class="rounded-[2rem] bg-gradient-to-br from-[#eff8f5] to-[#d9ebe5] p-8 text-ink-950">
                        <div class="flex items-center justify-between">
                            <span class="rounded-full bg-white px-3 py-1.5 text-xs font-black text-brand-700 shadow-sm">اختيار موثوق</span>
                            <div class="flex gap-1 text-gold-400"><span v-for="i in 5" :key="i">★</span></div>
                        </div>
                        <svg viewBox="0 0 520 260" class="mt-5 w-full drop-shadow-xl" fill="none">
                            <path d="M80 178V121c0-16 8-31 22-40l63-41c12-8 26-12 41-12h113c26 0 50 12 65 33l44 60c8 11 12 24 12 37v20" fill="#fff" stroke="#123038" stroke-width="7" />
                            <path d="M62 178h396v38H62z" fill="#16806b" stroke="#123038" stroke-width="7" />
                            <path d="M170 62h148c17 0 33 8 43 22l27 37H129l41-59Z" fill="#b2ead9" stroke="#123038" stroke-width="7" />
                            <circle cx="153" cy="211" r="35" fill="#0a2026" /><circle cx="153" cy="211" r="14" fill="#eefbf7" />
                            <circle cx="371" cy="211" r="35" fill="#0a2026" /><circle cx="371" cy="211" r="14" fill="#eefbf7" />
                            <path d="M250 33v88M218 63h64" stroke="#16806b" stroke-width="9" stroke-linecap="round" />
                            <path d="M90 143h55M385 143h55" stroke="#e8b44b" stroke-width="9" stroke-linecap="round" />
                        </svg>
                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <div class="rounded-2xl bg-white p-3 text-center shadow-sm"><strong class="block text-xl text-ink-950">+50</strong><span class="text-[10px] text-[#6b8085]">مركز صيانة</span></div>
                            <div class="rounded-2xl bg-white p-3 text-center shadow-sm"><strong class="block text-xl text-ink-950">4.8</strong><span class="text-[10px] text-[#6b8085]">متوسط التقييم</span></div>
                            <div class="rounded-2xl bg-white p-3 text-center shadow-sm"><strong class="block text-xl text-ink-950">24/7</strong><span class="text-[10px] text-[#6b8085]">سهولة البحث</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="centers" class="scroll-mt-24 py-14 sm:py-18">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-black text-brand-700">اختيارات قريبة منك</p>
                    <h2 class="mt-2 text-2xl font-black text-ink-950 sm:text-3xl">اكتشف مراكز الصيانة</h2>
                    <p v-if="meta" class="mt-2 text-sm text-[#718489]">وجدنا {{ meta.total }} مركزًا مطابقًا لاختيارك</p>
                </div>
                <button type="button" class="flex h-11 items-center gap-2 rounded-xl border border-[#dbe5e1] bg-white px-4 text-sm font-black text-ink-950 lg:hidden" @click="filtersOpen = ! filtersOpen">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4" /></svg>
                    الفلاتر
                </button>
            </div>

            <div class="grid items-start gap-7 lg:grid-cols-[280px_1fr]">
                <aside :class="filtersOpen ? 'block' : 'hidden lg:block'">
                    <FilterPanel
                        v-model="filters"
                        :governorates="governorates"
                        :cities="cities"
                        :services="services"
                        :car-brands="carBrands"
                        :loading-cities="loadingCities"
                        :loading="loading"
                        @apply="applyFilters"
                        @reset="resetFilters"
                    />
                </aside>

                <div>
                    <div v-if="errorMessage" class="mb-6 flex items-center justify-between gap-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-800">
                        <span>{{ errorMessage }}</span>
                        <button type="button" class="shrink-0 underline" @click="loadCenters()">حاول مجددًا</button>
                    </div>

                    <div v-if="loading" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3" aria-label="جاري تحميل النتائج">
                        <div v-for="i in 6" :key="i" class="overflow-hidden rounded-3xl border border-[#e3e9e7] bg-white">
                            <div class="h-48 animate-pulse bg-[#e6eeeb]"></div>
                            <div class="grid gap-4 p-5"><div class="h-5 w-3/4 animate-pulse rounded bg-[#e6eeeb]"></div><div class="h-4 w-full animate-pulse rounded bg-[#edf2f0]"></div><div class="h-11 animate-pulse rounded-xl bg-[#e6eeeb]"></div></div>
                        </div>
                    </div>

                    <div v-else-if="centers.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        <ServiceCenterCard v-for="center in centers" :key="center.id" :center="center" />
                    </div>

                    <div v-else class="rounded-3xl border border-dashed border-[#ccd9d5] bg-white px-6 py-16 text-center">
                        <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-50 text-brand-700">
                            <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4M8 11h6" /></svg>
                        </div>
                        <h3 class="mt-5 text-xl font-black text-ink-950">مفيش نتائج مطابقة حاليًا</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-[#718489]">جرّب توسّع نطاق البحث أو امسح بعض الفلاتر، وممكن تلاقي اختيارات مناسبة أكتر.</p>
                        <button type="button" class="mt-5 rounded-xl bg-ink-950 px-5 py-3 text-sm font-black text-white" @click="resetFilters">عرض كل المراكز</button>
                    </div>

                    <div v-if="meta && meta.last_page > 1 && ! loading" class="mt-8 flex items-center justify-center gap-2" aria-label="التنقل بين الصفحات">
                        <button class="grid size-10 place-items-center rounded-xl border border-[#dbe5e1] bg-white disabled:opacity-40" :disabled="meta.current_page === 1" @click="changePage(meta.current_page - 1)">→</button>
                        <span class="px-3 text-sm font-bold text-[#657a7f]">{{ meta.current_page }} من {{ meta.last_page }}</span>
                        <button class="grid size-10 place-items-center rounded-xl border border-[#dbe5e1] bg-white disabled:opacity-40" :disabled="meta.current_page === meta.last_page" @click="changePage(meta.current_page + 1)">←</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-black text-brand-700">رحلة بسيطة</p>
                <h2 class="mt-2 text-3xl font-black text-ink-950">من المشكلة للحل في 3 خطوات</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <div v-for="(step, index) in [
                    ['حدد احتياجك', 'اختار محافظتك ونوع الخدمة وماركة سيارتك.'],
                    ['قارن بثقة', 'راجع بيانات المراكز والخدمات والتقييمات في مكان واحد.'],
                    ['تواصل مباشرة', 'اتصل بالمركز المناسب واحجز خدمتك بسهولة.'],
                ]" :key="step[0]" class="relative rounded-3xl border border-[#e3e9e7] bg-[#fbfcfb] p-6">
                    <span class="absolute left-5 top-5 text-5xl font-black text-brand-100">0{{ index + 1 }}</span>
                    <div class="relative pt-12"><h3 class="text-lg font-black text-ink-950">{{ step[0] }}</h3><p class="mt-3 text-sm leading-7 text-[#6d8186]">{{ step[1] }}</p></div>
                </div>
            </div>
        </div>
    </section>
</template>
