<?php

namespace App\Enums;

enum SurgicalPosition: string
{
    case Supine = 'supine';
    case Prone = 'prone';
    case Lithotomy = 'lithotomy';
    case LateralKiri = 'lateral_kiri';
    case LateralKanan = 'lateral_kanan';
    case Trendelenburg = 'trendelenburg';
    case Fowler = 'fowler';
    case SemiFowler = 'semi_fowler';

    public function label(): string
    {
        return __('enums.surgical_position.'.$this->value);
    }
}
