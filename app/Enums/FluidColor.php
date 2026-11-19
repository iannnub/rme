<?php

namespace App\Enums;

enum FluidColor: string
{
    case Merah = 'merah';
    case MerahMuda = 'merah_muda';
    case Kekuningan = 'kekuningan';
    case Kehijauan = 'kehijauan';
    case Kecoklatan = 'kecoklatan';

    public function label(): string
    {
        return __('enums.fluid_color.'.$this->value);
    }
}
