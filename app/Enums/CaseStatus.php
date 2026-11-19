<?php

namespace App\Enums;

enum CaseStatus: string
{
    case PreOp = 'pre_op';
    case SiapOperasi = 'siap_operasi';
    case IntraOp = 'intra_op';
    case PascaOp = 'pasca_op';
    case SerahTerima = 'serah_terima';
    case Selesai = 'selesai';

    public function label(): string
    {
        return __('enums.case_status.'.$this->value);
    }

    public function order(): int
    {
        return match ($this) {
            self::PreOp => 0,
            self::SiapOperasi => 1,
            self::IntraOp => 2,
            self::PascaOp => 3,
            self::SerahTerima => 4,
            self::Selesai => 5,
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::PreOp => self::SiapOperasi,
            self::SiapOperasi => self::IntraOp,
            self::IntraOp => self::PascaOp,
            self::PascaOp => self::SerahTerima,
            self::SerahTerima => self::Selesai,
            self::Selesai => null,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->order() >= $other->order();
    }
}
