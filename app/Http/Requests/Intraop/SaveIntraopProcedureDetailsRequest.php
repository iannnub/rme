<?php

namespace App\Http\Requests\Intraop;

use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveIntraopProcedureDetailsRequest extends FormRequest
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
            'procedure_details' => ['nullable', 'array'],
            'procedure_details.*' => ['nullable'],
            'procedure_details.blood_transfusion_given' => ['nullable', 'boolean'],
            'implants' => ['nullable', 'array'],
            'implants.*' => ['required', 'array'],
            'implants.*.implant_type' => ['nullable', 'string', 'max:120'],
            'implants.*.size' => ['nullable', 'string', 'max:120'],
            'implants.*.planned_qty' => ['nullable', 'string', 'max:50'],
            'implants.*.brand' => ['nullable', 'string', 'max:120'],
            'implants.*.serial_lot' => ['nullable', 'string', 'max:120'],
            'implants.*.placement_site' => ['nullable', 'string', 'max:200'],
            'implants.*.used_qty' => ['nullable', 'string', 'max:50'],
            'implants.*.unused_doc' => ['nullable', 'string', 'max:500'],
        ];
    }
}
