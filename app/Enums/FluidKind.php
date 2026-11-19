<?php

namespace App\Enums;

enum FluidKind: string
{
    case Kristaloid = 'kristaloid';
    case Koloid = 'koloid';
    case Transfusi = 'transfusi';
    case Perdarahan = 'perdarahan';
    case Urine = 'urine';
    case SuctionDrain = 'suction_drain';

    public function label(): string
    {
        return __('enums.fluid_kind.'.$this->value);
    }

    public function direction(): FluidDirection
    {
        return match ($this) {
            self::Kristaloid, self::Koloid, self::Transfusi => FluidDirection::Intake,
            self::Perdarahan, self::Urine, self::SuctionDrain => FluidDirection::Output,
        };
    }
}
