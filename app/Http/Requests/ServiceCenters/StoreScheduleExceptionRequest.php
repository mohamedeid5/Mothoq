<?php

namespace App\Http\Requests\ServiceCenters;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'is_closed' => ['required', 'boolean'],
            'opens_at' => ['nullable', 'required_if:is_closed,0,false', 'prohibited_if:is_closed,1,true', 'date_format:H:i'],
            'closes_at' => ['nullable', 'required_if:is_closed,0,false', 'prohibited_if:is_closed,1,true', 'date_format:H:i', 'after:opens_at'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'closes_at.after' => 'وقت الإغلاق يجب أن يكون بعد وقت الفتح في اليوم نفسه. المواعيد الممتدة لليوم التالي غير مدعومة.',
            'opens_at.prohibited_if' => 'اليوم المغلق لا يقبل وقت فتح.',
            'closes_at.prohibited_if' => 'اليوم المغلق لا يقبل وقت إغلاق.',
        ];
    }
}
