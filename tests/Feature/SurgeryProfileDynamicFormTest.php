<?php

use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\CaseStatus;
use App\Enums\Gender;
use App\Enums\NurseType;
use App\Enums\ReadinessStatus;
use App\Enums\SscStatus;
use App\Enums\SurgeryProfile;
use App\Models\AnesthesiaType;
use App\Models\SscSignIn;
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
