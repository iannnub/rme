<?php

namespace App\Enums;

enum SscStatus: string
{
    case BelumDimulai = 'belum_dimulai';
    case DalamProses = 'dalam_proses';
    case Selesai = 'selesai';

    public function label(): string
    {
        return __('enums.ssc_status.'.$this->value);
    }
}
