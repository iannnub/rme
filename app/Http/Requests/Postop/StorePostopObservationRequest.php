<?php

namespace App\Http\Requests\Postop;

use App\Enums\AirwayPatency;
use App\Enums\AnalgesicResponse;
use App\Enums\BreathingPattern;
use App\Enums\Consciousness;
use App\Enums\DressingCondition;
use App\Enums\FluidColor;
use App\Enums\FluidOdor;
use App\Enums\GeneralCondition;
use App\Enums\OxygenDevice;
use App\Enums\PainCharacter;
use App\Enums\PatientResponse;
use App\Enums\SutureCondition;
use App\Models\PostopObservation;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostopObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('updatePostop', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recorded_at' => ['required', 'date'],
            'gcs_eye' => ['required', 'integer', 'min:1', 'max:4'],
            'gcs_verbal' => ['required', 'integer', 'min:1', 'max:5'],
            'gcs_motor' => ['required', 'integer', 'min:1', 'max:6'],
            'general_condition' => ['required', Rule::enum(GeneralCondition::class)],
            'response' => ['required', Rule::enum(PatientResponse::class)],
            'airway_patency' => ['required', Rule::enum(AirwayPatency::class)],

            // TTV & Pernapasan
            'bp_systolic' => ['nullable', 'integer', 'min:20', 'max:300'],
            'bp_diastolic' => ['nullable', 'integer', 'min:10', 'max:200'],
            'pulse' => ['nullable', 'integer', 'min:10', 'max:260'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:80'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'breathing_pattern' => ['required', Rule::enum(BreathingPattern::class)],
            'oxygen_device' => ['required', Rule::enum(OxygenDevice::class)],
            'oxygen_flow_lpm' => ['nullable', 'integer', 'min:1', 'max:15'],

            // Luka
            'wound_location' => ['nullable', 'string', 'max:150'],
            'dressing' => ['nullable', Rule::enum(DressingCondition::class)],
            'bleeding_seepage' => ['nullable', 'boolean'],
            'swelling_hematoma' => ['nullable', 'boolean'],
            'suture_condition' => ['nullable', Rule::enum(SutureCondition::class)],

            // Nyeri
            'pain_nrs' => ['required', 'integer', 'min:0', 'max:10'],
            'pain_location' => ['nullable', 'string', 'max:150'],
            'pain_character' => ['nullable', Rule::enum(PainCharacter::class)],
            'pain_duration' => ['nullable', 'string', 'max:100'],
            'analgesic_response' => ['nullable', Rule::enum(AnalgesicResponse::class)],

            // Cairan & Drain (opsional per observasi)
            'fluid_intake_ml' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'urine_ml' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'drain_ml' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'drain_color' => ['nullable', Rule::enum(FluidColor::class)],
            'drain_odor' => ['nullable', Rule::enum(FluidOdor::class)],
            'bleeding_ml' => ['nullable', 'integer', 'min:0', 'max:20000'],

            // Efek anestesi & komplikasi
            'anesthesia_recovery' => ['nullable', Rule::enum(Consciousness::class)],
            'nausea_vomiting' => ['nullable', 'boolean'],
            'shivering_hypothermia' => ['nullable', 'boolean'],
            'complications' => ['nullable', 'array'],
            'complications.*' => ['string', Rule::in(PostopObservation::COMPLICATION_OPTIONS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
