<?php

namespace App\Enums;

enum PatientResponse: string
{
    case Stabil = 'stabil';
    case Lemah = 'lemah';

    public function label(): string
    {
        return __('enums.patient_response.'.$this->value);
    }
}
