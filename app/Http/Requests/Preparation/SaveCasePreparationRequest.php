<?php

namespace App\Http\Requests\Preparation;

use App\Enums\Role;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCasePreparationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('surgeryCase');

        return $case instanceof SurgeryCase
            && ($this->user()?->can('managePreparation', $case) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('is_active', true)],
            'surgeon_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', Role::DokterOperator->value)->where('is_active', true),
            ],
            'anesthesiologist_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', Role::DokterAnestesi->value)->where('is_active', true),
            ],
            'circulating_nurse_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', Role::Perawat->value)->where('is_active', true),
            ],
            'scrub_nurse_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', Role::Perawat->value)->where('is_active', true),
            ],
            'anesthesia_nurse_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', Role::Perawat->value)->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'room_id' => 'ruang operasi',
            'surgeon_id' => 'dokter operator',
            'anesthesiologist_id' => 'dokter anestesi',
            'circulating_nurse_id' => 'perawat sirkuler',
            'scrub_nurse_id' => 'perawat instrumen',
            'anesthesia_nurse_id' => 'perawat anestesi',
        ];
    }
}
