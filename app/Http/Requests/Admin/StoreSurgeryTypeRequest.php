<?php

namespace App\Http\Requests\Admin;

use App\Models\SurgeryType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSurgeryTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $specialty = $this->input('specialty');

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'specialty' => is_string($specialty) && trim($specialty) !== '' ? trim($specialty) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(SurgeryType::class, 'name')->ignore($this->targetId())],
            'specialty' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function targetId(): mixed
    {
        return null;
    }
}
