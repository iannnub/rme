<?php

use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\CaseStatus;
use App\Enums\CountVerification;
use App\Enums\Gender;
use App\Enums\NurseType;
use App\Enums\ReadinessStatus;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Enums\SscStatus;
use App\Enums\SurgeryProfile;
use App\Models\AnesthesiaType;
use App\Models\CaseSignoff;
use App\Models\MedicalDiagnosis;
use App\Models\SscSignIn;
use App\Models\SscSignOut;
use App\Models\SscTimeOut;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use Database\Seeders\MasterSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(MasterSeeder::class);
});

test('alur profil bedah saraf EDH menyimpan identitas IGD, pre-op neurologis, 50 alat, intra-op kraniotomi, pasca-op, dan PDF', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $neuroType = SurgeryType::query()->where('code', SurgeryProfile::BedahSaraf->value)->firstOrFail();
    $anesthesia = AnesthesiaType::query()->firstOrFail();

    $createPayload = [
        'patient_mode' => 'new',
        'rm_number' => 'RM-NEURO-001',
        'patient_name' => 'Budi Santoso',
        'birth_date' => '1988-05-12',
        'gender' => Gender::Male->value,
        'identity_number' => '3275011205880001',
        'address' => 'Jl. Merdeka No. 10',
        'phone' => '081234567890',
        'guardian_name' => 'Siti Aminah',
        'guardian_relation' => 'Istri',
        'guardian_phone' => '081298765432',
        'admitted_at' => '2026-11-23T08:00',
        'igd_visit_number' => 'IGD-20261123-010',
        'igd_admitted_at' => '2026-11-23T07:15',
        'origin_ward' => 'IGD',
        'scheduled_at' => '2026-11-23T09:00',
        'urgency_status' => 'emergency',
        'dpjp_name' => 'dr. Hendra, Sp.BS',
        'surgery_type_id' => $neuroType->id,
        'surgery_site' => 'Temporoparietal Kanan',
        'anesthesia_type_id' => $anesthesia->id,
    ];

    $this->actingAs($perawat)
        ->post('/kasus', $createPayload)
        ->assertRedirect();

    $case = SurgeryCase::query()->with(['patient', 'medicalDiagnosis', 'surgeryType'])->latest('id')->firstOrFail();
    expect($case->surgeryProfile())->toBe(SurgeryProfile::BedahSaraf);
    expect($case->medicalDiagnosis?->name)->toBe('Epidural Hematoma (EDH)');
    expect($case->igd_visit_number)->toBe('IGD-20261123-010');
    expect($case->dpjp_name)->toBe('dr. Hendra, Sp.BS');
    expect($case->patient?->identity_number)->toBe('3275011205880001');
    expect($case->patient?->guardian_name)->toBe('Siti Aminah');

    $this->actingAs($perawat)
        ->get("/kasus/{$case->id}/pre-op")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/pre-op')
            ->where('caseHeader.surgery_profile', SurgeryProfile::BedahSaraf->value)
            ->has('assessment.clinical_details.neurological')
        );

    $preopPayload = [
        'chief_complaint' => 'Penurunan kesadaran pasca benturan kepala',
        'allergy_status' => AllergyStatus::TidakAda->value,
        'fasting_status' => true,
        'fasting_since' => '2026-11-23T05:00',
        'anxiety_level' => AnxietyLevel::TidakCemas->value,
        'readiness_status' => ReadinessStatus::Siap->value,
        'preparation' => [
            'identity_verified' => true,
            'informed_consent_surgery' => true,
            'anesthesia_consent' => true,
            'fasting_verified' => true,
            'allergy_assessed' => true,
            'lab_available' => true,
            'iv_access' => true,
            'operation_area_prepared' => true,
            'jewelry_removed' => true,
        ],
        'clinical_details' => [
            'incident' => ['injury_cause' => 'Kecelakaan lalu lintas', 'loss_of_consciousness' => 'Lucid interval (+)'],
            'neurological' => ['gcs_e' => '2', 'gcs_v' => '3', 'gcs_m' => '5', 'pupil_size_right' => '5 mm'],
            'vital_extra' => ['gds' => '138 mg/dL'],
            'supporting_detail' => ['ct_scan_result' => 'EDH temporoparietal dekstra volume 45 mL', 'midline_shift' => '8 mm ke kiri'],
            'prep_extra' => ['blood_prep' => '2 kolf PRC standby'],
            'anesthesia_pre' => ['asa_class' => 'ASA III E'],
            'anesthesia_exec' => ['anesthesia_method' => 'General Anesthesia ETT'],
        ],
    ];

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", $preopPayload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $case->refresh();
    expect($case->preopAssessment?->clinical_details['neurological']['pupil_size_right'])->toBe('5 mm');
    expect($case->preopAssessment?->clinical_details['supporting_detail']['midline_shift'])->toBe('8 mm ke kiri');

    $this->actingAs($perawat)
        ->get("/kasus/{$case->id}/persiapan")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/preparation')
            ->has('options.instruments', 50)
        );

    $case->forceFill(['status' => CaseStatus::IntraOp])->save();
    SscSignIn::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai]);
    SscTimeOut::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/kronologi", [
            'operation_plan_actual' => 'Kraniotomi Evakuasi EDH Temporoparietal Dekstra',
            'postop_diagnosis' => 'Post Kraniotomi Evakuasi Epidural Hematoma (EDH)',
            'entered_or_at' => '2026-11-23T09:10',
            'anesthesia_start_at' => '2026-11-23T09:20',
            'incision_at' => '2026-11-23T09:40',
            'surgery_end_at' => '2026-11-23T11:30',
            'left_or_at' => '2026-11-23T11:50',
            'anesthesia_end_at' => '2026-11-23T11:45',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/detail-tindakan", [
            'procedure_details' => [
                'craniotomy_location' => 'Temporoparietal kanan',
                'hematoma_volume_condition' => '45 mL bekuan epidural',
                'bone_flap_management' => 'Bone flap dikembalikan dengan miniplate',
                'blood_transfusion_given' => false,
            ],
            'implants' => [],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $case->refresh();
    expect($case->postop_diagnosis)->toBe('Post Kraniotomi Evakuasi Epidural Hematoma (EDH)');
    expect($case->intraopRecord?->procedure_details['craniotomy_location'])->toBe('Temporoparietal kanan');

    $case->forceFill(['status' => CaseStatus::PascaOp])->save();

    $this->actingAs($perawat)
        ->post("/kasus/{$case->id}/pasca-op/observasi", [
            'recorded_at' => '2026-11-23T12:15',
            'gcs_eye' => 4,
            'gcs_verbal' => 4,
            'gcs_motor' => 6,
            'general_condition' => 'baik',
            'response' => 'stabil',
            'airway_patency' => 'paten',
            'breathing_pattern' => 'eupnea',
            'oxygen_device' => 'nasal_kanul',
            'oxygen_flow_lpm' => 3,
            'pain_nrs' => 3,
            'clinical_details' => [
                'pupil_size_right' => '3 mm',
                'pupil_size_left' => '3 mm',
                'pupil_light_reaction' => 'Isokor +/+',
                'extremity_motor' => '5/5 bilateral',
                'anesthesia_recovery_score' => 'Aldrete 9/10',
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pasca-op/kondisi-pindah", [
            'ready_to_transfer' => true,
            'pre_transfer_condition_notes' => 'Pasien sadar penuh dan hemodinamik stabil',
            'followup_plan' => [
                'medications_given' => 'Ceftriaxone 2 g IV',
                'neuro_followup_monitoring' => 'Observasi GCS/pupil tiap 30 menit di ICU',
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $pdf = $this->actingAs($perawat)->get("/kasus/{$case->id}/pdf");
    $pdf->assertOk();
    expect(substr((string) $pdf->getContent(), 0, 5))->toBe('%PDF-');
});

test('alur profil bedah ortopedi fraktur femur menyediakan 66 alat, implan ORIF, neurovaskular 5P, dan PDF', function () {
    $perawat = User::factory()->perawat(NurseType::Sirkuler)->create();
    $orthoType = SurgeryType::query()->where('code', SurgeryProfile::BedahOrtopedi->value)->firstOrFail();
    $anesthesia = AnesthesiaType::query()->firstOrFail();

    $this->actingAs($perawat)
        ->post('/kasus', [
            'patient_mode' => 'new',
            'rm_number' => 'RM-ORTHO-001',
            'patient_name' => 'Andi Wijaya',
            'birth_date' => '1994-03-20',
            'gender' => Gender::Male->value,
            'admitted_at' => '2026-11-23T10:00',
            'origin_ward' => 'IGD',
            'scheduled_at' => '2026-11-23T13:00',
            'urgency_status' => 'emergency',
            'dpjp_name' => 'dr. Bagus, Sp.OT',
            'surgery_type_id' => $orthoType->id,
            'surgery_site' => 'Femur Dekstra 1/3 Tengah',
            'anesthesia_type_id' => $anesthesia->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $case = SurgeryCase::query()->with(['medicalDiagnosis'])->latest('id')->firstOrFail();
    expect($case->surgeryProfile())->toBe(SurgeryProfile::BedahOrtopedi);
    expect($case->medicalDiagnosis?->name)->toBe('Fraktur Femur');

    $this->actingAs($perawat)
        ->get("/kasus/{$case->id}/persiapan")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/preparation')
            ->has('options.instruments', 66)
        );

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pre-op", [
            'allergy_status' => AllergyStatus::TidakAda->value,
            'fasting_status' => true,
            'fasting_since' => '2026-11-23T06:00',
            'anxiety_level' => AnxietyLevel::Ringan->value,
            'readiness_status' => ReadinessStatus::Siap->value,
            'preparation' => [
                'identity_verified' => true,
                'informed_consent_surgery' => true,
                'anesthesia_consent' => true,
                'fasting_verified' => true,
                'allergy_assessed' => true,
                'lab_available' => true,
                'iv_access' => true,
                'operation_area_prepared' => true,
                'jewelry_removed' => true,
            ],
            'clinical_details' => [
                'extremity_physical' => ['fracture_location' => '1/3 Tengah Femur', 'fracture_side' => 'Dekstra'],
                'neurovascular' => ['crt' => '< 2 detik', 'peripheral_pulse' => 'Dorsalis pedis kuat'],
                'supporting_detail' => ['xray_femur_result' => 'Fraktur diafisis femur kanan komplit'],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $case->forceFill(['status' => CaseStatus::IntraOp])->save();
    SscSignIn::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai]);
    SscTimeOut::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai]);

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/intra-op/detail-tindakan", [
            'procedure_details' => [
                'fracture_location' => 'Diafisis femur dekstra',
                'reduction_type' => 'Open Reduction (ORIF)',
                'fixation_method' => 'Broad LCP Plate 10 Hole',
                'fluoroscopy_check' => 'Posisi plate dan screw baik',
                'blood_transfusion_given' => true,
                'blood_product_type_amount' => 'PRC 1 kolf (250 mL)',
            ],
            'implants' => [
                [
                    'implant_type' => 'Broad LCP Femur Plate',
                    'size' => '10 Hole 4.5 mm',
                    'planned_qty' => '1',
                    'brand' => 'Synthes',
                    'serial_lot' => 'LOT-2026-991',
                    'placement_site' => 'Lateral Femur Dekstra',
                    'used_qty' => '1',
                    'unused_doc' => 'Tidak ada sisa',
                ],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $case->refresh();
    expect($case->intraopRecord?->implants)->toHaveCount(1);
    expect($case->intraopRecord?->implants[0]['serial_lot'])->toBe('LOT-2026-991');

    $case->forceFill(['status' => CaseStatus::PascaOp])->save();

    $this->actingAs($perawat)
        ->put("/kasus/{$case->id}/pasca-op/kondisi-pindah", [
            'ready_to_transfer' => true,
            'pre_transfer_condition_notes' => 'Neurovaskular distal hangat, CRT < 2 dtk',
            'followup_plan' => [
                'mobilization_instruction' => 'Latihan isometric kuadrisep',
                'weight_bearing_instruction' => 'Non-weight bearing (NWB)',
                'radiology_plan' => 'Rontgen Femur AP/Lateral kontrol',
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $pdf = $this->actingAs($perawat)->get("/kasus/{$case->id}/pdf");
    $pdf->assertOk();
    expect(substr((string) $pdf->getContent(), 0, 5))->toBe('%PDF-');
});

test('skpl bedah saraf edh menyederhanakan preop tanpa cemas, 3 item hitung intraop, merge procedure details, dan transisi pascaop tanpa keluar ok', function () {
    $sirkuler = User::factory()->perawat(NurseType::Sirkuler)->create(['name' => 'Ns. Sirkuler EDH']);
    $instrumen = User::factory()->perawat(NurseType::Instrumen)->create();
    $surgeon = User::factory()->dokterOperator()->create();
    $anesthesiologist = User::factory()->dokterAnestesi()->create();

    $neuroType = SurgeryType::query()->where('code', SurgeryProfile::BedahSaraf->value)->firstOrFail();
    $diagnosis = MedicalDiagnosis::query()->where('name', 'like', '%Epidural Hematoma%')->first() ?? MedicalDiagnosis::query()->firstOrFail();
    $anesthesia = AnesthesiaType::query()->firstOrFail();

    $case = SurgeryCase::factory()->create([
        'surgery_type_id' => $neuroType->id,
        'medical_diagnosis_id' => $diagnosis->id,
        'anesthesia_type_id' => $anesthesia->id,
        'surgeon_id' => $surgeon->id,
        'anesthesiologist_id' => $anesthesiologist->id,
        'scrub_nurse_id' => $instrumen->id,
        'circulating_nurse_id' => $sirkuler->id,
        'created_by' => $sirkuler->id,
        'status' => CaseStatus::PreOp,
    ]);

    $this->actingAs($sirkuler)
        ->get("/kasus/{$case->id}/pre-op")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/pre-op')
            ->has('prepProgress.instrument_checks_count')
            ->has('caseHeader.created_by_name')
            ->has('caseHeader.patient.address')
        );

    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/pre-op", [
            'medical_diagnosis_id' => (string) $diagnosis->id,
            'anesthesia_type_id' => (string) $anesthesia->id,
            'surgery_site' => 'Kranium Frontotemporoparietal Kanan',
            'chief_complaint' => 'Penurunan kesadaran pascatrauma',
            'allergy_status' => AllergyStatus::TidakAda->value,
            'fasting_status' => false,
            'readiness_status' => ReadinessStatus::Siap->value,
            'preparation' => [
                'informed_consent_surgery' => true,
                'iv_access' => true,
                'site_marked' => true,
            ],
            'vital_signs' => [
                'bp_systolic' => 140,
                'bp_diastolic' => 88,
                'pulse' => 92,
                'respiratory_rate' => 22,
                'temperature' => 36.8,
                'spo2' => 97,
                'consciousness' => 'somnolen',
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $case->refresh();
    expect($case->preopAssessment?->anxiety_level)->toBe(AnxietyLevel::TidakCemas);

    $case->forceFill(['status' => CaseStatus::IntraOp])->save();
    SscSignIn::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai]);
    SscTimeOut::factory()->create(['case_id' => $case->id, 'status' => SscStatus::Selesai]);
    SscSignOut::factory()->create(['case_id' => $case->id, 'status' => SignOutStatus::Terverifikasi]);

    $this->actingAs($sirkuler)
        ->get("/kasus/{$case->id}/intra-op")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cases/intra-op')
            ->has('instrumentCounts.rows.kasa_steril')
            ->has('instrumentCounts.rows.jarum')
            ->has('instrumentCounts.rows.instrumen_set')
            ->missing('instrumentCounts.rows.pisau_bisturi')
            ->missing('instrumentCounts.rows.implant')
            ->has('chronology.time_out_label')
            ->has('chronology.sign_out_label')
        );

    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/intra-op/detail-tindakan", [
            'procedure_details' => [
                'bone_tissue_condition' => 'Fraktur linear os temporoparietal kanan',
                'hematoma_volume_condition' => 'Bekuan 50 mL',
            ],
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/intra-op/detail-tindakan", [
            'procedure_details' => [
                'cautery_usage' => 'Bipolar 25 W berfungsi baik',
            ],
        ])
        ->assertSessionHasNoErrors();

    $case->refresh();
    expect($case->intraopRecord?->procedure_details['bone_tissue_condition'] ?? null)
        ->toBe('Fraktur linear os temporoparietal kanan');
    expect($case->intraopRecord?->procedure_details['cautery_usage'] ?? null)
        ->toBe('Bipolar 25 W berfungsi baik');

    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/intra-op/kronologi", [
            'operation_plan_actual' => 'Kraniotomi evakuasi hematoma',
            'postop_diagnosis' => 'Post Kraniotomi EDH',
            'entered_or_at' => '2026-11-23T09:00',
            'anesthesia_start_at' => '2026-11-23T09:10',
            'incision_at' => '2026-11-23T09:25',
            'surgery_end_at' => '2026-11-23T11:10',
            'anesthesia_end_at' => '2026-11-23T11:25',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($anesthesiologist)
        ->post("/kasus/{$case->id}/intra-op/ttv", [
            'recorded_at' => '2026-11-23T09:30',
            'bp_systolic' => 122,
            'bp_diastolic' => 78,
            'pulse' => 84,
            'temperature' => 36.6,
            'respiratory_rate' => 16,
            'spo2' => 99,
            'breathing_pattern' => 'eupnea',
            'oxygen_device' => 'tanpa_oksigen',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($sirkuler)
        ->put("/kasus/{$case->id}/intra-op/hitung-instrumen", [
            'counts' => [
                'kasa_steril' => ['initial_count' => 20, 'additional_count' => 0, 'final_count' => 20],
                'jarum' => ['initial_count' => 6, 'additional_count' => 0, 'final_count' => 6],
                'instrumen_set' => ['initial_count' => 33, 'additional_count' => 0, 'final_count' => 33],
            ],
            'count_verification' => CountVerification::LengkapSesuai->value,
        ])
        ->assertSessionHasNoErrors();

    foreach (['perawat_sirkuler' => $sirkuler, 'perawat_instrumen' => $instrumen, 'dokter_anestesi' => $anesthesiologist, 'dokter_operator' => $surgeon] as $role => $signer) {
        CaseSignoff::query()->create([
            'case_id' => $case->id,
            'section' => SignoffSection::IntraOp,
            'kind' => SignoffKind::Signoff,
            'role_label' => $role,
            'user_id' => $signer->id,
            'signed_at' => now(),
        ]);
    }

    $this->actingAs($sirkuler)
        ->post("/kasus/{$case->id}/lanjut-status", [
            'target_status' => CaseStatus::PascaOp->value,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($case->fresh()->status)->toBe(CaseStatus::PascaOp);
});
