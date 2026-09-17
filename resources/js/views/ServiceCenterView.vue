<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { serviceCenterApi, storageUrl } from '../api';

const route = useRoute();
const center = ref(null);
const loading = ref(true);
const errorMessage = ref('');
const failedImages = ref(new Set());

const dayLabels = {
    saturday: 'السبت',
    sunday: 'الأحد',
    monday: 'الاثنين',
    tuesday: 'الثلاثاء',
    wednesday: 'الأربعاء',
    thursday: 'الخميس',
    friday: 'الجمعة',
};

const whatsappLink = computed(() => {
    if (! center.value?.whatsapp) return null;
    const phone = center.value.whatsapp.replace(/\D/g, '').replace(/^0/, '20');
    return `https://wa.me/${phone}`;
});

async function loadCenter() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await serviceCenterApi.show(route.params.slug);
        center.value = response.data;
        document.title = `${center.value.name} | موثوق`;
    } catch (error) {
        errorMessage.value = error.status === 404 ? 'مركز الصيانة غير موجود أو غير متاح حاليًا.' : error.message;
    } finally {
        loading.value = false;
    }
}

function formatTime(time) {
    if (! time) return '';
    const [hour, minute] = time.split(':').map(Number);
    const date = new Date(2000, 0, 1, hour, minute);
    return new Intl.DateTimeFormat('ar-EG', { hour: 'numeric', minute: '2-digit' }).format(date);
}

function markImageFailed(id) {
    failedImages.value = new Set([...failedImages.value, id]);
}

watch(() => route.params.slug, loadCenter);
onMounted(loadCenter);
</script>

