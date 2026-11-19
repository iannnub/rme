<?php

namespace App\Enums;

enum DressingCondition: string
{
    case Bersih = 'bersih';
    case Kering = 'kering';
    case Basah = 'basah';

    public function label(): string
    {
        return __('enums.dressing_condition.'.$this->value);
    }
}
