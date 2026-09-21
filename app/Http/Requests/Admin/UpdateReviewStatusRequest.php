<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReviewStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewStatusRequest extends FormRequest
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
                    ReviewStatus::Published->value,
                    ReviewStatus::Rejected->value,
                ]),
            ],
        ];
    }

    public function status(): ReviewStatus
    {
        return ReviewStatus::from($this->string('status')->toString());
    }

    public function messages(): array
    {
        return [
            'status.required' => 'اختر نشر التقييم أو رفضه.',
            'status.in' => 'حالة التقييم المختارة غير صالحة.',
        ];
    }
}
