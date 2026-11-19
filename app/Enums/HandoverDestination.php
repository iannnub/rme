<?php

namespace App\Enums;

enum HandoverDestination: string
{
    case Pacu = 'pacu';
    case RuangRawatInap = 'ruang_rawat_inap';
    case Icu = 'icu';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return __('enums.handover_destination.'.$this->value);
    }
}
