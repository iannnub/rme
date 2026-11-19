<?php

namespace App\Enums;

enum NursingPhase: string
{
    case Pre = 'pre';
    case Intra = 'intra';
    case Post = 'post';

    public function label(): string
    {
        return __('enums.nursing_phase.'.$this->value);
    }
}
