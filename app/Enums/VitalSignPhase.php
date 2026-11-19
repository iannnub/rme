<?php

namespace App\Enums;

enum VitalSignPhase: string
{
    case Pre = 'pre';
    case Intra = 'intra';
    case Post = 'post';

    public function label(): string
    {
        return __('enums.vital_sign_phase.'.$this->value);
    }
}
