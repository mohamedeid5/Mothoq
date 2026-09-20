<?php

namespace App\Http\Requests\ServiceCenters;

use App\Data\ServiceCenters\StoreCenterImageData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;

class StoreCenterImageRequest extends FormRequest
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
            'image' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max('5mb'),
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_cover' => ['sometimes', 'boolean'],
        ];
    }

    public function toData(): StoreCenterImageData
    {
        /** @var UploadedFile $image */
        $image = $this->file('image');

        return new StoreCenterImageData(
            image: $image,
            altText: $this->filled('alt_text') ? $this->string('alt_text')->toString() : null,
            isCover: $this->boolean('is_cover'),
        );
    }
}
