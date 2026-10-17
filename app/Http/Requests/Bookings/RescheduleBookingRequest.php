<?php

namespace App\Http\Requests\Bookings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'scheduled_at' => ['bail', 'required', 'string', 'date_format:Y-m-d\\TH:i,Y-m-d\\TH:i:s,Y-m-d H:i,Y-m-d H:i:s', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.required' => 'اختر موعد الحجز.',
            'scheduled_at.date_format' => 'أدخل موعدًا بتوقيت مصر بدون إزاحة زمنية، مثل 2026-09-25T09:00.',
            'scheduled_at.after' => 'موعد الحجز يجب أن يكون في المستقبل.',
        ];
    }
}
