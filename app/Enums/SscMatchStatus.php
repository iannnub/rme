<?php

namespace App\Enums;

enum SscMatchStatus: string
{
    case Sesuai = 'sesuai';
    case TidakSesuai = 'tidak_sesuai';

    public function label(): string
    {
        return __('enums.ssc_match_status.'.$this->value);
    }
}
