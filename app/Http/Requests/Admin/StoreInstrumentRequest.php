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

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'category' => is_string($category) && trim($category) !== '' ? trim($category) : null,
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
        ];
    }

    protected function targetId(): mixed
    {
        return null;
    }
}
