<?php

namespace App\Http\Requests\Intraop;

use App\Actions\SignIntraopCase;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SignIntraopRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('signIntraop', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_label' => ['required', 'string', Rule::in(SignIntraopCase::VALID_ROLE_LABELS)],
            'password' => ['required', 'current_password'],
        ];
    }
}
