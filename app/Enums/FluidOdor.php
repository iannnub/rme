<?php

namespace App\Enums;

enum FluidOdor: string
{
    case TidakBerbau = 'tidak_berbau';
    case Berbau = 'berbau';

    public function label(): string
    {
        return __('enums.fluid_odor.'.$this->value);
    }
}
