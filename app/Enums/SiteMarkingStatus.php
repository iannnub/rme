<?php

namespace App\Enums;

enum SiteMarkingStatus: string
{
    case Ada = 'ada';
    case TidakAda = 'tidak_ada';
    case TidakBerlaku = 'tidak_berlaku';

    public function label(): string
    {
        return __('enums.site_marking_status.'.$this->value);
    }
}
