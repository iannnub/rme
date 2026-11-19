<?php

namespace App\Enums;

enum SutureCondition: string
{
    case RapiUtuh = 'rapi_utuh';
    case Terlepas = 'terlepas';

    public function label(): string
    {
        return __('enums.suture_condition.'.$this->value);
    }
}
