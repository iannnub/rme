<?php

namespace App\Enums;

enum AirwayPatency: string
{
    case Paten = 'paten';
    case TidakPaten = 'tidak_paten';
    case ObstruksiParsial = 'obstruksi_parsial';
    case ObstruksiTotal = 'obstruksi_total';

    public function label(): string
    {
        return __('enums.airway_patency.'.$this->value);
    }
}
