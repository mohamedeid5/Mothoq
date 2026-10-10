<?php

namespace App\Http\Requests\ServiceCenters;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceDurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return ['duration_minutes' => ['present', 'nullable', 'integer', 'between:1,1439']];
    }
}
