@props(['center'])

<article class="group overflow-hidden rounded-3xl border border-[#e0e8e5] bg-white transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-[0_24px_55px_-35px_rgba(10,32,38,.45)]">
    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-[#d8eee7] via-[#eef6f3] to-[#d9e2df]">
        <div class="absolute inset-0 grid place-items-center text-brand-800/60">
            <svg viewBox="0 0 120 70" class="w-32" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 48V32l9-16h55l16 16v16M10 48h100M28 48a9 9 0 1 0 18 0M76 48a9 9 0 1 0 18 0M31 25h47M57 17v15" /><path d="M51 7h18v10H51zM57 7V2h6v5" /></svg>
        </div>
        @if ($center->coverImage)
            <img src="{{ Storage::url($center->coverImage->path) }}" alt="{{ $center->coverImage->alt_text ?: $center->name }}" class="relative h-full w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.remove()">
        @endif
        <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-ink-950/65 to-transparent"></div>
        @if ($center->verified_at)
            <span class="absolute right-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-black text-brand-700 shadow-sm">✓ مركز موثق</span>
        @endif
        <div class="absolute bottom-4 right-4 flex items-center gap-1.5 rounded-full bg-ink-950/75 px-3 py-1.5 text-xs font-bold text-white backdrop-blur">
            <span class="text-gold-400">★</span>
            {{ $center->published_reviews_avg_rating ? round((float) $center->published_reviews_avg_rating, 1) : 'جديد' }}
            @if ($center->published_reviews_count)
                <span class="font-medium text-white/60">({{ $center->published_reviews_count }})</span>
            @endif
        </div>
    </div>

    <div class="p-5">
        <h3 class="text-lg font-black leading-7 text-ink-950">{{ $center->name }}</h3>
        <p class="mt-2 flex items-start gap-2 text-sm leading-6 text-[#687e83]">⌖ {{ $center->address }}، {{ $center->city->name }}</p>
        <div class="my-5 flex min-h-7 flex-wrap gap-2">
            @foreach ($center->services->take(3) as $service)
                <span class="rounded-lg bg-[#f1f5f4] px-2.5 py-1.5 text-[11px] font-bold text-[#4d666c]">{{ $service->name }}</span>
            @endforeach
            @if ($center->services->count() > 3)
                <span class="rounded-lg bg-brand-50 px-2.5 py-1.5 text-[11px] font-black text-brand-700">+{{ $center->services->count() - 3 }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2 border-t border-[#edf1f0] pt-4">
            <a href="{{ route('service-centers.show', $center->slug) }}" class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-ink-950 text-sm font-black text-white transition hover:bg-brand-700">عرض التفاصيل ←</a>
            <a href="tel:{{ $center->phone }}" class="grid size-11 place-items-center rounded-xl border border-[#dbe5e1] text-brand-700 transition hover:bg-brand-50" aria-label="اتصل بالمركز">☎</a>
        </div>
    </div>
</article>
