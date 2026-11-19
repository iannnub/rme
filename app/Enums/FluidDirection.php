<?php

namespace App\Enums;

enum FluidDirection: string
{
    case Intake = 'intake';
    case Output = 'output';

    public function label(): string
    {
        return __('enums.fluid_direction.'.$this->value);
    }
}
