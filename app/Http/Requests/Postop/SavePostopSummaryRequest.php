<?php

namespace App\Http\Requests\Postop;

use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SavePostopSummaryRequest extends FormRequest
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
            'ready_to_transfer' => ['required', 'boolean'],
            'pre_transfer_condition_notes' => ['nullable', 'string', 'max:2000'],
            'assessed_at' => ['nullable', 'date'],
        ];
    }
}
