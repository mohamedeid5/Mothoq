<script setup>
defineProps({
    modelValue: { type: Object, required: true },
    governorates: { type: Array, default: () => [] },
    cities: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    carBrands: { type: Array, default: () => [] },
    loadingCities: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'apply', 'reset']);
</script>

<template>
    <form class="rounded-3xl border border-[#dfe7e4] bg-white p-5 surface-shadow" @submit.prevent="$emit('apply')">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-brand-700">خصص بحثك</p>
                <h2 class="mt-1 text-lg font-black text-ink-950">فلترة النتائج</h2>
            </div>
            <span class="grid size-10 place-items-center rounded-xl bg-brand-50 text-brand-700">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M4 6h16M7 12h10M10 18h4" />
                </svg>
            </span>
        </div>

        <div class="grid gap-4">
            <label class="grid gap-2 text-sm font-bold text-[#29464d]">
                المحافظة
                <select
                    :value="modelValue.governorate"
                    class="h-12 w-full rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3 text-sm font-medium outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100"
                    @change="$emit('update:modelValue', { ...modelValue, governorate: $event.target.value, city: '' })"
                >
                    <option value="">كل المحافظات</option>
                    <option v-for="item in governorates" :key="item.id" :value="item.slug">{{ item.name }}</option>
                </select>
            </label>

            <label class="grid gap-2 text-sm font-bold text-[#29464d]">
                المدينة
                <select
                    :value="modelValue.city"
                    :disabled="! modelValue.governorate || loadingCities"
                    class="h-12 w-full rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3 text-sm font-medium outline-none transition disabled:cursor-not-allowed disabled:opacity-50 focus:border-brand-500 focus:ring-3 focus:ring-brand-100"
                    @change="$emit('update:modelValue', { ...modelValue, city: $event.target.value })"
                >
                    <option value="">{{ loadingCities ? 'جاري تحميل المدن...' : 'كل المدن' }}</option>
                    <option v-for="item in cities" :key="item.id" :value="item.slug">{{ item.name }}</option>
                </select>
            </label>

            <label class="grid gap-2 text-sm font-bold text-[#29464d]">
                نوع الخدمة
                <select
                    :value="modelValue.service"
                    class="h-12 w-full rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3 text-sm font-medium outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100"
                    @change="$emit('update:modelValue', { ...modelValue, service: $event.target.value })"
                >
                    <option value="">كل الخدمات</option>
                    <option v-for="item in services" :key="item.id" :value="item.slug">{{ item.name }}</option>
                </select>
            </label>

            <label class="grid gap-2 text-sm font-bold text-[#29464d]">
                ماركة السيارة
                <select
                    :value="modelValue.car_brand"
                    class="h-12 w-full rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-3 text-sm font-medium outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100"
                    @change="$emit('update:modelValue', { ...modelValue, car_brand: $event.target.value })"
                >
                    <option value="">كل الماركات</option>
                    <option v-for="item in carBrands" :key="item.id" :value="item.slug">{{ item.name }}</option>
                </select>
            </label>

            <label class="flex cursor-pointer items-center justify-between rounded-xl border border-[#dbe5e1] bg-[#fafcfb] px-4 py-3.5">
                <span>
                    <span class="block text-sm font-bold text-[#29464d]">مراكز موثقة فقط</span>
                    <span class="mt-0.5 block text-xs text-[#6a7f84]">راجعنا بيانات المركز</span>
                </span>
                <input
                    type="checkbox"
                    :checked="modelValue.verified === true"
                    class="size-5 accent-brand-600"
                    @change="$emit('update:modelValue', { ...modelValue, verified: $event.target.checked ? true : '' })"
                >
            </label>
        </div>

        <div class="mt-5 grid grid-cols-[1fr_auto] gap-2">
            <button type="submit" :disabled="loading" class="h-12 rounded-xl bg-ink-950 px-5 text-sm font-black text-white transition hover:bg-brand-700 disabled:opacity-60">
                {{ loading ? 'جاري البحث...' : 'عرض النتائج' }}
            </button>
            <button type="button" class="h-12 rounded-xl border border-[#dbe5e1] px-4 text-sm font-bold text-[#5b7075] transition hover:bg-[#f4f7f6]" @click="$emit('reset')">
                مسح
            </button>
        </div>
    </form>
</template>
