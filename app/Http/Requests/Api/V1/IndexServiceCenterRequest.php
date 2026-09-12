<?php

namespace App\Http\Requests\Api\V1;

use App\Models\City;
use App\Queries\ServiceCenters\PublicServiceCenterFilters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexServiceCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toFilters(): PublicServiceCenterFilters
    {
        $validated = $this->validated();

        return new PublicServiceCenterFilters(
            governorate: isset($validated['governorate']) ? (string) $validated['governorate'] : null,
            city: isset($validated['city']) ? (string) $validated['city'] : null,
            service: isset($validated['service']) ? (string) $validated['service'] : null,
            carBrand: isset($validated['car_brand']) ? (string) $validated['car_brand'] : null,
            verified: array_key_exists('verified', $validated) ? $this->boolean('verified') : null,
            page: isset($validated['page']) ? (int) $validated['page'] : 1,
            perPage: isset($validated['per_page']) ? (int) $validated['per_page'] : 15,
        );
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
                'nullable',
                'string',
                'max:255',
                Rule::exists('governorates', 'slug')->where('is_active', true),
                'required_with:city',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('cities', 'slug')->where('is_active', true),
            ],
            'service' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('services', 'slug')->where('is_active', true),
            ],
            'car_brand' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('car_brands', 'slug')->where('is_active', true),
            ],
            'verified' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['governorate', 'city']) || ! $this->filled('city')) {
                    return;
                }

                $cityExistsInGovernorate = City::query()
                    ->where('slug', $this->string('city')->toString())
                    ->where('is_active', true)
                    ->whereHas('governorate', fn (Builder $query): Builder => $query
                        ->where('slug', $this->string('governorate')->toString())
                        ->where('is_active', true))
                    ->exists();

                if (! $cityExistsInGovernorate) {
                    $validator->errors()->add('city', 'المدينة المحددة لا تتبع المحافظة المختارة.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'governorate.required_with' => 'يجب تحديد المحافظة عند اختيار المدينة.',
            'governorate.exists' => 'المحافظة المحددة غير متاحة.',
            'city.exists' => 'المدينة المحددة غير متاحة.',
            'service.exists' => 'الخدمة المحددة غير متاحة.',
            'car_brand.exists' => 'ماركة السيارة المحددة غير متاحة.',
            'verified.boolean' => 'قيمة التوثيق يجب أن تكون صحيحة أو خاطئة.',
            'per_page.between' => 'عدد النتائج في الصفحة يجب أن يكون بين 1 و50.',
        ];
    }
}
