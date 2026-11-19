<?php

namespace App\Enums;

enum SignOutStatus: string
{
    case Draf = 'draf';
    case PerluTindakLanjut = 'perlu_tindak_lanjut';
    case Terverifikasi = 'terverifikasi';

    public function label(): string
    {
        return __('enums.sign_out_status.'.$this->value);
    }
}
