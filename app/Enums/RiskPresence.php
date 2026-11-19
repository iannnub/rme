<?php

namespace App\Enums;

enum RiskPresence: string
{
    case Ada = 'ada';
    case TidakAda = 'tidak_ada';

    public function label(): string
    {
        return __('enums.risk_presence.'.$this->value);
    }
}
