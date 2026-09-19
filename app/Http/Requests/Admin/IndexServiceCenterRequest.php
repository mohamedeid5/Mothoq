<?php

namespace App\Http\Requests\Admin;

use App\Enums\ServiceCenterStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexServiceCenterRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(ServiceCenterStatus::class)],
            'verified' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): ?string
    {
        return $this->filled('search') ? $this->string('search')->toString() : null;
    }

    public function status(): ?ServiceCenterStatus
    {
        return $this->filled('status')
            ? ServiceCenterStatus::from($this->string('status')->toString())
            : null;
    }

    public function verified(): ?bool
    {
        return $this->has('verified') && $this->input('verified') !== ''
            ? $this->boolean('verified')
            : null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => Str::squish($this->string('search')->toString()),
        ]);
    }
}
