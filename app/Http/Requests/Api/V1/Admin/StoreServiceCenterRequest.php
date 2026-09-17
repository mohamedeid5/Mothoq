<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Data\ServiceCenters\CreateServiceCenterData;
use App\Enums\UserRole;
use App\Models\ServiceCenter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceCenterRequest extends FormRequest
{
    public function toData(): CreateServiceCenterData
    {
        return CreateServiceCenterData::fromArray($this->validated());
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ServiceCenter::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->whereIn('role', [UserRole::Customer->value, UserRole::CenterOwner->value])),
            ],
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where('is_active', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }
}
