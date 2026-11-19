<?php

namespace App\Enums;

enum SscReadiness: string
{
    case Siap = 'siap';
    case TidakSiap = 'tidak_siap';

    public function label(): string
    {
        return __('enums.ssc_readiness.'.$this->value);
    }
}
