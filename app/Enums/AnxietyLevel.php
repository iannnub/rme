<?php

namespace App\Enums;

enum AnxietyLevel: string
{
    case TidakCemas = 'tidak_cemas';
    case Ringan = 'ringan';
    case Sedang = 'sedang';
    case Berat = 'berat';

    public function label(): string
    {
        return __('enums.anxiety_level.'.$this->value);
    }
}
