<?php

namespace App\Http\Requests\Intraop;

use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveIntraopChronologyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('updateIntraop', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expected_updated_at' => ['nullable', 'string'],
            'anesthesia_type_id' => ['nullable', 'integer', 'exists:anesthesia_types,id'],
            'operation_plan_actual' => ['nullable', 'string', 'max:255'],
            'postop_diagnosis' => ['nullable', 'string', 'max:255'],
            'entered_or_at' => ['nullable', 'date'],
            'anesthesia_start_at' => ['nullable', 'date'],
            'incision_at' => ['nullable', 'date'],
            'surgery_end_at' => ['nullable', 'date'],
            'left_or_at' => ['nullable', 'date'],
            'anesthesia_end_at' => ['nullable', 'date'],
        ];
    }
}
