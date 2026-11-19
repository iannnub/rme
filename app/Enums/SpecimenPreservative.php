<?php

namespace App\Enums;

enum SpecimenPreservative: string
{
    case Formalin10 = 'formalin_10';
    case NaCl09 = 'nacl_09';
    case Fresh = 'fresh';

    public function label(): string
    {
        return __('enums.specimen_preservative.'.$this->value);
    }
}
