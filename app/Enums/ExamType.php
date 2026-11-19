<?php

namespace App\Enums;

enum ExamType: string
{
    case Hemoglobin = 'hemoglobin';
    case Leukosit = 'leukosit';
    case Trombosit = 'trombosit';
    case GulaDarah = 'gula_darah';
    case UreumKreatinin = 'ureum_kreatinin';
    case Xray = 'xray';
    case Usg = 'usg';
    case CtScan = 'ct_scan';
    case Mri = 'mri';
    case Mammografi = 'mammografi';
    case Panoramic = 'panoramic';
    case Ekg = 'ekg';
    case Echo = 'echo';
    case Treadmill = 'treadmill';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return __('enums.exam_type.'.$this->value);
    }
}
