<?php

namespace App\Enums;

enum CountVerification: string
{
    case LengkapSesuai = 'lengkap_sesuai';
    case TidakSesuai = 'tidak_sesuai';

    public function label(): string
    {
        return __('enums.count_verification.'.$this->value);
    }
}
