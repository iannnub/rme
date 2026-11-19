<?php

namespace App\Enums;

enum BreathingPattern: string
{
    case Eupnea = 'eupnea';
    case Takipnea = 'takipnea';
    case Bradipnea = 'bradipnea';
    case Apnea = 'apnea';
    case Dispnea = 'dispnea';

    public function label(): string
    {
        return __('enums.breathing_pattern.'.$this->value);
    }
}
