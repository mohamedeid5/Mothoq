<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { auth } from '../auth';

const route = useRoute();
const router = useRouter();
const form = reactive({ email: '', password: '' });
const errors = ref({});
const message = ref('');
const submitting = ref(false);

function safeRedirect() {
    const redirect = route.query.redirect;

    if (typeof redirect === 'string' && redirect.startsWith('/') && ! redirect.startsWith('//')) {
        return redirect;
    }

    return auth.homeForRole();
}

async function submit() {
    errors.value = {};
    message.value = '';
    submitting.value = true;

    try {
        await auth.login(form);
        await router.push(safeRedirect());
    } catch (error) {
        errors.value = error.errors ?? {};

        if (errors.value.email?.[0] === 'The provided credentials are incorrect.') {
            errors.value.email = ['بيانات الدخول غير صحيحة.'];
        }

        if (error.status === 422) {
            message.value = 'راجع البريد الإلكتروني وكلمة المرور.';
        } else if (error.status >= 500) {
            message.value = 'حدث خطأ أثناء تسجيل الدخول. حاول مرة أخرى.';
        } else {
            message.value = error.message;
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <section class="relative isolate min-h-[calc(100vh-4.5rem)] overflow-hidden bg-ink-950 px-4 py-12 sm:px-6 lg:px-8">
        <div class="absolute inset-0 -z-10 opacity-30 [background-image:linear-gradient(rgba(255,255,255,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.06)_1px,transparent_1px)] [background-size:48px_48px]"></div>
        <div class="absolute -start-24 top-20 -z-10 size-80 rounded-full bg-brand-500/20 blur-3xl"></div>

        <div class="mx-auto grid max-w-5xl items-center gap-10 lg:grid-cols-[1fr_0.85fr]">
            <div class="text-white">
                <span class="inline-flex rounded-full border border-brand-300/20 bg-brand-400/10 px-4 py-2 text-sm font-bold text-brand-200">
                    حساب واحد لإدارة كل شيء
                </span>
                <h1 class="mt-6 max-w-xl text-4xl font-black leading-tight sm:text-5xl">
                    أهلاً بعودتك إلى <span class="text-brand-300">موثوق</span>
                </h1>
                <p class="mt-5 max-w-lg text-lg leading-8 text-white/65">
                    ادخل إلى حسابك لمتابعة بيانات مركزك وإدارة حضوره على المنصة بأمان.
                </p>

                <div class="mt-8 grid max-w-lg gap-3 text-sm text-white/70 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">✓ دخول محمي باستخدام Sanctum</div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">✓ توجيه تلقائي حسب صلاحيتك</div>
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-2xl shadow-black/25 sm:p-8">
                <div>
                    <p class="text-sm font-bold text-brand-700">تسجيل الدخول</p>
                    <h2 class="mt-2 text-3xl font-black text-ink-950">ادخل بيانات حسابك</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">استخدم البريد الإلكتروني وكلمة المرور المسجلين لدينا.</p>
                </div>

                <div v-if="message" role="alert" class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">
                    {{ message }}
                </div>

                <form class="mt-7 grid gap-5" @submit.prevent="submit">
                    <label class="grid gap-2 text-sm font-bold text-ink-950">
                        البريد الإلكتروني
                        <input
                            v-model.trim="form.email"
                            type="email"
                            name="email"
                            autocomplete="email"
                            required
                            placeholder="name@example.com"
                            class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100"
                        >
                        <span v-if="errors.email" class="text-xs text-red-600">{{ errors.email[0] }}</span>
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-ink-950">
                        كلمة المرور
                        <input
                            v-model="form.password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-start font-medium outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-100"
                        >
                        <span v-if="errors.password" class="text-xs text-red-600">{{ errors.password[0] }}</span>
                    </label>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="mt-1 flex h-13 items-center justify-center gap-2 rounded-2xl bg-brand-600 px-5 font-black text-white transition hover:bg-brand-500 disabled:cursor-wait disabled:opacity-60"
                    >
                        <span v-if="submitting" class="size-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                        {{ submitting ? 'جاري تسجيل الدخول...' : 'تسجيل الدخول' }}
                    </button>
                </form>

            </div>
        </div>
    </section>
</template>
