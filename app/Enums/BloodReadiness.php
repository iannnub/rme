<?php

namespace App\Enums;

enum BloodReadiness: string
{
    case Siap = 'siap';
    case TidakSiap = 'tidak_siap';
    case TidakDiindikasikan = 'tidak_diindikasikan';

    public function label(): string
    {
        return __('enums.blood_readiness.'.$this->value);
    }
}