<template>
    <div v-if="loading" class="mx-auto min-h-[65vh] max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="h-7 w-48 animate-pulse rounded bg-[#e2eae7]"></div>
        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="h-96 animate-pulse rounded-[2rem] bg-[#e2eae7]"></div>
            <div class="h-72 animate-pulse rounded-[2rem] bg-[#e8eeec]"></div>
        </div>
    </div>

    <div v-else-if="errorMessage" class="mx-auto grid min-h-[65vh] max-w-2xl place-items-center px-4 py-16 text-center">
        <div>
            <div class="mx-auto grid size-20 place-items-center rounded-3xl bg-red-50 text-red-700">
                <svg viewBox="0 0 24 24" class="size-9" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9" /><path d="M12 7v6M12 17h.01" /></svg>
            </div>
            <h1 class="mt-5 text-2xl font-black text-ink-950">تعذر عرض المركز</h1>
            <p class="mt-3 leading-7 text-[#6c8085]">{{ errorMessage }}</p>
            <RouterLink to="/" class="mt-6 inline-flex rounded-xl bg-ink-950 px-5 py-3 text-sm font-black text-white">العودة للمراكز</RouterLink>
        </div>
    </div>

    <template v-else-if="center">
        <section class="border-b border-[#e0e8e5] bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <nav class="flex items-center gap-2 text-xs font-bold text-[#789095]" aria-label="مسار الصفحة">
                    <RouterLink to="/" class="hover:text-brand-700">الرئيسية</RouterLink>
                    <span>/</span>
                    <RouterLink to="/#centers" class="hover:text-brand-700">مراكز الصيانة</RouterLink>
                    <span>/</span>
                    <span class="truncate text-[#435e64]">{{ center.name }}</span>
                </nav>
            </div>
        </section>

        <section class="py-8 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-7 lg:grid-cols-[1fr_360px]">
                    <div>
                        <div class="relative min-h-80 overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#d9eee7] to-[#cbd9d5] sm:min-h-105">
                            <img
                                v-if="center.cover_image && ! failedImages.has(center.cover_image.path)"
                                :src="storageUrl(center.cover_image.path)"
                                :alt="center.cover_image.alt_text || center.name"
                                class="absolute inset-0 h-full w-full object-cover"
                                @error="markImageFailed(center.cover_image.path)"
                            >
                            <div v-else class="absolute inset-0 grid place-items-center text-brand-800/55">
                                <svg viewBox="0 0 180 100" class="w-52 sm:w-72" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M25 70V45l14-25h80l25 25v25M12 70h156M42 70a14 14 0 1 0 28 0M111 70a14 14 0 1 0 28 0M44 33h72M79 21v27M68 10h34v11H68z" /></svg>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-950/90 via-ink-950/40 to-transparent px-6 pt-24 pb-6 text-white sm:px-8 sm:pb-8">
                                <div class="mb-3 flex flex-wrap gap-2">
                                    <span v-if="center.is_verified" class="inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-3 py-1.5 text-xs font-black">
                                        <span>✓</span> مركز موثق
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold backdrop-blur">
                                        <span class="text-gold-400">★</span> {{ center.rating.average ?? 'جديد' }}
                                        <span v-if="center.rating.count">({{ center.rating.count }} تقييم)</span>
                                    </span>
                                </div>
                                <h1 class="text-2xl font-black leading-tight sm:text-4xl">{{ center.name }}</h1>
                                <p class="mt-3 flex items-center gap-2 text-sm text-white/75 sm:text-base">
                                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></svg>
                                    {{ center.address }}، {{ center.city.name }}، {{ center.city.governorate.name }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-6">
                            <section class="rounded-3xl border border-[#e0e8e5] bg-white p-6 sm:p-7">
                                <h2 class="text-xl font-black text-ink-950">عن المركز</h2>
                                <p class="mt-3 text-sm leading-8 text-[#60777c] sm:text-base">{{ center.description || 'مركز متخصص يقدم خدمات صيانة وفحص السيارات.' }}</p>
                            </section>

                            <section class="grid gap-6 md:grid-cols-2">
                                <div class="rounded-3xl border border-[#e0e8e5] bg-white p-6">
                                    <div class="mb-5 flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">⚙</span><h2 class="text-lg font-black text-ink-950">الخدمات المتاحة</h2></div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="service in center.services" :key="service.id" class="rounded-xl border border-brand-100 bg-brand-50 px-3 py-2 text-xs font-bold text-brand-800">{{ service.name }}</span>
                                    </div>
                                </div>
                                <div class="rounded-3xl border border-[#e0e8e5] bg-white p-6">
                                    <div class="mb-5 flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-[#fff7e7] text-[#a56d00]">◆</span><h2 class="text-lg font-black text-ink-950">ماركات السيارات</h2></div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="brand in center.car_brands" :key="brand.id" class="rounded-xl border border-[#efe5ce] bg-[#fffbf3] px-3 py-2 text-xs font-bold text-[#6d5930]">{{ brand.name }}</span>
                                    </div>
                                </div>
                            </section>

                            <section v-if="center.opening_hours.length" class="rounded-3xl border border-[#e0e8e5] bg-white p-6 sm:p-7">
                                <h2 class="text-xl font-black text-ink-950">مواعيد العمل</h2>
                                <div class="mt-5 grid gap-x-8 gap-y-1 sm:grid-cols-2">
                                    <div v-for="hours in center.opening_hours" :key="hours.day" class="flex items-center justify-between border-b border-[#edf1ef] py-3 text-sm">
                                        <span class="font-bold text-[#405c62]">{{ dayLabels[hours.day] ?? hours.day }}</span>
                                        <span :class="hours.is_closed ? 'text-red-600' : 'text-[#6e8287]'">{{ hours.is_closed ? 'مغلق' : `${formatTime(hours.opens_at)} - ${formatTime(hours.closes_at)}` }}</span>
                                    </div>
                                </div>
                            </section>

                            <section v-if="center.latest_reviews.length" class="rounded-3xl border border-[#e0e8e5] bg-white p-6 sm:p-7">
                                <h2 class="text-xl font-black text-ink-950">أحدث التقييمات</h2>
                                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <article v-for="review in center.latest_reviews" :key="review.id" class="rounded-2xl bg-[#f7f9f8] p-4">
                                        <div class="flex items-center justify-between gap-3"><strong class="text-sm text-ink-950">{{ review.user.name }}</strong><span class="text-sm text-gold-400">★ {{ review.rating }}</span></div>
                                        <p class="mt-3 text-sm leading-7 text-[#687d82]">{{ review.comment }}</p>
                                    </article>
                                </div>
                            </section>
                        </div>
                    </div>

                    <aside class="lg:sticky lg:top-24 lg:self-start">
                        <div class="rounded-3xl border border-[#dce6e2] bg-white p-6 surface-shadow">
                            <p class="text-xs font-black text-brand-700">تواصل مباشرة</p>
                            <h2 class="mt-2 text-xl font-black text-ink-950">جاهز لصيانة سيارتك؟</h2>
                            <p class="mt-2 text-sm leading-6 text-[#718489]">اتصل بالمركز أو ابعت رسالة واتساب للاستفسار والحجز.</p>

                            <div class="mt-6 grid gap-3">
                                <a :href="`tel:${center.phone}`" class="flex h-13 items-center justify-center gap-2 rounded-xl bg-ink-950 text-sm font-black text-white transition hover:bg-brand-700">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2.1Z" /></svg>
                                    اتصل الآن
                                </a>
                                <a v-if="whatsappLink" :href="whatsappLink" target="_blank" rel="noopener noreferrer" class="flex h-13 items-center justify-center gap-2 rounded-xl bg-[#25a866] text-sm font-black text-white transition hover:bg-[#218f59]">
                                    واتساب
                                </a>
                            </div>

                            <div class="mt-6 grid gap-3 border-t border-[#edf1ef] pt-5 text-sm">
                                <div class="flex items-center justify-between gap-4"><span class="text-[#7a8d91]">رقم الهاتف</span><a :href="`tel:${center.phone}`" dir="ltr" class="font-bold text-ink-950">{{ center.phone }}</a></div>
                                <div class="flex items-center justify-between gap-4"><span class="text-[#7a8d91]">المدينة</span><strong class="text-ink-950">{{ center.city.name }}</strong></div>
                                <div class="flex items-center justify-between gap-4"><span class="text-[#7a8d91]">حالة التوثيق</span><strong :class="center.is_verified ? 'text-brand-700' : 'text-[#7a8d91]'">{{ center.is_verified ? 'موثق' : 'غير موثق' }}</strong></div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-[#e4e9e7] bg-[#f0f5f3] p-4 text-xs leading-6 text-[#61777c]">
                            <strong class="text-ink-950">نصيحة موثوق:</strong>
                            اتأكد من تفاصيل الخدمة والسعر مع المركز قبل الزيارة.
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </template>
</template>
