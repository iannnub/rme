<?php

namespace App\Http\Requests\Patients;

use App\Models\Patient;

class UpdatePatientRequest extends StorePatientRequest
{
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        return $patient instanceof Patient && ($this->user()?->can('update', $patient) ?? false);
    }

    protected function targetPatient(): mixed
    {
        return $this->route('patient');
    }
}
