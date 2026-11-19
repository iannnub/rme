<?php

namespace App\Http\Requests\Intraop;

use App\Enums\SkinIntegrity;
use App\Enums\SurgicalPosition;
use App\Models\IntraopRecord;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveIntraopPositionRequest extends FormRequest
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
            'position' => ['nullable', Rule::enum(SurgicalPosition::class)],
            'pressure_protection' => ['nullable', 'array'],
            'pressure_protection.*' => ['string', Rule::in(IntraopRecord::PRESSURE_PROTECTION_OPTIONS)],
            'skin_antiseptic' => ['nullable', 'array'],
            'skin_antiseptic.*' => ['string', Rule::in(IntraopRecord::SKIN_ANTISEPTIC_OPTIONS)],
            'grounding_plate_site' => ['nullable', 'string', 'max:150'],
            'skin_condition_pre' => ['nullable', Rule::enum(SkinIntegrity::class)],
            'skin_condition_post' => ['nullable', Rule::enum(SkinIntegrity::class)],
            'draping_done' => ['nullable', 'boolean'],
            'warming' => ['nullable', 'array'],
            'warming.*' => ['string', Rule::in(IntraopRecord::WARMING_OPTIONS)],
            'room_temperature_c' => ['nullable', 'numeric', 'min:10', 'max:40'],
        ];
    }
}
