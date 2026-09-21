@extends('layouts.app')

@section('title', 'إدارة التقييمات | موثوق')

@section('content')
    <section class="min-h-[calc(100vh-4.5rem)] bg-sand-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <nav class="flex items-center gap-2 text-sm font-bold text-slate-500"><a href="{{ route('admin.dashboard') }}" class="hover:text-brand-700">لوحة الإدارة</a><span>/</span><span class="text-ink-950">التقييمات</span></nav>
            <header class="mt-6 rounded-[2rem] bg-ink-950 p-7 text-white sm:p-9"><p class="text-sm font-bold text-brand-300">مراجعة المحتوى</p><h1 class="mt-2 text-3xl font-black sm:text-4xl">تقييمات العملاء</h1><p class="mt-3 text-sm text-white/60">راجع التقييمات الجديدة قبل ظهورها في صفحات مراكز الصيانة.</p></header>

            @if (session('success'))
                <div role="status" class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
            @endif

            <form method="GET" action="{{ route('admin.reviews.index') }}" class="mt-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_14rem_auto]">
                <label class="grid gap-2 text-sm font-bold text-slate-700">بحث
                    <input name="search" value="{{ request('search') }}" placeholder="العميل أو المركز أو نص التعليق" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500">
                </label>
                <label class="grid gap-2 text-sm font-bold text-slate-700">حالة التقييم
                    <select name="status" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-3">
                        <option value="">كل الحالات</option>
                        @foreach (App\Enums\ReviewStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="flex items-end gap-2"><button class="h-12 rounded-xl bg-brand-600 px-5 text-sm font-black text-white hover:bg-brand-500">تطبيق</button><a href="{{ route('admin.reviews.index') }}" class="grid h-12 place-items-center rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-600">مسح</a></div>
            </form>

            <div class="mt-6 grid gap-4">
                @forelse ($reviews as $review)
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-3"><strong class="text-ink-950">{{ $review->user->name }}</strong><span class="text-gold-400">★ {{ $review->rating }}/5</span><span @class(['rounded-full px-3 py-1 text-xs font-black', 'bg-amber-50 text-amber-700' => $review->status === App\Enums\ReviewStatus::Pending, 'bg-emerald-50 text-emerald-700' => $review->status === App\Enums\ReviewStatus::Published, 'bg-red-50 text-red-700' => $review->status === App\Enums\ReviewStatus::Rejected])>{{ $review->status->label() }}</span></div>
                                <p class="mt-2 text-sm text-slate-500">مركز: @if ($review->serviceCenter->trashed())<span class="font-bold text-slate-500">{{ $review->serviceCenter->name }} (محذوف)</span>@else<a href="{{ route('admin.service-centers.show', $review->serviceCenter) }}" class="font-bold text-brand-700">{{ $review->serviceCenter->name }}</a>@endif · {{ $review->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <span class="text-xs text-slate-400">{{ $review->user->email }}</span>
                        </div>
                        <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm leading-7 text-slate-700">{{ $review->comment ?: 'لم يكتب العميل تعليقًا.' }}</p>
                        <form method="POST" action="{{ route('admin.reviews.status.update', $review) }}" class="mt-4 flex flex-wrap gap-2">
                            @csrf
                            @method('PATCH')
                            <button name="status" value="{{ App\Enums\ReviewStatus::Published->value }}" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-500">نشر</button>
                            <button name="status" value="{{ App\Enums\ReviewStatus::Rejected->value }}" class="rounded-xl bg-red-50 px-5 py-2.5 text-sm font-black text-red-700 hover:bg-red-100">رفض</button>
                        </form>
                    </article>
                @empty
                    <div class="rounded-3xl border border-slate-200 bg-white px-5 py-14 text-center text-slate-500">لا توجد تقييمات مطابقة للفلاتر.</div>
                @endforelse
            </div>

            @if ($reviews->hasPages())
                <div class="mt-6">{{ $reviews->links() }}</div>
            @endif
        </div>
    </section>
@endsection
