<?php

namespace App\Http\Requests\Preparation;

use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveInstrumentChecksRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('managePreparation', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.instrument_id' => ['required', 'integer', 'exists:instruments,id'],
            'items.*.is_available' => ['required', 'boolean'],
            'items.*.is_sterile' => ['required', 'boolean'],
            'items.*.is_functional' => ['required', 'boolean'],
            'items.*.is_rusty' => ['required', 'boolean'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Minimal satu alat wajib ditambahkan pada daftar cek alat.',
            'items.min' => 'Minimal satu alat wajib ditambahkan pada daftar cek alat.',
        ];
    }
}
