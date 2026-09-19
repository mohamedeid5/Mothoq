<?php

namespace App\Http\Requests\ServiceCenters;

use App\Data\ServiceCenters\UpdateServiceCenterData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceCenterRequest extends FormRequest
{
    public function toData(): UpdateServiceCenterData
    {
        return UpdateServiceCenterData::fromArray($this->validated());
    }

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
            'governorate' => [
                'sometimes',
                'required',
                'string',
                Rule::exists('governorates', 'slug')->where('is_active', true),
            ],
            'city_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('is_active', true),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'required', 'string', 'max:30'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'required', 'string', 'max:500'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }
}
