<?php

namespace App\Enums;

enum IvAccessStatus: string
{
    case Memadai = 'memadai';
    case TidakMemadai = 'tidak_memadai';

    public function label(): string
    {
        return __('enums.iv_access_status.'.$this->value);
    }
}
