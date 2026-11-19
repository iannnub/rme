<?php

namespace App\Http\Requests\Cases;

use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvanceCaseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase && ($this->user()?->can('advanceStatus', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_status' => ['required', Rule::enum(CaseStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
