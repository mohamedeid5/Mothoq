<?php

namespace App\Http\Requests\ServiceCenters;

use App\Data\ServiceCenters\ReorderCenterImagesData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderCenterImagesRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*.id' => [
                'required',
                'integer',
                'distinct',
            ],
            'images.*.sort_order' => ['required', 'integer', 'min:0', 'max:9', 'distinct'],
        ];
    }

    public function toData(): ReorderCenterImagesData
    {
        return ReorderCenterImagesData::fromArray($this->validated());
    }
}
