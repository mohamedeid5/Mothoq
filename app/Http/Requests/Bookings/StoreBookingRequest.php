<?php

namespace App\Http\Requests\Bookings;

use App\Data\Bookings\CreateBookingData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'scheduled_at' => ['bail', 'required', 'string', 'date_format:Y-m-d\\TH:i,Y-m-d\\TH:i:s,Y-m-d H:i,Y-m-d H:i:s', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
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
            'scheduled_at.date_format' => 'أدخل موعدًا بتوقيت مصر بدون إزاحة زمنية، مثل 2026-09-25T09:00.',
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
