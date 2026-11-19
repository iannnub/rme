<?php

namespace App\Enums;

enum Consciousness: string
{
    case ComposMentis = 'compos_mentis';
    case Apatis = 'apatis';
    case Somnolen = 'somnolen';
    case Sopor = 'sopor';
    case Koma = 'koma';

    public function label(): string
    {
        return __('enums.consciousness.'.$this->value);
    }
}
