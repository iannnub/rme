<?php

namespace App\Enums;

enum SpecimenPurpose: string
{
    case PatologiAnatomi = 'patologi_anatomi';
    case Sitologi = 'sitologi';
    case MikrobiologiKultur = 'mikrobiologi_kultur';

    public function label(): string
    {
        return __('enums.specimen_purpose.'.$this->value);
    }
}
