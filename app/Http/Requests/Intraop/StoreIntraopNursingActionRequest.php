<?php

namespace App\Http\Requests\Intraop;

use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntraopNursingActionRequest extends FormRequest
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
            'performed_at' => ['required', 'date'],
            'performed_by_role' => ['required', Rule::in(['sirkuler', 'instrumen'])],
            'action' => ['required', 'string', 'max:1000'],
            'evaluation' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
