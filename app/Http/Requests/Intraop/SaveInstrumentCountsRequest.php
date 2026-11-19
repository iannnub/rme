<?php

namespace App\Http\Requests\Intraop;

use App\Enums\CountVerification;
use App\Models\IntraopRecord;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveInstrumentCountsRequest extends FormRequest
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
            'counts' => ['required', 'array'],
            'counts.*.initial_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'counts.*.additional_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'counts.*.final_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'count_verification' => ['nullable', Rule::enum(CountVerification::class)],
            'count_discrepancy_actions' => ['nullable', 'array'],
            'count_discrepancy_actions.*' => ['string', Rule::in(IntraopRecord::COUNT_DISCREPANCY_ACTIONS)],
        ];
    }
}
