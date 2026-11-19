<?php

namespace App\Http\Requests\Intraop;

use App\Enums\FluidKind;
use App\Models\SurgeryCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntraopFluidRequest extends FormRequest
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
            'recorded_at' => ['nullable', 'date'],
            'kind' => ['required', Rule::enum(FluidKind::class)],
            'volume_ml' => ['required', 'integer', 'min:1', 'max:50000'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
