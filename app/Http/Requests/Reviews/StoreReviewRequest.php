<?php

namespace App\Http\Requests\Reviews;

use App\Data\Reviews\StoreReviewData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function toData(): StoreReviewData
    {
        return new StoreReviewData(
            rating: $this->integer('rating'),
            comment: $this->comment(),
        );
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'اختر تقييمًا من نجمة إلى خمس نجوم.',
            'rating.integer' => 'التقييم يجب أن يكون رقمًا صحيحًا.',
            'rating.between' => 'التقييم يجب أن يكون من نجمة إلى خمس نجوم.',
            'comment.max' => 'التعليق يجب ألا يزيد عن 2000 حرف.',
        ];
    }

    private function comment(): ?string
    {
        $comment = $this->string('comment')->trim()->toString();

        return $comment !== '' ? $comment : null;
    }
}
