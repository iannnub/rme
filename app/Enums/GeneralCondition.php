<?php

namespace App\Enums;

enum GeneralCondition: string
{
    case Baik = 'baik';
    case Sedang = 'sedang';
    case Buruk = 'buruk';

    public function label(): string
    {
        return __('enums.general_condition.'.$this->value);
    }
}
