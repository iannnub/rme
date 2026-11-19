<?php

namespace App\Enums;

enum Role: string
{
    case Perawat = 'perawat';
    case DokterOperator = 'dokter_operator';
    case DokterAnestesi = 'dokter_anestesi';
    case KepalaRuangan = 'kepala_ruangan';
    case Admin = 'admin';

    public function label(): string
    {
        return __('enums.role.'.$this->value);
    }
}
