<?php

namespace App\Enums;

enum SlkiExpectation: string
{
    case Meningkat = 'meningkat';
    case Menurun = 'menurun';
    case Membaik = 'membaik';

    public function label(): string
    {
        return __('enums.slki_expectation.'.$this->value);
    }
}
