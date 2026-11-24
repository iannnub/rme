<?php

namespace App\Enums;

enum SurgeryProfile: string
{
    case BedahSaraf = 'bedah_saraf';
    case BedahOrtopedi = 'bedah_ortopedi';

    public function label(): string
    {
        return match ($this) {
            self::BedahSaraf => 'Bedah Saraf (EDH)',
            self::BedahOrtopedi => 'Bedah Ortopedi (Fraktur Femur)',
        };
    }

    public function defaultProcedureName(): string
    {
        return match ($this) {
            self::BedahSaraf => 'Kraniotomi evakuasi hematoma',
            self::BedahOrtopedi => 'ORIF / Fiksasi Fraktur Femur',
        };
    }

    public function defaultDiagnosisName(): string
    {
        return match ($this) {
            self::BedahSaraf => 'Epidural Hematoma (EDH)',
            self::BedahOrtopedi => 'Fraktur Femur',
        };
    }
}
