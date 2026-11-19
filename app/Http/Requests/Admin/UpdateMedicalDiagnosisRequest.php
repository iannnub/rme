<?php

namespace App\Http\Requests\Admin;

class UpdateMedicalDiagnosisRequest extends StoreMedicalDiagnosisRequest
{
    protected function targetId(): mixed
    {
        return $this->route('medicalDiagnosis');
    }
}
