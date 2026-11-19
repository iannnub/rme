<?php

namespace App\Http\Requests\Intraop;

use App\Enums\BreathingPattern;
use App\Enums\Consciousness;
use App\Enums\OxygenDevice;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntraopVitalSignRequest extends FormRequest
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
            'recorded_at' => ['required', 'date'],
            'bp_systolic' => ['nullable', 'integer', 'min:20', 'max:300'],
            'bp_diastolic' => ['nullable', 'integer', 'min:10', 'max:200'],
            'pulse' => ['nullable', 'integer', 'min:10', 'max:260'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:80'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'consciousness' => ['nullable', Rule::enum(Consciousness::class)],
            'breathing_pattern' => ['required', Rule::enum(BreathingPattern::class)],
            'oxygen_device' => ['required', Rule::enum(OxygenDevice::class)],
            'oxygen_flow_lpm' => ['nullable', 'integer', 'min:1', 'max:15'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
