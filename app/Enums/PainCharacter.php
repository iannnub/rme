<?php

namespace App\Enums;

enum PainCharacter: string
{
    case Tajam = 'tajam';
    case Tumpul = 'tumpul';
    case Berdenyut = 'berdenyut';

    public function label(): string
    {
        return __('enums.pain_character.'.$this->value);
    }
}
