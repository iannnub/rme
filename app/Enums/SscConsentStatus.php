<?php

namespace App\Enums;

enum SscConsentStatus: string
{
    case Ada = 'ada';
    case BelumAda = 'belum_ada';

    public function label(): string
    {
        return __('enums.ssc_consent_status.'.$this->value);
    }
}
