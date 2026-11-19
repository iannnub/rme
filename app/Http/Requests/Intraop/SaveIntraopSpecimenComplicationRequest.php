<?php

namespace App\Http\Requests\Intraop;

use App\Enums\SpecimenPreservative;
use App\Enums\SpecimenPurpose;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveIntraopSpecimenComplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('updateIntraopComplication', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expected_updated_at' => ['nullable', 'string'],
            'has_specimen' => ['nullable', 'boolean'],
            'specimen_type' => ['nullable', 'string', 'max:150'],
            'origin_organ' => ['nullable', 'string', 'max:150'],
            'purpose' => ['nullable', Rule::enum(SpecimenPurpose::class)],
            'preservative' => ['nullable', Rule::enum(SpecimenPreservative::class)],
            'has_complication' => ['required', 'boolean'],
            'complication_description' => ['nullable', 'string', 'max:2000'],
            'complication_handling' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
