<?php

namespace App\Http\Requests\Web\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PasswordResetLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:255']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'أدخل البريد الإلكتروني.',
            'email.email' => 'أدخل بريدًا إلكترونيًا صحيحًا.',
            'email.max' => 'البريد الإلكتروني يجب ألا يزيد عن 255 حرفًا.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower($this->string('email')->trim()->toString())]);
    }
}
