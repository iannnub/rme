<?php

namespace App\Http\Requests\Postop;

use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CorrectPostopByDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('correctMedical', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pre_transfer_condition_notes' => ['required', 'string', 'max:2000'],
            'operation_plan_actual' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pre_transfer_condition_notes' => 'catatan kondisi pasca-operasi',
            'reason' => 'alasan koreksi dokter',
        ];
    }
}
