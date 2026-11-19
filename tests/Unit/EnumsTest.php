<?php

use App\Enums\CaseStatus;
use App\Enums\NurseType;
use App\Enums\Role;
use Tests\TestCase;

uses(TestCase::class);

test('enum Role memiliki nilai dan label Bahasa Indonesia yang sesuai', function () {
    expect(array_map(fn (Role $r) => $r->value, Role::cases()))->toBe([
        'perawat',
        'dokter_operator',
        'dokter_anestesi',
        'kepala_ruangan',
        'admin',
    ]);

    expect(Role::Perawat->label())->toBe('Perawat');
    expect(Role::DokterOperator->label())->toBe('Dokter Operator');
    expect(Role::DokterAnestesi->label())->toBe('Dokter Anestesi');
    expect(Role::KepalaRuangan->label())->toBe('Kepala Ruangan');
    expect(Role::Admin->label())->toBe('Admin');
});

test('enum NurseType memiliki nilai dan label Bahasa Indonesia yang sesuai', function () {
    expect(array_map(fn (NurseType $n) => $n->value, NurseType::cases()))->toBe([
        'sirkuler',
        'instrumen',
        'anestesi',
    ]);

    expect(NurseType::Sirkuler->label())->toBe('Perawat Sirkuler');
    expect(NurseType::Instrumen->label())->toBe('Perawat Instrumen');
    expect(NurseType::Anestesi->label())->toBe('Perawat Anestesi');
});

test('enum CaseStatus memiliki nilai dan label Bahasa Indonesia yang sesuai', function () {
    expect(array_map(fn (CaseStatus $s) => $s->value, CaseStatus::cases()))->toBe([
        'pre_op',
        'siap_operasi',
        'intra_op',
        'pasca_op',
        'serah_terima',
        'selesai',
    ]);

    expect(CaseStatus::PreOp->label())->toBe('Pre-Operasi');
    expect(CaseStatus::SiapOperasi->label())->toBe('Siap Operasi');
    expect(CaseStatus::IntraOp->label())->toBe('Intra-Operasi');
    expect(CaseStatus::PascaOp->label())->toBe('Pasca-Operasi');
    expect(CaseStatus::SerahTerima->label())->toBe('Serah Terima');
    expect(CaseStatus::Selesai->label())->toBe('Selesai');
});
