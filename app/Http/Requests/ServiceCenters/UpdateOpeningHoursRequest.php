<?php

namespace App\Http\Requests\ServiceCenters;

use App\Data\ServiceCenters\UpdateOpeningHoursData;
use App\Enums\DayOfWeek;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOpeningHoursRequest extends FormRequest
{
    public function toData(): UpdateOpeningHoursData
    {
        return UpdateOpeningHoursData::fromArray($this->validated());
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
            'opening_hours' => ['required', 'array', 'list', 'size:7'],
            'opening_hours.*' => ['required', 'array:day,is_closed,opens_at,closes_at'],
            'opening_hours.*.day' => [
                'required',
                'distinct:strict',
                Rule::enum(DayOfWeek::class),
            ],
            'opening_hours.*.is_closed' => ['required', 'boolean'],
            'opening_hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $openingHours = $this->input('opening_hours', []);

            if (! is_array($openingHours)) {
                return;
            }

            foreach ($openingHours as $index => $openingHour) {
                if (! is_array($openingHour)) {
                    continue;
                }

                $isClosed = filter_var(
                    $openingHour['is_closed'] ?? null,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                );

                if ($isClosed !== false) {
                    continue;
                }

                $opensAt = $openingHour['opens_at'] ?? null;
                $closesAt = $openingHour['closes_at'] ?? null;

                if (blank($opensAt)) {
                    $validator->errors()->add(
                        "opening_hours.{$index}.opens_at",
                        'وقت الفتح مطلوب في الأيام المفتوحة.',
                    );
                }

                if (blank($closesAt)) {
                    $validator->errors()->add(
                        "opening_hours.{$index}.closes_at",
                        'وقت الإغلاق مطلوب في الأيام المفتوحة.',
                    );
                }

                if (
                    filled($opensAt)
                    && filled($closesAt)
                    && ! $validator->errors()->has("opening_hours.{$index}.opens_at")
                    && ! $validator->errors()->has("opening_hours.{$index}.closes_at")
                    && $closesAt <= $opensAt
                ) {
                    $validator->errors()->add(
                        "opening_hours.{$index}.closes_at",
                        'وقت الإغلاق يجب أن يكون بعد وقت الفتح.',
                    );
                }
            }
        }];
    }
}
