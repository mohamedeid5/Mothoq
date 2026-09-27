<?php

namespace App\Http\Requests\Bookings;

use App\Data\Bookings\UpdateBookingStatusData;
use App\Enums\BookingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in([
                    BookingStatus::Accepted->value,
                    BookingStatus::Rejected->value,
                    BookingStatus::Completed->value,
                ]),
            ],
            'status_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toData(): UpdateBookingStatusData
    {
        return new UpdateBookingStatusData(
            status: BookingStatus::from($this->string('status')->toString()),
            statusNote: $this->statusNote(),
        );
    }

    public function messages(): array
    {
        return [
            'status.required' => 'اختر حالة الحجز الجديدة.',
            'status.in' => 'حالة الحجز المختارة غير صالحة.',
            'status_note.max' => 'ملاحظة الحالة يجب ألا تزيد عن 1000 حرف.',
        ];
    }

    private function statusNote(): ?string
    {
        $statusNote = $this->string('status_note')->trim()->toString();

        return $statusNote !== '' ? $statusNote : null;
    }
}
