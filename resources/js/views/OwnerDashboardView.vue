<script setup>
import { onMounted, ref } from 'vue';
import { auth } from '../auth';
import { ownerApi } from '../api';

const centers = ref([]);
const loading = ref(true);
const errorMessage = ref('');

onMounted(async () => {
    try {
        const response = await ownerApi.serviceCenters(auth.state.token);
        centers.value = response.data;
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        loading.value = false;
    }
});

const statusLabels = {
    draft: 'مسودة',
    pending: 'قيد المراجعة',
    published: 'منشور',
    rejected: 'مرفوض',
};
</script>

<template>
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="rounded-[2rem] bg-ink-950 p-7 text-white sm:p-10">
                <p class="text-sm font-bold text-brand-300">لوحة صاحب المركز</p>
                <h1 class="mt-2 text-3xl font-black sm:text-4xl">أهلاً، {{ auth.state.user?.name }}</h1>
                <p class="mt-3 max-w-2xl text-white/60">تابع مراكزك وحالة نشر بياناتها من مكان واحد.</p>
            </div>

            <div class="mt-8">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-brand-700">مراكزك</p>
                        <h2 class="mt-1 text-2xl font-black text-ink-950">مراكز الصيانة المسجلة</h2>
                    </div>
                    <span class="rounded-full bg-brand-50 px-4 py-2 text-sm font-black text-brand-700">{{ centers.length }} مركز</span>
                </div>

                <div v-if="loading" class="mt-6 rounded-3xl bg-white p-8 text-center text-slate-500">جاري تحميل مراكزك...</div>
                <div v-else-if="errorMessage" role="alert" class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-6 text-red-700">{{ errorMessage }}</div>
                <div v-else-if="centers.length === 0" class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
                    <h3 class="text-xl font-black text-ink-950">لا يوجد مركز مرتبط بحسابك بعد</h3>
                    <p class="mt-2 text-slate-500">تواصل مع الإدارة لإضافة مركزك إلى منصة موثوق.</p>
                </div>
                <div v-else class="mt-6 grid gap-5 md:grid-cols-2">
                    <article v-for="center in centers" :key="center.id" class="rounded-3xl bg-white p-6 surface-shadow">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-xl font-black text-ink-950">{{ center.name }}</h3>
                                <p class="mt-2 text-sm text-slate-500">{{ center.city.name }}، {{ center.city.governorate.name }}</p>
                            </div>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-black text-brand-700">
                                {{ statusLabels[center.status] ?? center.status }}
                            </span>
                        </div>
                        <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-5 text-sm">
                            <span :class="center.is_verified ? 'text-brand-700' : 'text-slate-400'">
                                {{ center.is_verified ? '✓ مركز موثق' : 'بانتظار التوثيق' }}
                            </span>
                            <RouterLink :to="{ name: 'centers.show', params: { slug: center.slug } }" class="font-black text-ink-950 hover:text-brand-700">
                                عرض الصفحة ←
                            </RouterLink>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
