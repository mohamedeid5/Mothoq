<section class="mt-6 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7" aria-labelledby="service-durations-heading">
    <h2 id="service-durations-heading" class="text-xl font-black text-ink-950">مدة الخدمات</h2>
    <p class="mt-2 text-sm leading-7 text-slate-600">حدد مدة كل خدمة بالدقائق لإتاحتها للحجز. يلزم أيضًا تحديد سعة الحجوزات ومواعيد العمل. ترك المدة فارغة يوقف الحجز لهذه الخدمة، ولا يغير الحجوزات السابقة.</p>
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        @forelse ($serviceCenter->services as $service)
            @php($isSubmittedService = (int) old('duration_service_id') === $service->id)
            <form method="POST" action="{{ route('owner.service-centers.services.duration.update', [$serviceCenter, $service]) }}" class="grid gap-3 rounded-2xl border border-slate-200 p-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="duration_service_id" value="{{ $service->id }}">
                <label for="service-duration-{{ $service->id }}" class="text-sm font-bold text-ink-950">{{ $service->name }} — المدة بالدقائق</label>
                <input id="service-duration-{{ $service->id }}" name="duration_minutes" type="number" min="1" max="1439" step="1" inputmode="numeric" value="{{ $isSubmittedService ? old('duration_minutes') : $service->pivot->duration_minutes }}" placeholder="غير محددة — الحجز غير متاح" aria-describedby="service-duration-error-{{ $service->id }}" aria-invalid="{{ $isSubmittedService && $errors->has('duration_minutes') ? 'true' : 'false' }}" class="h-12 rounded-xl border border-slate-200 bg-slate-50 px-4 outline-none focus:border-brand-500 focus:ring-3 focus:ring-brand-100">
                <span id="service-duration-error-{{ $service->id }}" class="text-xs text-red-600">
                    @if ($isSubmittedService)
                        @error('duration_minutes'){{ $message }}@enderror
                    @endif
                </span>
                <button type="submit" class="rounded-2xl bg-brand-600 px-6 py-3 text-sm font-black text-white hover:bg-brand-500">حفظ مدة {{ $service->name }}</button>
            </form>
        @empty
            <p class="text-sm text-slate-500">اختر الخدمات التي يقدمها المركز واحفظ بياناته أولًا، ثم حدد مدة كل خدمة هنا.</p>
        @endforelse
    </div>
</section>
