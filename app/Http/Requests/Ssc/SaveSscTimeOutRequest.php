<?php

namespace App\Http\Requests\Ssc;

use App\Enums\SscStatus;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSscTimeOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('updateTimeOut', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expected_updated_at' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(SscStatus::class)],
            'intro_circulating' => ['nullable', 'boolean'],
            'intro_scrub' => ['nullable', 'boolean'],
            'intro_anesthesia_nurse' => ['nullable', 'boolean'],
            'intro_surgeon' => ['nullable', 'boolean'],
            'intro_anesthesiologist' => ['nullable', 'boolean'],
            'identity_procedure_site_confirmed' => ['nullable', 'boolean'],
            'antibiotic_prophylaxis' => ['nullable', Rule::in(['ya', 'na'])],
            'antibiotic_name' => ['nullable', 'string', 'max:150'],
            'antibiotic_given_at' => ['nullable', 'date'],
            'antibiotic_confirmed_by' => ['nullable', 'string', 'max:150'],
            'surgeon_critical_steps' => ['nullable', Rule::in(['ya', 'tidak'])],
            'surgeon_critical_steps_text' => ['nullable', 'string', 'max:2000'],
            'surgeon_estimated_duration_min' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'surgeon_estimated_blood_loss_ml' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'anesthesia_special_condition' => ['nullable', Rule::in(['ya', 'tidak'])],
            'anesthesia_risk' => ['nullable', Rule::in(['ya', 'tidak'])],
            'anesthesia_note' => ['nullable', 'string', 'max:2000'],
            'nurse_sterility_confirmed' => ['nullable', 'boolean'],
            'nurse_indicator_checked' => ['nullable', 'boolean'],
            'equipment_problem' => ['nullable', Rule::in(['ya', 'tidak'])],
            'other_concern' => ['nullable', Rule::in(['ya', 'tidak'])],
            'nurse_note' => ['nullable', 'string', 'max:2000'],
            'imaging_needed' => ['nullable', Rule::in(['ya', 'tidak'])],
            'imaging_displayed' => ['nullable', Rule::in(['ya', 'tidak'])],
            'imaging_matches' => ['nullable', Rule::in(['ya', 'tidak'])],
        ];
    }
}
