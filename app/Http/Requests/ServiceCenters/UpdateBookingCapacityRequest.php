<?php

namespace App\Http\Requests\ServiceCenters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingCapacityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['booking_capacity' => ['required', 'integer', 'between:1,2147483647']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'booking_capacity.required' => 'يجب تحديد سعة الحجوزات.',
            'booking_capacity.integer' => 'سعة الحجوزات يجب أن تكون عددًا صحيحًا.',
            'booking_capacity.between' => 'سعة الحجوزات يجب أن تكون بين 1 و2147483647.',
        ];
    }
}
