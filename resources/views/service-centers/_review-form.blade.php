@php
    $isEditing = $viewerReview !== null;
    $selectedRating = (int) old('rating', $viewerReview?->rating ?? 5);
@endphp

<section class="rounded-3xl border border-[#e0e8e5] bg-white p-6 sm:p-7">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-ink-950">{{ $isEditing ? 'تقييمك للمركز' : 'قيّم تجربتك' }}</h2>
            <p class="mt-2 text-sm leading-6 text-[#687d82]">تقييمك يظهر للزوار بعد مراجعته من الإدارة.</p>
        </div>
        @if ($isEditing)
            <span @class([
                'rounded-full px-3 py-1.5 text-xs font-black',
                'bg-amber-50 text-amber-700' => $viewerReview->status === App\Enums\ReviewStatus::Pending,
                'bg-emerald-50 text-emerald-700' => $viewerReview->status === App\Enums\ReviewStatus::Published,
                'bg-red-50 text-red-700' => $viewerReview->status === App\Enums\ReviewStatus::Rejected,
            ])>{{ $viewerReview->status->label() }}</span>
        @endif
    </div>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('reviews.update', $viewerReview) : route('reviews.store', $serviceCenter->slug) }}" class="mt-5 grid gap-4">
        @csrf
        @if ($isEditing)
            @method('PATCH')
        @endif

        <fieldset>
            <legend class="mb-2 text-sm font-black text-slate-700">عدد النجوم</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (range(5, 1) as $rating)
                    <label class="cursor-pointer">
                        <input type="radio" name="rating" value="{{ $rating }}" class="peer sr-only" @checked($selectedRating === $rating)>
                        <span class="grid size-11 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-lg text-slate-400 transition peer-checked:border-gold-400 peer-checked:bg-amber-50 peer-checked:text-gold-400">★</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <label class="grid gap-2 text-sm font-black text-slate-700">
            تعليقك <span class="font-medium text-slate-400">(اختياري)</span>
            <textarea name="comment" rows="4" maxlength="2000" placeholder="احكِ لنا عن جودة الخدمة والتعامل..." class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal leading-7 outline-none focus:border-brand-500">{{ old('comment', $viewerReview?->comment) }}</textarea>
        </label>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-black text-white hover:bg-brand-500">
                {{ $isEditing ? 'حفظ التعديل' : 'إرسال التقييم' }}
            </button>
        </div>
    </form>

    @if ($isEditing)
        <form method="POST" action="{{ route('reviews.destroy', $viewerReview) }}" class="mt-3" onsubmit="return confirm('هل تريد حذف تقييمك؟')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-black text-red-600 hover:text-red-500">حذف التقييم</button>
        </form>
    @endif
</section>
