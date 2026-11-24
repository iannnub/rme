<?php

use App\Enums\CaseStatus;
use App\Enums\NurseType;
use App\Enums\Role;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Enums\SurgeryProfile;
use App\Models\AnesthesiaType;
use App\Models\CaseSignoff;
use App\Models\Handover;
use App\Models\Instrument;
use App\Models\InstrumentCheck;
use App\Models\InstrumentCount;
use App\Models\MedicalDiagnosis;
use App\Models\NursingDiagnosis;
use App\Models\Patient;
use App\Models\PreopAssessment;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SscSignIn;
use App\Models\SscSignOut;
use App\Models\SscTimeOut;
use App\Models\SupportingExam;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use App\Models\VitalSign;
use Database\Seeders\DatabaseSeeder;

test('master seeder dan demo seeder bersifat idempoten serta menghasilkan seluruh data awal', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Room::query()->count())->toBe(3);
    expect(AnesthesiaType::query()->count())->toBe(5);
    expect(MedicalDiagnosis::query()->count())->toBe(2);
    expect(SurgeryType::query()->count())->toBe(2);
    expect(Instrument::query()->count())->toBe(81);
    expect(Instrument::forProfile(SurgeryProfile::BedahSaraf)->count())->toBe(50);
    expect(Instrument::forProfile(SurgeryProfile::BedahOrtopedi)->count())->toBe(66);
    expect(Setting::query()->count())->toBe(4);
    expect(User::query()->count())->toBe(8);
    expect(Patient::query()->count())->toBe(4);
    expect(SurgeryCase::query()->count())->toBe(4);
    expect(CaseSignoff::query()->count())->toBe(10);
    expect(NursingDiagnosis::query()->count())->toBe(20);
    expect(PreopAssessment::query()->count())->toBe(2);
    expect(VitalSign::query()->count())->toBe(8);
    expect(SupportingExam::query()->count())->toBe(3);
    expect(InstrumentCheck::query()->count())->toBe(9);
    expect(SscSignIn::query()->count())->toBe(3);
    expect(SscTimeOut::query()->count())->toBe(2);
    expect(SscSignOut::query()->count())->toBe(2);
    expect(InstrumentCount::query()->count())->toBe(10);
    expect(Handover::query()->count())->toBe(1);

    expect(Patient::query()->where('rm_number', 'DEMO-001')->exists())->toBeTrue();
    expect(Patient::query()->where('rm_number', 'DEMO-002')->exists())->toBeTrue();
    expect(Patient::query()->where('rm_number', 'DEMO-003')->exists())->toBeTrue();
    expect(Patient::query()->where('rm_number', 'DEMO-004')->exists())->toBeTrue();

    $case1 = SurgeryCase::query()->where('case_number', 'OK-20261119-001')->first();
    $case2 = SurgeryCase::query()->where('case_number', 'OK-20261119-002')->first();
    $case3 = SurgeryCase::query()->where('case_number', 'OK-20261119-003')->first();
    $case4 = SurgeryCase::query()->where('case_number', 'OK-20261119-004')->first();

    expect($case1?->status)->toBe(CaseStatus::PreOp);
    expect($case2?->status)->toBe(CaseStatus::SiapOperasi);
    expect($case3?->status)->toBe(CaseStatus::Selesai);
    expect($case4?->status)->toBe(CaseStatus::IntraOp);
    expect($case4?->sscSignOut?->status)->toBe(SignOutStatus::PerluTindakLanjut);
    expect($case2?->room_id)->not->toBeNull();
    expect($case2?->surgeon_id)->not->toBeNull();
    expect($case2?->signoffs()->where('section', SignoffSection::PreOp)->where('kind', SignoffKind::Signoff)->exists())->toBeTrue();

    expect(User::query()->where('username', 'admin')->first()?->role)->toBe(Role::Admin);
    expect(User::query()->where('username', 'kepala')->first()?->role)->toBe(Role::KepalaRuangan);
    expect(User::query()->where('username', 'dr.operator1')->first()?->role)->toBe(Role::DokterOperator);
    expect(User::query()->where('username', 'dr.anestesi1')->first()?->role)->toBe(Role::DokterAnestesi);
    expect(User::query()->where('username', 'ns.sirkuler1')->first()?->nurse_type)->toBe(NurseType::Sirkuler);
    expect(User::query()->where('username', 'ns.instrumen1')->first()?->nurse_type)->toBe(NurseType::Instrumen);
    expect(User::query()->where('username', 'ns.anestesi1')->first()?->nurse_type)->toBe(NurseType::Anestesi);
    expect(User::query()->where('username', 'ns.sirkuler2')->first()?->nurse_type)->toBe(NurseType::Sirkuler);
});

test('seluruh akun demo dapat login dengan sandi dari konfigurasi', function () {
    $this->seed(DatabaseSeeder::class);
    $password = config('app.demo_password') ?: 'Password123!';

    $usernames = [
        'admin',
        'kepala',
        'dr.operator1',
        'dr.anestesi1',
        'ns.sirkuler1',
        'ns.instrumen1',
        'ns.anestesi1',
        'ns.sirkuler2',
    ];

    foreach ($usernames as $username) {
        $this->post('/login', [
            'login' => $username,
            'password' => $password,
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->post('/logout');
        $this->assertGuest();
    }
});
