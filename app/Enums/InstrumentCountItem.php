<?php

namespace App\Enums;

enum InstrumentCountItem: string
{
    case KasaSteril = 'kasa_steril';
    case KasaLaparotomi = 'kasa_laparotomi';
    case Jarum = 'jarum';
    case Pisau = 'pisau';
    case InstrumenSet = 'instrumen_set';

    public function label(): string
    {
        return __('enums.instrument_count_item.'.$this->value);
    }

    /**
     * Daftar item penghitungan intra-operasi sesuai profil operasi.
     * Sesuai SKPL Revisi 1 Bedah Saraf (EDH) D.8-D.10: Kasa, Jarum, Instrumen.
     *
     * @return list<self>
     */
    public static function casesForProfile(?SurgeryProfile $profile): array
    {
        if ($profile === SurgeryProfile::BedahSaraf) {
            return [
                self::KasaSteril,
                self::Jarum,
                self::InstrumenSet,
            ];
        }

        return self::cases();
    }
}
