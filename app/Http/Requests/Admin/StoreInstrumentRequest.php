<?php

namespace App\Http\Requests\Admin;

use App\Models\Instrument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstrumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $category = $this->input('category');
        $profile = $this->input('surgery_profile');
        $description = $this->input('description');

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'category' => is_string($category) && trim($category) !== '' ? trim($category) : null,
            'surgery_profile' => is_string($profile) && trim($profile) !== '' ? trim($profile) : 'semua',
            'description' => is_string($description) && trim($description) !== '' ? trim($description) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Instrument::class, 'name')->ignore($this->targetId())],
            'category' => ['nullable', 'string', 'max:100'],
            'surgery_profile' => ['nullable', Rule::in(['semua', 'bedah_saraf', 'bedah_ortopedi'])],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function targetId(): mixed
    {
        return null;
    }
}
