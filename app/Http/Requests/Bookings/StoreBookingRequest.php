<?php

namespace App\Http\Requests\Bookings;

use App\Data\Bookings\CreateBookingData;
use App\Models\ServiceCenter;
use App\Support\LocalBookingTime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where('is_active', true),
            ],
            'customer_phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'scheduled_at' => ['required', 'string', 'date', 'regex:/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(?::\d{2}(?:\.0{1,6})?)?(?:Z|[+-](?:0\d|1[0-4]):[0-5]\d)?$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('scheduled_at')) {
                return;
            }

            $timezone = ServiceCenter::query()->where('slug', $this->route('serviceCenter'))->value('timezone');
            if ($timezone === null) {
                return;
            }

            try {
                $start = (new LocalBookingTime)->parse($this->string('scheduled_at')->toString(), $timezone);
                if ($start->lessThanOrEqualTo(now('UTC'))) {
                    $validator->errors()->add('scheduled_at', 'موعد الحجز يجب أن يكون في المستقبل.');
                }
            } catch (ValidationException $exception) {
                $validator->errors()->add('scheduled_at', $exception->errors()['scheduled_at'][0]);
            }
        }];
    }

    public function toData(): CreateBookingData
    {
        return new CreateBookingData(
            serviceId: $this->integer('service_id'),
            customerPhone: $this->string('customer_phone')->toString(),
            scheduledAt: $this->string('scheduled_at')->toString(),
            notes: $this->notes(),
        );
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'اختر الخدمة المطلوبة.',
            'service_id.exists' => 'الخدمة المختارة غير متاحة.',
            'customer_phone.required' => 'أدخل رقم الهاتف للتواصل.',
            'customer_phone.regex' => 'أدخل رقم موبايل مصري صحيحًا.',
            'scheduled_at.required' => 'اختر موعد الحجز.',
            'scheduled_at.after' => 'موعد الحجز يجب أن يكون في المستقبل.',
            'notes.max' => 'الملاحظات يجب ألا تزيد عن 2000 حرف.',
        ];
    }

    private function notes(): ?string
    {
        $notes = $this->string('notes')->trim()->toString();

        return $notes !== '' ? $notes : null;
    }
}
