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
}
