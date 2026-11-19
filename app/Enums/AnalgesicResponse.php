<?php

namespace App\Enums;

enum AnalgesicResponse: string
{
    case Berkurang = 'berkurang';
    case Tetap = 'tetap';
    case Bertambah = 'bertambah';

    public function label(): string
    {
        return __('enums.analgesic_response.'.$this->value);
    }
}
