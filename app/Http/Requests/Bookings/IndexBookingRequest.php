<?php

namespace App\Http\Requests\Bookings;

use App\Enums\BookingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexBookingRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'date' => ['nullable', Rule::date()->format('Y-m-d')],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): ?string
    {
        return $this->filled('search') ? $this->string('search')->toString() : null;
    }

    public function status(): ?BookingStatus
    {
        return $this->filled('status')
            ? BookingStatus::from($this->string('status')->toString())
            : null;
    }

    public function scheduledDate(): ?string
    {
        return $this->filled('date') ? $this->string('date')->toString() : null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => Str::squish($this->string('search')->toString()),
        ]);
    }
}
