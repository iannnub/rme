<?php

namespace App\Enums;

enum InstrumentCountStatus: string
{
    case Sesuai = 'S';
    case TidakSesuai = 'TS';

    public function label(): string
    {
        return __('enums.instrument_count_status.'.$this->value);
    }
}
