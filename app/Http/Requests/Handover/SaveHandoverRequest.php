<?php

namespace App\Http\Requests\Handover;

use App\Enums\HandoverDestination;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('updateHandover', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destination' => ['required', Rule::enum(HandoverDestination::class)],
            'destination_detail' => ['nullable', 'string', 'max:150'],
            'documents_complete' => ['required', 'boolean'],
            'received_by_name' => ['required', 'string', 'max:150'],
            'handed_over_at' => ['required', 'date'],
            'ongoing_issues' => ['nullable', 'string', 'max:2000'],
            'follow_up_instructions' => ['nullable', 'string', 'max:2000'],
            'complete_case' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'destination' => 'tujuan pemindahan',
            'received_by_name' => 'nama petugas penerima',
            'handed_over_at' => 'waktu serah terima',
        ];
    }
}
