<?php

namespace App\Enums;

enum NurseType: string
{
    case Sirkuler = 'sirkuler';
    case Instrumen = 'instrumen';
    case Anestesi = 'anestesi';

    public function label(): string
    {
        return __('enums.nurse_type.'.$this->value);
    }
}
