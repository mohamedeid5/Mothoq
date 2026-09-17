<script setup>
import { ref } from 'vue';
import { storageUrl } from '../api';

defineProps({ center: { type: Object, required: true } });
const imageFailed = ref(false);
</script>

<template>
    <article class="group overflow-hidden rounded-3xl border border-[#e0e8e5] bg-white transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_24px_55px_-35px_rgba(10,32,38,.45)]">
        <div class="relative h-48 overflow-hidden bg-gradient-to-br from-[#d8eee7] via-[#eef6f3] to-[#d9e2df]">
            <img
                v-if="center.cover_image && ! imageFailed"
                :src="storageUrl(center.cover_image.path)"
                :alt="center.cover_image.alt_text || center.name"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                @error="imageFailed = true"
            >
            <div v-else class="absolute inset-0 grid place-items-center text-brand-800/60">
                <svg viewBox="0 0 120 70" class="w-32" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 48V32l9-16h55l16 16v16M10 48h100M28 48a9 9 0 1 0 18 0M76 48a9 9 0 1 0 18 0M31 25h47M57 17v15" />
                    <path d="M51 7h18v10H51zM57 7V2h6v5" />
                </svg>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-ink-950/65 to-transparent"></div>
            <span v-if="center.is_verified" class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-black text-brand-700 shadow-sm">
                <svg viewBox="0 0 24 24" class="size-4" fill="currentColor"><path d="m12 2 2.1 2.1 3-.1.9 2.9 2.5 1.7-1 2.8 1 2.8-2.5 1.7-.9 2.9-3-.1L12 22l-2.1-2.1-3 .1-.9-2.9-2.5-1.7 1-2.8-1-2.8L6 6.9 6.9 4l3 .1L12 2Zm-1.2 13.5 5.5-5.5-1.4-1.4-4.1 4.1-1.9-1.9-1.4 1.4 3.3 3.3Z" /></svg>
                مركز موثق
            </span>
            <div class="absolute bottom-4 right-4 flex items-center gap-1.5 rounded-full bg-ink-950/75 px-3 py-1.5 text-xs font-bold text-white backdrop-blur">
                <svg viewBox="0 0 24 24" class="size-4 text-gold-400" fill="currentColor"><path d="m12 2.8 2.8 5.7 6.3.9-4.6 4.5 1.1 6.3-5.6-3-5.6 3 1.1-6.3-4.6-4.5 6.3-.9L12 2.8Z" /></svg>
                {{ center.rating.average ?? 'جديد' }}
                <span v-if="center.rating.count" class="font-medium text-white/60">({{ center.rating.count }})</span>
            </div>
        </div>

        <div class="p-5">
            <div class="mb-4">
                <h3 class="text-lg font-black leading-7 text-ink-950">{{ center.name }}</h3>
                <p class="mt-2 flex items-start gap-2 text-sm leading-6 text-[#687e83]">
                    <svg viewBox="0 0 24 24" class="mt-0.5 size-4 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></svg>
                    {{ center.address }}، {{ center.city.name }}
                </p>
            </div>

            <div class="mb-5 flex min-h-7 flex-wrap gap-2">
                <span v-for="service in center.services.slice(0, 3)" :key="service.id" class="rounded-lg bg-[#f1f5f4] px-2.5 py-1.5 text-[11px] font-bold text-[#4d666c]">{{ service.name }}</span>
                <span v-if="center.services.length > 3" class="rounded-lg bg-brand-50 px-2.5 py-1.5 text-[11px] font-black text-brand-700">+{{ center.services.length - 3 }}</span>
            </div>

            <div class="flex items-center gap-2 border-t border-[#edf1f0] pt-4">
                <RouterLink :to="{ name: 'centers.show', params: { slug: center.slug } }" class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-ink-950 text-sm font-black text-white transition hover:bg-brand-700">
                    عرض التفاصيل
                    <svg viewBox="0 0 24 24" class="size-4 rotate-180" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" /></svg>
                </RouterLink>
                <a :href="`tel:${center.phone}`" class="grid size-11 place-items-center rounded-xl border border-[#dbe5e1] text-brand-700 transition hover:bg-brand-50" aria-label="اتصل بالمركز">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2.1Z" /></svg>
                </a>
            </div>
        </div>
    </article>
</template>
