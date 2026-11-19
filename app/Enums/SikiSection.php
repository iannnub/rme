<?php

namespace App\Enums;

enum SikiSection: string
{
    case Observasi = 'observasi';
    case Terapeutik = 'terapeutik';
    case Edukasi = 'edukasi';
    case Kolaborasi = 'kolaborasi';

    public function label(): string
    {
        return __('enums.siki_section.'.$this->value);
    }
}
