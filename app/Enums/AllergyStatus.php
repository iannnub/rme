<?php

namespace App\Enums;

enum AllergyStatus: string
{
    case Ada = 'ada';
    case TidakAda = 'tidak_ada';
    case BelumDiketahui = 'belum_diketahui';

    public function label(): string
    {
        return __('enums.allergy_status.'.$this->value);
    }
}
