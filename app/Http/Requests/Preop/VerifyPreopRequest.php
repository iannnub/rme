<?php

namespace App\Http\Requests\Preop;

use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyPreopRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->isDokter() ?? false)
            && $case->status !== CaseStatus::Selesai;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }
}
