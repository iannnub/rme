<?php

namespace App\Http\Requests\Ssc;

use App\Enums\SignOutStatus;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSscSignOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('updateSignOut', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expected_updated_at' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(SignOutStatus::class)],
            'q1_procedure_confirmed' => ['nullable', 'boolean'],
            'q2_counts_correct' => ['nullable', 'boolean'],
            'q3_specimen_labeled' => ['nullable', Rule::in(['ya', 'tidak', 'tidak_berlaku'])],
            'q4_equipment_problem' => ['nullable', 'boolean'],
            'q5_recovery_discussed' => ['nullable', 'boolean'],
            'extra_6_procedure_matches' => ['nullable', Rule::in(['sesuai', 'belum_sesuai'])],
            'extra_7_changes_recorded' => ['nullable', Rule::in(['sudah', 'belum', 'tidak_ada_perubahan'])],
            'extra_8_discrepancy_followup' => ['nullable', Rule::in(['sudah', 'belum', 'tidak_ada_selisih'])],
            'extra_9_specimen_matches' => ['nullable', Rule::in(['sesuai', 'belum_sesuai', 'tidak_ada_spesimen'])],
            'extra_10_equipment_issue_recorded' => ['nullable', Rule::in(['sudah', 'belum', 'tidak_ada_masalah'])],
            'extra_11_complication_communicated' => ['nullable', Rule::in(['sudah', 'belum', 'tidak_ada_komplikasi'])],
            'extra_12_postop_monitoring_discussed' => ['nullable', Rule::in(['sudah', 'belum'])],
            'extra_13_devices_instructions' => ['nullable', Rule::in(['sudah', 'belum', 'tidak_berlaku'])],
            'recovery_notes' => ['nullable', 'string', 'max:2000'],
            'issues_and_actions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
