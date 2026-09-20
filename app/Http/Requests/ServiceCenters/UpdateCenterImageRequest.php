<?php

namespace App\Http\Requests\ServiceCenters;

use App\Data\ServiceCenters\UpdateCenterImageData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCenterImageRequest extends FormRequest
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
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toData(): UpdateCenterImageData
    {
        return new UpdateCenterImageData(
            altText: $this->filled('alt_text') ? $this->string('alt_text')->toString() : null,
        );
    }
}
