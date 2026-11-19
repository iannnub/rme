<?php

namespace App\Enums;

enum OxygenDevice: string
{
    case TanpaOksigen = 'tanpa_oksigen';
    case NasalKanul = 'nasal_kanul';
    case SimpleMask = 'simple_mask';
    case NonRebreathing = 'non_rebreathing';
    case Rebreathing = 'rebreathing';
    case Ventilator = 'ventilator';

    public function label(): string
    {
        return __('enums.oxygen_device.'.$this->value);
    }
}
