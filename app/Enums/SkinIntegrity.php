<?php

namespace App\Enums;

enum SkinIntegrity: string
{
    case Utuh = 'utuh';
    case Lesi = 'lesi';

    public function label(): string
    {
        return __('enums.skin_integrity.'.$this->value);
    }
}
