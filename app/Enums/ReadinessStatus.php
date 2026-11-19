<?php

namespace App\Enums;

enum ReadinessStatus: string
{
    case Siap = 'siap';
    case BelumSiap = 'belum_siap';

    public function label(): string
    {
        return __('enums.readiness_status.'.$this->value);
    }
}
