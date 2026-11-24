<?php

namespace Database\Seeders;

use App\Enums\AirwayPatency;
use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\BloodReadiness;
use App\Enums\BreathingPattern;
use App\Enums\CaseStatus;
use App\Enums\Consciousness;
use App\Enums\CountVerification;
use App\Enums\DressingCondition;
use App\Enums\ExamType;
use App\Enums\FluidDirection;
use App\Enums\FluidKind;
use App\Enums\Gender;
use App\Enums\GeneralCondition;
use App\Enums\HandoverDestination;
use App\Enums\InstrumentCountItem;
use App\Enums\InstrumentCountStatus;
use App\Enums\IvAccessStatus;
use App\Enums\NurseType;
use App\Enums\NursingPhase;
use App\Enums\OxygenDevice;
use App\Enums\PatientResponse;
use App\Enums\ReadinessStatus;
use App\Enums\RiskPresence;
use App\Enums\Role;
use App\Enums\SignoffKind;
use App\Enums\SignoffSection;
use App\Enums\SignOutStatus;
use App\Enums\SiteMarkingStatus;
use App\Enums\SkinIntegrity;
use App\Enums\SscConsentStatus;
use App\Enums\SscMatchStatus;
use App\Enums\SscReadiness;
use App\Enums\SscStatus;
use App\Enums\SurgeryProfile;
use App\Enums\SurgicalPosition;
use App\Enums\SutureCondition;
use App\Enums\VitalSignPhase;
use App\Models\AnesthesiaType;
use App\Models\CaseSignoff;
use App\Models\FluidRecord;
use App\Models\Handover;
use App\Models\Instrument;
use App\Models\InstrumentCheck;
use App\Models\InstrumentCount;
use App\Models\IntraopRecord;
use App\Models\MedicalDiagnosis;
use App\Models\Medication;
use App\Models\NursingAction;
use App\Models\Patient;
use App\Models\PostopObservation;
use App\Models\PostopSummary;
use App\Models\PreopAssessment;
use App\Models\Room;
use App\Models\Specimen;
use App\Models\SscSignIn;
use App\Models\SscSignOut;
use App\Models\SscTimeOut;
use App\Models\SupportingExam;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use App\Models\User;
use App\Models\VitalSign;
use App\Services\HandoverSummaryBuilder;
use App\Support\AuditContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $password = config('app.demo_password');

        if (! is_string($password) || $password === '') {
            if (app()->runningUnitTests()) {
                $password = 'Password123!';
            } else {
                throw new RuntimeException('DEMO_PASSWORD belum dikonfigurasi pada .env.');
            }
        }

        $accounts = [
            [
                'username' => 'admin',
                'name' => 'Administrator Sistem OKA',
                'email' => 'admin@rme-oka.test',
                'role' => Role::Admin,
                'nurse_type' => null,
                'nip' => null,
                'sip' => null,
            ],
            [
                'username' => 'kepala',
                'name' => 'Ns. Ratna Puspita, S.Kep',
                'email' => 'kepala@rme-oka.test',
                'role' => Role::KepalaRuangan,
                'nurse_type' => null,
                'nip' => '197804122005012001',
                'sip' => null,
            ],
            [
                'username' => 'dr.operator1',
                'name' => 'dr. Bagus Prasetyo, Sp.B',
                'email' => 'operator1@rme-oka.test',
                'role' => Role::DokterOperator,
                'nurse_type' => null,
                'nip' => null,
                'sip' => 'SIP.503/0101/SP.B/2026',
            ],
            [
                'username' => 'dr.anestesi1',
                'name' => 'dr. Hendra Wijaya, Sp.An',
                'email' => 'anestesi1@rme-oka.test',
                'role' => Role::DokterAnestesi,
                'nurse_type' => null,
                'nip' => null,
                'sip' => 'SIP.503/0202/SP.AN/2026',
            ],
            [
                'username' => 'ns.sirkuler1',
                'name' => 'Ns. Sari Rahmawati, S.Kep',
                'email' => 'sirkuler1@rme-oka.test',
                'role' => Role::Perawat,
                'nurse_type' => NurseType::Sirkuler,
                'nip' => '198906152014022001',
                'sip' => null,
            ],
            [
                'username' => 'ns.instrumen1',
                'name' => 'Ns. Dimas Saputra, S.Kep',
                'email' => 'instrumen1@rme-oka.test',
                'role' => Role::Perawat,
                'nurse_type' => NurseType::Instrumen,
                'nip' => '199002202015031002',
                'sip' => null,
            ],
            [
                'username' => 'ns.anestesi1',
                'name' => 'Ns. Dewi Kartika, S.Kep',
                'email' => 'perawat.anestesi1@rme-oka.test',
                'role' => Role::Perawat,
                'nurse_type' => NurseType::Anestesi,
                'nip' => '199108112016042003',
                'sip' => null,
            ],
            [
                'username' => 'ns.sirkuler2',
                'name' => 'Ns. Fajar Nugroho, S.Kep',
                'email' => 'sirkuler2@rme-oka.test',
                'role' => Role::Perawat,
                'nurse_type' => NurseType::Sirkuler,
                'nip' => '199211052018011004',
                'sip' => null,
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'email_verified_at' => now(),
                    'password' => $password,
                    'role' => $account['role'],
                    'nurse_type' => $account['nurse_type'],
                    'nip' => $account['nip'],
                    'sip' => $account['sip'],
                    'is_active' => true,
                ],
            );
        }

        $this->seedClinicalDemoData();
    }

    private function seedClinicalDemoData(): void
    {
        $sirkuler1 = User::query()->where('username', 'ns.sirkuler1')->first();
        $instrumen1 = User::query()->where('username', 'ns.instrumen1')->first();
        $perawatAnestesi1 = User::query()->where('username', 'ns.anestesi1')->first();
        $operator1 = User::query()->where('username', 'dr.operator1')->first();
        $anestesi1 = User::query()->where('username', 'dr.anestesi1')->first();

        if (! $sirkuler1 || ! $instrumen1 || ! $perawatAnestesi1 || ! $operator1 || ! $anestesi1) {
            return;
        }

        $room1 = Room::query()->where('code', 'OK 1')->first();
        $room2 = Room::query()->where('code', 'OK 2')->first();

        $diagEdh = MedicalDiagnosis::query()->where('name', 'Epidural Hematoma (EDH)')->first();
        $diagFemur = MedicalDiagnosis::query()->where('name', 'Fraktur Femur')->first();

        $surgSaraf = SurgeryType::query()->where('code', SurgeryProfile::BedahSaraf->value)->first();
        $surgOrtopedi = SurgeryType::query()->where('code', SurgeryProfile::BedahOrtopedi->value)->first();

        $anestGa = AnesthesiaType::query()->where('code', 'GA')->first();
        $anestSab = AnesthesiaType::query()->where('code', 'SAB')->first();

        $sarafInstruments = Instrument::query()
            ->active()
            ->forProfile(SurgeryProfile::BedahSaraf)
            ->orderBy('id')
            ->take(3)
            ->get();

        $ortoInstruments = Instrument::query()
            ->active()
            ->forProfile(SurgeryProfile::BedahOrtopedi)
            ->orderBy('id')
            ->take(3)
            ->get();

        AuditContext::run(function () use (
            $sirkuler1,
            $instrumen1,
            $perawatAnestesi1,
            $operator1,
            $anestesi1,
            $room1,
            $room2,
            $diagEdh,
            $diagFemur,
            $surgSaraf,
            $surgOrtopedi,
            $anestGa,
            $anestSab,
            $sarafInstruments,
            $ortoInstruments,
        ): void {
            $patientA = Patient::query()->firstOrCreate(
                ['rm_number' => 'DEMO-001'],
                [
                    'name' => 'Pasien Contoh A',
                    'birth_date' => '1985-04-12',
                    'gender' => Gender::Male,
                    'identity_number' => '3509121204850001',
                    'address' => 'Jl. Contoh Sehat No. 10, Jember',
                    'phone' => '081200000001',
                    'guardian_name' => 'Ny. Aminah Wijaya',
                    'guardian_relation' => 'Istri',
                    'guardian_phone' => '081299000001',
                    'created_by' => $sirkuler1->id,
                ],
            );

            $patientB = Patient::query()->firstOrCreate(
                ['rm_number' => 'DEMO-002'],
                [
                    'name' => 'Pasien Contoh B',
                    'birth_date' => '1994-09-21',
                    'gender' => Gender::Female,
                    'identity_number' => '3509126109940002',
                    'address' => 'Jl. Contoh Harmoni No. 25, Jember',
                    'phone' => '081200000002',
                    'guardian_name' => 'Tn. Hadi Prasetyo',
                    'guardian_relation' => 'Suami',
                    'guardian_phone' => '081299000002',
                    'created_by' => $sirkuler1->id,
                ],
            );

            if (! SurgeryCase::query()->where('case_number', 'OK-20261119-001')->exists()) {
                $case1 = new SurgeryCase([
                    'patient_id' => $patientA->id,
                    'case_number' => 'OK-20261119-001',
                    'igd_visit_number' => 'IGD-20261119-001',
                    'admitted_at' => now('Asia/Jakarta')->subHours(2),
                    'igd_admitted_at' => now('Asia/Jakarta')->subHours(3),
                    'origin_ward' => 'IGD',
                    'origin_class' => 'Triase Merah',
                    'urgency_status' => 'emergency',
                    'scheduled_at' => now('Asia/Jakarta')->addHours(1),
                    'medical_diagnosis_id' => $diagEdh?->id,
                    'surgery_type_id' => $surgSaraf?->id,
                    'surgery_site' => 'Regio Temporal Kanan',
                    'anesthesia_type_id' => $anestGa?->id,
                    'dpjp_name' => $operator1->name,
                    'created_by' => $sirkuler1->id,
                ]);
                $case1->forceFill(['status' => CaseStatus::PreOp])->save();
            }

            $case2 = SurgeryCase::query()->where('case_number', 'OK-20261119-002')->first();

            if (! $case2) {
                $case2 = new SurgeryCase([
                    'patient_id' => $patientB->id,
                    'case_number' => 'OK-20261119-002',
                    'igd_visit_number' => 'IGD-20261119-002',
                    'admitted_at' => now('Asia/Jakarta')->subHours(4),
                    'igd_admitted_at' => now('Asia/Jakarta')->subHours(6),
                    'origin_ward' => 'IGD',
                    'origin_class' => 'Kelas 1',
                    'urgency_status' => 'emergency',
                    'scheduled_at' => now('Asia/Jakarta')->addHour(),
                    'medical_diagnosis_id' => $diagFemur?->id,
                    'surgery_type_id' => $surgOrtopedi?->id,
                    'surgery_site' => 'Femur Shaft Dekstra',
                    'anesthesia_type_id' => $anestSab?->id,
                    'dpjp_name' => $operator1->name,
                    'room_id' => $room1?->id,
                    'surgeon_id' => $operator1->id,
                    'anesthesiologist_id' => $anestesi1->id,
                    'circulating_nurse_id' => $sirkuler1->id,
                    'scrub_nurse_id' => $instrumen1->id,
                    'anesthesia_nurse_id' => $perawatAnestesi1->id,
                    'team_assigned_at' => now('Asia/Jakarta')->subMinutes(30),
                    'ready_at' => now('Asia/Jakarta')->subMinutes(10),
                    'stage_readiness_flags' => [
                        'preop_assessment_completed' => true,
                        'preop_checklist_ready' => true,
                        'instrument_check_completed' => true,
                        'sign_in_completed' => true,
                    ],
                    'created_by' => $sirkuler1->id,
                ]);
                $case2->forceFill(['status' => CaseStatus::SiapOperasi])->save();
            }

            $hasPreopSignoff = CaseSignoff::query()
                ->where('case_id', $case2->id)
                ->where('section', SignoffSection::PreOp)
                ->where('kind', SignoffKind::Signoff)
                ->exists();

            if (! $hasPreopSignoff) {
                CaseSignoff::query()->create([
                    'case_id' => $case2->id,
                    'section' => SignoffSection::PreOp,
                    'kind' => SignoffKind::Signoff,
                    'role_label' => 'perawat',
                    'user_id' => $sirkuler1->id,
                    'nip_snapshot' => $sirkuler1->nip,
                    'sip_snapshot' => null,
                    'signed_at' => now('Asia/Jakarta')->subMinutes(15),
                ]);
            }

            PreopAssessment::query()->firstOrCreate(
                ['case_id' => $case2->id],
                [
                    'chief_complaint' => 'Nyeri hebat pada paha kanan dan tidak dapat digerakkan setelah kecelakaan lalu lintas.',
                    'current_illness_history' => 'Pasien mengalami kecelakaan sepeda motor 5 jam lalu, paha kanan terbentur keras, tampak bengkak dan bengkok.',
                    'past_illness_history' => 'Tidak ada riwayat diabetes melitus atau hipertensi.',
                    'surgery_history' => 'Belum pernah menjalani operasi sebelumnya.',
                    'allergy_status' => AllergyStatus::TidakAda,
                    'allergy_detail' => null,
                    'medication_history' => 'Ketorolac 30mg IV di IGD.',
                    'anesthesia_history' => 'Belum pernah menjalani anestesi sebelumnya.',
                    'fasting_status' => true,
                    'fasting_since' => now('Asia/Jakarta')->subHours(6),
                    'skin_condition' => 'Terlihat bengkak pada regio femur kanan tanpa luka robek terbuka',
                    'anxiety_level' => AnxietyLevel::Ringan,
                    'surgery_knowledge' => 'Pasien dan keluarga memahami rencana tindakan ORIF Femur.',
                    'family_support' => 'Didampingi suami.',
                    'mental_readiness' => 'Pasien menyatakan siap menjalani tindakan operasi.',
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
                        'site_marked' => true,
                    ],
                    'implementation_actions' => 'Imobilisasi bidai femur dekstra, pasang infus RL 20 tpm, cek neurovaskular distal.',
                    'implementation_education' => 'Edukasi puasa, posisi immobilisasi, dan prosedur kamar operasi.',
                    'implementation_monitoring' => 'TTV stabil, pulsasi arteri dorsalis pedis teraba kuat.',
                    'implementation_response' => 'Pasien kooperatif dan memahami instruksi.',
                    'evaluation_condition' => 'Kesadaran compos mentis, nyeri terkontrol NRS 5.',
                    'evaluation_completeness' => 'Seluruh berkas, rontgen femur, dan set implan ortopedi siap.',
                    'readiness_status' => ReadinessStatus::Siap,
                    'evaluation_notes' => 'Siap dipindahkan ke Kamar Operasi OK 1.',
                    'clinical_details' => [
                        'incident' => [
                            'injury_cause' => 'Kecelakaan lalu lintas (sepeda motor)',
                            'incident_datetime' => now('Asia/Jakarta')->subHours(6)->format('Y-m-d\TH:i'),
                            'incident_location' => 'Jl. Kalimantan, Jember',
                            'pain_location' => 'Paha kanan (regio 1/3 tengah femur)',
                        ],
                        'extremity_physical' => [
                            'fracture_location' => 'Shaft',
                            'fracture_side' => 'Kanan',
                            'fracture_type' => 'Tertutup',
                            'fracture_pattern' => 'Transversal',
                            'skin_condition' => 'Utuh, tampak memar',
                            'open_wound' => 'Tidak ada',
                            'bleeding' => 'Minimal (tertutup)',
                            'swelling' => 'Ada pembengkakan sedang di paha kanan',
                            'deformity' => 'Tampak pemendekan & angulasi paha kanan',
                            'pain_nrs' => '5',
                            'extremity_movement' => 'Terbatas karena nyeri fraktur',
                        ],
                        'neurovascular' => [
                            'foot_skin_color' => 'Merah muda normal',
                            'foot_skin_temp' => 'Hangat',
                            'crt' => '< 2 detik',
                            'peripheral_pulse' => 'Arteri dorsalis pedis teraba kuat',
                            'foot_sensation' => 'Baik / utuh',
                            'toe_movement' => 'Dapat menggerakkan jari kaki kanan',
                            'tingling' => 'Tidak ada',
                            'numbness' => 'Tidak ada',
                        ],
                        'supporting_detail' => [
                            'xray_femur_result' => 'Fraktur komplit 1/3 tengah shaft femur dekstra posisi displaced ringan',
                            'xray_location_side' => 'Femur Dekstra Shaft',
                            'xray_pattern' => 'Transversal',
                            'ct_scan_note' => 'Tidak diperlukan',
                            'hb' => '12.6 g/dL',
                            'ht' => '38 %',
                            'leukosit' => '9.800 /uL',
                            'trombosit' => '265.000 /uL',
                            'blood_type' => 'O Rh+',
                            'pt_inr' => '11.2 dtk / INR 1.0',
                            'aptt' => '29.5 dtk',
                            'blood_glucose' => '108 mg/dL',
                            'electrolytes' => 'Na 139, K 4.1, Cl 102 mEq/L',
                            'renal_function' => 'Ur 24 / Cr 0.8 mg/dL',
                        ],
                        'prep_extra' => [
                            'decision_time' => now('Asia/Jakarta')->subHours(2)->format('Y-m-d\TH:i'),
                            'iv_fluid_detail' => 'IV line 18G tangan kiri, infus RL 20 tpm',
                            'blood_prep' => 'Crossmatch 2 kantong PRC siap di Bank Darah',
                            'antibiotic_instruction' => 'Cefazolin 2g IV profilaksis 30 menit pra-insisi',
                            'consent_signer' => 'Tn. Hadi Prasetyo (Suami)',
                            'implant_instrument_prep' => 'Set instrumen ortopedi & implan Plate/Screw disterilkan',
                        ],
                        'anesthesia_pre' => [
                            'airway_exam' => 'Mallampati I, gerak leher bebas',
                            'cardiopulmonary' => 'S1 S2 reguler, vesikuler +/+ tanpa ronki',
                            'asa_class' => 'ASA II',
                            'planned_anesthesia' => 'Spinal Anesthesia (SAB)',
                        ],
                        'anesthesia_exec' => [
                            'anesthesia_method' => 'Spinal Anesthesia (SAB)',
                            'airway_aid' => 'Nasal kanul 3 LPM',
                            'medications_summary' => 'Bupivacaine 0.5% Heavy 15 mg intratekal',
                            'fluids_given' => 'Ringer Laktat 500 ml loading',
                            'complications' => 'Tidak ada',
                        ],
                    ],
                    'created_by' => $sirkuler1->id,
                ],
            );

            VitalSign::query()->firstOrCreate(
                ['case_id' => $case2->id, 'phase' => VitalSignPhase::Pre],
                [
                    'recorded_at' => now('Asia/Jakarta')->subMinutes(20),
                    'bp_systolic' => 120,
                    'bp_diastolic' => 80,
                    'pulse' => 84,
                    'temperature' => 36.7,
                    'respiratory_rate' => 18,
                    'spo2' => 99,
                    'weight_kg' => 58.0,
                    'height_cm' => 162.0,
                    'consciousness' => Consciousness::ComposMentis,
                    'notes' => 'TTV stabil sebelum masuk kamar operasi.',
                    'created_by' => $sirkuler1->id,
                ],
            );

            SupportingExam::query()->firstOrCreate(
                ['case_id' => $case2->id, 'exam_type' => ExamType::Xray],
                [
                    'result' => 'Fraktur komplit 1/3 tengah femur dekstra pola transversal',
                    'exam_date' => now('Asia/Jakarta')->toDateString(),
                    'created_by' => $sirkuler1->id,
                ],
            );

            SupportingExam::query()->firstOrCreate(
                ['case_id' => $case2->id, 'exam_type' => ExamType::Hemoglobin],
                [
                    'result' => '12.6 g/dL (Normal)',
                    'exam_date' => now('Asia/Jakarta')->toDateString(),
                    'created_by' => $sirkuler1->id,
                ],
            );

            foreach ($ortoInstruments as $instrument) {
                InstrumentCheck::query()->firstOrCreate(
                    ['case_id' => $case2->id, 'instrument_id' => $instrument->id],
                    [
                        'is_available' => true,
                        'is_sterile' => true,
                        'is_functional' => true,
                        'is_rusty' => false,
                        'note' => null,
                        'checked_by' => $instrumen1->id,
                        'checked_at' => now('Asia/Jakarta')->subMinutes(20),
                    ],
                );
            }

            SscSignIn::query()->firstOrCreate(
                ['case_id' => $case2->id],
                [
                    'status' => SscStatus::Selesai,
                    'identity_confirmed' => SscMatchStatus::Sesuai,
                    'procedure_confirmed' => SscMatchStatus::Sesuai,
                    'site_confirmed' => SscMatchStatus::Sesuai,
                    'consent_surgery' => SscConsentStatus::Ada,
                    'consent_anesthesia' => SscConsentStatus::Ada,
                    'site_marking' => SiteMarkingStatus::Ada,
                    'readiness' => [
                        'anesthesia_machine' => SscReadiness::Siap->value,
                        'oxygen_source' => SscReadiness::Siap->value,
                        'anesthesia_drugs' => SscReadiness::Siap->value,
                        'airway_aids' => SscReadiness::Siap->value,
                        'suction' => SscReadiness::Siap->value,
                        'monitoring_device' => SscReadiness::Siap->value,
                        'emergency_drugs' => SscReadiness::Siap->value,
                    ],
                    'pulse_ox_attached' => true,
                    'pulse_ox_functional' => true,
                    'spo2' => 99,
                    'pulse' => 84,
                    'pulse_ox_note' => 'Terpasang pada jari telunjuk kiri, sinyal baik.',
                    'allergy_status' => AllergyStatus::TidakAda,
                    'difficult_airway_risk' => RiskPresence::TidakAda,
                    'aspiration_risk' => RiskPresence::TidakAda,
                    'anesthesiologist_assessment' => 'Jalan napas Mallampati I, puasa > 6 jam, risiko aspirasi rendah.',
                    'anticipation_plan' => 'Persiapan anestesi spinal standar, pantau hemodinamik.',
                    'airway_equipment_ready' => SscReadiness::Siap,
                    'bleeding_risk' => RiskPresence::Ada,
                    'estimated_blood_loss_ml' => 450,
                    'iv_access' => IvAccessStatus::Memadai,
                    'fluids_ready' => SscReadiness::Siap,
                    'blood_ready' => BloodReadiness::Siap,
                    'bleeding_note' => 'Terpasang IV line 18G paten dan darah siap di bank darah.',
                    'notes_followup' => null,
                    'signed_at' => now('Asia/Jakarta')->subMinutes(12),
                    'filled_by' => $sirkuler1->id,
                    'created_by' => $sirkuler1->id,
                ],
            );

            $this->seedCompletedNeuroDemoCase(
                $sirkuler1,
                $instrumen1,
                $perawatAnestesi1,
                $operator1,
                $anestesi1,
                $room1,
                $diagEdh,
                $surgSaraf,
                $anestGa,
                $sarafInstruments,
            );

            $this->seedIntraopOrthoDemoCase(
                $sirkuler1,
                $instrumen1,
                $perawatAnestesi1,
                $operator1,
                $anestesi1,
                $room2,
                $diagFemur,
                $surgOrtopedi,
                $anestGa,
                $ortoInstruments,
            );
        }, null, null, $sirkuler1);
    }

    /**
     * @param  Collection<int, Instrument>  $sarafInstruments
     */
    private function seedCompletedNeuroDemoCase(
        User $sirkuler1,
        User $instrumen1,
        User $perawatAnestesi1,
        User $operator1,
        User $anestesi1,
        ?Room $room1,
        ?MedicalDiagnosis $diagEdh,
        ?SurgeryType $surgSaraf,
        ?AnesthesiaType $anestGa,
        $sarafInstruments,
    ): void {
        $patientC = Patient::query()->firstOrCreate(
            ['rm_number' => 'DEMO-003'],
            [
                'name' => 'Pasien Contoh C',
                'birth_date' => '1979-02-18',
                'gender' => Gender::Male,
                'identity_number' => '3509121802790003',
                'address' => 'Jl. Contoh Kamboja No. 8, Jember',
                'phone' => '081200000003',
                'guardian_name' => 'Ny. Sri Wahyuni',
                'guardian_relation' => 'Istri',
                'guardian_phone' => '081299000003',
                'created_by' => $sirkuler1->id,
            ],
        );

        $baseTime = now('Asia/Jakarta')->subDays(2)->setTime(8, 0);

        $case3 = SurgeryCase::query()->where('case_number', 'OK-20261119-003')->first();
        if (! $case3) {
            $case3 = new SurgeryCase([
                'patient_id' => $patientC->id,
                'case_number' => 'OK-20261119-003',
                'igd_visit_number' => 'IGD-20261117-008',
                'admitted_at' => $baseTime->copy()->subHours(2),
                'igd_admitted_at' => $baseTime->copy()->subHours(3),
                'origin_ward' => 'IGD',
                'origin_class' => 'Triase Merah',
                'urgency_status' => 'emergency',
                'scheduled_at' => $baseTime->copy(),
                'medical_diagnosis_id' => $diagEdh?->id,
                'surgery_type_id' => $surgSaraf?->id,
                'surgery_site' => 'Regio Temporal Kanan',
                'anesthesia_type_id' => $anestGa?->id,
                'dpjp_name' => $operator1->name,
                'operation_plan_actual' => 'Kraniotomi Evakuasi Epidural Hematoma (EDH) Temporal Dekstra',
                'postop_diagnosis' => 'Post Kraniotomi Evakuasi Epidural Hematoma Temporal Dekstra',
                'room_id' => $room1?->id,
                'surgeon_id' => $operator1->id,
                'anesthesiologist_id' => $anestesi1->id,
                'circulating_nurse_id' => $sirkuler1->id,
                'scrub_nurse_id' => $instrumen1->id,
                'anesthesia_nurse_id' => $perawatAnestesi1->id,
                'team_assigned_at' => $baseTime->copy()->subMinutes(40),
                'ready_at' => $baseTime->copy()->subMinutes(15),
                'entered_or_at' => $baseTime->copy(),
                'anesthesia_start_at' => $baseTime->copy()->addMinutes(10),
                'incision_at' => $baseTime->copy()->addMinutes(20),
                'surgery_end_at' => $baseTime->copy()->addMinutes(95),
                'left_or_at' => $baseTime->copy()->addMinutes(110),
                'anesthesia_end_at' => $baseTime->copy()->addMinutes(105),
                'completed_at' => $baseTime->copy()->addHours(3),
                'created_by' => $sirkuler1->id,
            ]);
            $case3->forceFill(['status' => CaseStatus::Selesai])->save();
        }

        PreopAssessment::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'chief_complaint' => 'Penurunan kesadaran setelah jatuh dari tangga, didahului sakit kepala hebat dan muntah.',
                'current_illness_history' => 'Sempat sadar sebentar setelah benturan kepala kanan lalu kesadaran menurun progresif (lucid interval).',
                'past_illness_history' => 'Tidak ada hipertensi kronis maupun gangguan pembekuan darah.',
                'surgery_history' => 'Belum pernah menjalani operasi.',
                'allergy_status' => AllergyStatus::TidakAda,
                'fasting_status' => true,
                'fasting_since' => $baseTime->copy()->subHours(6),
                'skin_condition' => 'Tampak jejas dan hematoma subgaleal di regio temporal kanan.',
                'anxiety_level' => AnxietyLevel::Sedang,
                'surgery_knowledge' => 'Keluarga telah dijelaskan tindakan kraniotomi emergensi.',
                'family_support' => 'Didampingi istri di ruang tunggu OK.',
                'mental_readiness' => 'Persetujuan tindakan darurat ditandatangani istri pasien.',
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
                    'site_marked' => true,
                ],
                'implementation_actions' => 'Elevasi kepala 30 derajat, pemasangan 2 jalur IV besar, persiapan kraniotomi emergensi.',
                'evaluation_condition' => 'GCS E2 V3 M5 (10), pupil anisokor kanan 4 mm melambat.',
                'evaluation_completeness' => 'CT Scan kepala, lab lengkap, dan set kraniotomi siap.',
                'readiness_status' => ReadinessStatus::Siap,
                'clinical_details' => [
                    'incident' => [
                        'injury_cause' => 'Terjatuh dari tangga setinggi 2 meter dengan benturan kepala sisi kanan',
                        'incident_datetime' => $baseTime->copy()->subHours(5)->format('Y-m-d\TH:i'),
                        'incident_location' => 'Rumah pasien, Kec. Sumbersari',
                        'headache_history' => 'Ada sakit kepala hebat progresif',
                        'nausea_vomiting' => 'Ya, muntah proyektil 2x di IGD',
                        'loss_of_consciousness' => 'Ya, riwayat lucid interval',
                        'seizure_history' => 'Tidak ada',
                        'anticoagulant_use' => 'Tidak mengonsumsi obat pengencer darah',
                    ],
                    'neurological' => [
                        'consciousness_level' => 'Somnolen',
                        'gcs_e' => '2',
                        'gcs_v' => '3',
                        'gcs_m' => '5',
                        'pupil_size_right' => '4 mm',
                        'pupil_size_left' => '2.5 mm',
                        'pupil_light_reaction' => 'Kanan lambat (+), Kiri cepat (+)',
                        'motor_right' => '5/5/5/5',
                        'motor_left' => '3/3/3/3 (hemiparesis kiri)',
                        'sensory_response' => 'Menarik ekstremitas terhadap rangsang nyeri',
                        'seizure_status' => 'Tidak ada kejang saat observasi',
                        'neuro_change_during_monitoring' => 'Penurunan GCS dari 13 menjadi 10 dalam 1 jam terakhir',
                    ],
                    'vital_extra' => [
                        'gds' => '132 mg/dL',
                    ],
                    'supporting_detail' => [
                        'ct_scan_result' => 'Hematoma bikonveks epidural regio temporal kanan volume ~45 ml',
                        'hematoma_location' => 'Temporal',
                        'hematoma_side' => 'Kanan',
                        'hematoma_thickness' => '24 mm (volume ~45 ml)',
                        'midline_shift' => 'Ada (bergeser ke kiri 7 mm)',
                        'skull_fracture' => 'Ada fraktur linier os temporal kanan',
                        'associated_bleeding' => 'Tidak ada perdarahan intraserebral',
                        'hb' => '13.1 g/dL',
                        'ht' => '39 %',
                        'leukosit' => '11.500 /uL',
                        'trombosit' => '240.000 /uL',
                        'blood_type' => 'A Rh+',
                        'electrolytes' => 'Na 141, K 3.9, Cl 103 mEq/L',
                        'blood_glucose' => '132 mg/dL',
                        'renal_function' => 'Ur 28 / Cr 0.9 mg/dL',
                    ],
                    'prep_extra' => [
                        'decision_time' => $baseTime->copy()->subHours(1)->format('Y-m-d\TH:i'),
                        'iv_fluid_detail' => 'IV 16G & 18G, NaCl 0,9% 20 tpm',
                        'blood_prep' => 'Siap PRC 2 kantong crossmatch sesuai',
                        'antibiotic_instruction' => 'Ceftriaxone 2g IV sebelum insisi',
                        'consent_signer' => 'Ny. Sri Wahyuni',
                        'consent_relation' => 'Istri',
                        'emergency_consent_doc' => 'Informed consent tindakan darurat ditandatangani keluarga kandung (istri)',
                    ],
                    'anesthesia_pre' => [
                        'airway_exam' => 'Mallampati II, risiko aspirasi darurat',
                        'cardiopulmonary' => 'TD 148/90, nadi 64, bunyi jantung reguler, paru bersih',
                        'asa_class' => 'ASA III E',
                        'planned_anesthesia' => 'General Anesthesia (Intubasi ETT)',
                    ],
                    'anesthesia_exec' => [
                        'anesthesia_method' => 'Anestesi Umum (GA)',
                        'ett_type_size' => 'Non-kinking ETT No. 7.5',
                        'ett_depth_cm' => '21 cm di batas bibir',
                        'ventilator_setting' => 'VCV, TV 480 ml, RR 14, PEEP 5, FiO2 50%',
                        'medications_summary' => 'Propofol 120 mg, Fentanyl 100 mcg, Rocuronium 50 mg, Sevoflurane',
                        'fluids_given' => 'NaCl 0,9% 1000 ml, Manitol 20% 250 ml',
                        'complications' => 'Tidak ada komplikasi anestesi',
                    ],
                ],
                'created_by' => $sirkuler1->id,
            ],
        );

        VitalSign::query()->firstOrCreate(
            ['case_id' => $case3->id, 'phase' => VitalSignPhase::Pre],
            [
                'recorded_at' => $baseTime->copy()->subMinutes(30),
                'bp_systolic' => 148,
                'bp_diastolic' => 90,
                'pulse' => 64,
                'temperature' => 36.6,
                'respiratory_rate' => 20,
                'spo2' => 98,
                'weight_kg' => 68.0,
                'height_cm' => 170.0,
                'consciousness' => Consciousness::Somnolen,
                'created_by' => $sirkuler1->id,
            ],
        );

        SupportingExam::query()->firstOrCreate(
            ['case_id' => $case3->id, 'exam_type' => ExamType::CtScan],
            [
                'result' => 'Epidural Hematoma temporal kanan 24 mm, midline shift 7 mm ke kiri, fraktur os temporal',
                'exam_date' => $baseTime->copy()->toDateString(),
                'created_by' => $sirkuler1->id,
            ],
        );

        foreach ($sarafInstruments as $instrument) {
            InstrumentCheck::query()->firstOrCreate(
                ['case_id' => $case3->id, 'instrument_id' => $instrument->id],
                [
                    'is_available' => true,
                    'is_sterile' => true,
                    'is_functional' => true,
                    'is_rusty' => false,
                    'checked_by' => $instrumen1->id,
                    'checked_at' => $baseTime->copy()->subMinutes(25),
                ],
            );
        }

        SscSignIn::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'status' => SscStatus::Selesai,
                'identity_confirmed' => SscMatchStatus::Sesuai,
                'procedure_confirmed' => SscMatchStatus::Sesuai,
                'site_confirmed' => SscMatchStatus::Sesuai,
                'consent_surgery' => SscConsentStatus::Ada,
                'consent_anesthesia' => SscConsentStatus::Ada,
                'site_marking' => SiteMarkingStatus::Ada,
                'readiness' => [
                    'anesthesia_machine' => SscReadiness::Siap->value,
                    'oxygen_source' => SscReadiness::Siap->value,
                    'anesthesia_drugs' => SscReadiness::Siap->value,
                    'airway_aids' => SscReadiness::Siap->value,
                    'suction' => SscReadiness::Siap->value,
                    'monitoring_device' => SscReadiness::Siap->value,
                    'emergency_drugs' => SscReadiness::Siap->value,
                ],
                'pulse_ox_attached' => true,
                'pulse_ox_functional' => true,
                'spo2' => 98,
                'pulse' => 64,
                'allergy_status' => AllergyStatus::TidakAda,
                'difficult_airway_risk' => RiskPresence::TidakAda,
                'aspiration_risk' => RiskPresence::Ada,
                'anesthesiologist_assessment' => 'ASA III E, induksi cepat RSI untuk proteksi jalan napas.',
                'airway_equipment_ready' => SscReadiness::Siap,
                'bleeding_risk' => RiskPresence::Ada,
                'estimated_blood_loss_ml' => 500,
                'iv_access' => IvAccessStatus::Memadai,
                'fluids_ready' => SscReadiness::Siap,
                'blood_ready' => BloodReadiness::Siap,
                'signed_at' => $baseTime->copy()->addMinutes(5),
                'filled_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        SscTimeOut::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'status' => SscStatus::Selesai,
                'intro_circulating' => true,
                'intro_scrub' => true,
                'intro_anesthesia_nurse' => true,
                'intro_surgeon' => true,
                'intro_anesthesiologist' => true,
                'identity_procedure_site_confirmed' => true,
                'antibiotic_prophylaxis' => 'ya',
                'antibiotic_name' => 'Ceftriaxone 2g IV',
                'antibiotic_given_at' => $baseTime->copy()->addMinutes(12),
                'antibiotic_confirmed_by' => $perawatAnestesi1->name,
                'surgeon_critical_steps' => 'ya',
                'surgeon_critical_steps_text' => 'Siapkan bipolar forceps, agen hemostatik, dan bone wax untuk arteri meningea media.',
                'surgeon_estimated_duration_min' => 90,
                'surgeon_estimated_blood_loss_ml' => 450,
                'anesthesia_special_condition' => 'ya',
                'anesthesia_risk' => 'ya',
                'anesthesia_note' => 'Pertahankan MAP stabil dan normokapnia (EtCO2 32-35 mmHg).',
                'nurse_sterility_confirmed' => true,
                'nurse_indicator_checked' => true,
                'equipment_problem' => 'tidak',
                'other_concern' => 'tidak',
                'imaging_needed' => 'ya',
                'imaging_displayed' => 'ya',
                'imaging_matches' => 'ya',
                'signed_at' => $baseTime->copy()->addMinutes(18),
                'filled_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        IntraopRecord::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'position' => SurgicalPosition::Supine,
                'pressure_protection' => ['ganjal_kepala_leher', 'safety_belt', 'armboard'],
                'skin_antiseptic' => ['povidone_iodine'],
                'grounding_plate_site' => 'Paha kiri lateral',
                'skin_condition_pre' => SkinIntegrity::Utuh,
                'skin_condition_post' => SkinIntegrity::Utuh,
                'draping_done' => true,
                'warming' => ['blanket_warmer'],
                'room_temperature_c' => 20.5,
                'count_verification' => CountVerification::LengkapSesuai,
                'count_discrepancy_actions' => [],
                'has_complication' => false,
                'procedure_details' => [
                    'incision_location_side' => 'Regio temporal-parietal kanan (question mark incision)',
                    'craniotomy_location' => 'Temporal-parietal dekstra',
                    'bone_tissue_condition' => 'Tampak garis fraktur os temporal kanan memanjang ke basis',
                    'hematoma_volume_condition' => 'Bekuan darah epidural sekitar 45 ml warna kehitaman menekan duramater',
                    'craniotomy_opening_process' => 'Dilakukan 3 burr hole dengan perforator lalu bone flap dibuka dengan craniotome',
                    'hematoma_evacuation_process' => 'Evakuasi total bekuan darah dengan suction irrigation dan disektor',
                    'hemostasis_action' => 'Koagulasi sumber perdarahan cabang arteri meningea media dengan bipolar forceps & surgicel',
                    'brain_tissue_condition' => 'Duramater utuh, pulsasi otak membaik dan tidak membengkak setelah evakuasi',
                    'bone_flap_management' => 'Bone flap dikembalikan dan difiksasi dengan miniplate/benang jahit non-absorbable',
                    'drain_placement' => 'Dipasang vacuum drain subgaleal No. 14',
                    'wound_closure' => 'Penutupan lapis demi lapis galea dengan Vicryl 2-0 dan kulit dengan Silk 3-0',
                    'postop_wound_condition' => 'Luka operasi kering tertutup balutan steril rapat',
                    'blood_transfusion_given' => true,
                    'blood_product_type_amount' => 'PRC 1 kantong (250 ml)',
                    'cautery_usage' => 'Bipolar forceps daya 25 W, monopolar 30 W',
                    'equipment_usage_notes' => 'Cranial drill, craniotome, headlight, dan suction berfungsi baik',
                ],
                'created_by' => $sirkuler1->id,
            ],
        );

        $intraVitals = [
            ['offset' => 25, 'sys' => 128, 'dia' => 78, 'pulse' => 78, 'temp' => 36.5, 'rr' => 14, 'spo2' => 99],
            ['offset' => 55, 'sys' => 118, 'dia' => 74, 'pulse' => 74, 'temp' => 36.4, 'rr' => 14, 'spo2' => 100],
            ['offset' => 85, 'sys' => 122, 'dia' => 76, 'pulse' => 76, 'temp' => 36.5, 'rr' => 14, 'spo2' => 99],
        ];
        if (! VitalSign::query()->where('case_id', $case3->id)->where('phase', VitalSignPhase::Intra)->exists()) {
            foreach ($intraVitals as $iv) {
                VitalSign::query()->create([
                    'case_id' => $case3->id,
                    'phase' => VitalSignPhase::Intra,
                    'recorded_at' => $baseTime->copy()->addMinutes($iv['offset']),
                    'bp_systolic' => $iv['sys'],
                    'bp_diastolic' => $iv['dia'],
                    'pulse' => $iv['pulse'],
                    'temperature' => $iv['temp'],
                    'respiratory_rate' => $iv['rr'],
                    'spo2' => $iv['spo2'],
                    'breathing_pattern' => BreathingPattern::Eupnea,
                    'oxygen_device' => OxygenDevice::Ventilator,
                    'oxygen_flow_lpm' => 3,
                    'consciousness' => Consciousness::ComposMentis,
                    'created_by' => $perawatAnestesi1->id,
                ]);
            }
        }

        if (! Medication::query()->where('case_id', $case3->id)->exists()) {
            Medication::query()->create([
                'case_id' => $case3->id,
                'phase' => VitalSignPhase::Intra,
                'name' => 'Ceftriaxone',
                'dose' => '2 gram',
                'route' => 'IV',
                'given_at' => $baseTime->copy()->addMinutes(12),
                'note' => 'Antibiotik profilaksis',
                'created_by' => $perawatAnestesi1->id,
            ]);
        }

        if (! FluidRecord::query()->where('case_id', $case3->id)->exists()) {
            FluidRecord::query()->create([
                'case_id' => $case3->id,
                'phase' => VitalSignPhase::Intra,
                'recorded_at' => $baseTime->copy()->addMinutes(30),
                'direction' => FluidDirection::Intake,
                'kind' => FluidKind::Kristaloid,
                'volume_ml' => 1000,
                'note' => 'NaCl 0,9%',
                'created_by' => $sirkuler1->id,
            ]);
            FluidRecord::query()->create([
                'case_id' => $case3->id,
                'phase' => VitalSignPhase::Intra,
                'recorded_at' => $baseTime->copy()->addMinutes(85),
                'direction' => FluidDirection::Output,
                'kind' => FluidKind::Perdarahan,
                'volume_ml' => 280,
                'note' => 'Perdarahan saat pembukaan tulang & evakuasi hematoma',
                'created_by' => $sirkuler1->id,
            ]);
        }

        if (! NursingAction::query()->where('case_id', $case3->id)->exists()) {
            NursingAction::query()->create([
                'case_id' => $case3->id,
                'phase' => NursingPhase::Intra,
                'performed_at' => $baseTime->copy()->addMinutes(15),
                'performed_by_role' => 'sirkuler',
                'action' => 'Desinfeksi kulit kepala, pemasangan head holder, dan draping steril kraniotomi',
                'evaluation' => 'Area operasi steril terjaga baik.',
                'created_by' => $sirkuler1->id,
            ]);
        }

        foreach (InstrumentCountItem::cases() as $item) {
            InstrumentCount::query()->firstOrCreate(
                ['case_id' => $case3->id, 'item' => $item],
                [
                    'initial_count' => 10,
                    'additional_count' => 0,
                    'final_count' => 10,
                    'status' => InstrumentCountStatus::Sesuai,
                    'created_by' => $instrumen1->id,
                ],
            );
        }

        Specimen::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'has_specimen' => false,
                'created_by' => $sirkuler1->id,
            ],
        );

        SscSignOut::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'status' => SignOutStatus::Terverifikasi,
                'q1_procedure_confirmed' => true,
                'q2_counts_correct' => true,
                'q3_specimen_labeled' => 'tidak_berlaku',
                'q4_equipment_problem' => false,
                'q5_recovery_discussed' => true,
                'extra_6_procedure_matches' => 'sesuai',
                'extra_7_changes_recorded' => 'tidak_ada_perubahan',
                'extra_8_discrepancy_followup' => 'tidak_ada_selisih',
                'extra_9_specimen_matches' => 'tidak_ada_spesimen',
                'extra_10_equipment_issue_recorded' => 'tidak_ada_masalah',
                'extra_11_complication_communicated' => 'tidak_ada_komplikasi',
                'extra_12_postop_monitoring_discussed' => 'sudah',
                'extra_13_devices_instructions' => 'sudah',
                'recovery_notes' => 'Pantau GCS, ukuran pupil tiap 15 menit, dan produksi drain subgaleal di ICU.',
                'signed_at' => $baseTime->copy()->addMinutes(95),
                'filled_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        $signoffsToEnsure = [
            ['section' => SignoffSection::PreOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'perawat', 'user' => $sirkuler1, 'at' => $baseTime->copy()->subMinutes(20)],
            ['section' => SignoffSection::PreOp, 'kind' => SignoffKind::Verification, 'role_label' => 'dokter_operator', 'user' => $operator1, 'at' => $baseTime->copy()->subMinutes(16)],
            ['section' => SignoffSection::SignIn, 'kind' => SignoffKind::Verification, 'role_label' => 'dokter_anestesi', 'user' => $anestesi1, 'at' => $baseTime->copy()->addMinutes(7)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'perawat_sirkuler', 'user' => $sirkuler1, 'at' => $baseTime->copy()->addMinutes(96)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'perawat_instrumen', 'user' => $instrumen1, 'at' => $baseTime->copy()->addMinutes(97)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'dokter_anestesi', 'user' => $anestesi1, 'at' => $baseTime->copy()->addMinutes(98)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'dokter_operator', 'user' => $operator1, 'at' => $baseTime->copy()->addMinutes(99)],
            ['section' => SignoffSection::SignOut, 'kind' => SignoffKind::Verification, 'role_label' => 'dokter_operator', 'user' => $operator1, 'at' => $baseTime->copy()->addMinutes(100)],
            ['section' => SignoffSection::PostOp, 'kind' => SignoffKind::Verification, 'role_label' => 'dokter_anestesi', 'user' => $anestesi1, 'at' => $baseTime->copy()->addMinutes(160)],
        ];

        foreach ($signoffsToEnsure as $sf) {
            CaseSignoff::query()->firstOrCreate(
                [
                    'case_id' => $case3->id,
                    'section' => $sf['section'],
                    'kind' => $sf['kind'],
                    'role_label' => $sf['role_label'],
                ],
                [
                    'user_id' => $sf['user']->id,
                    'nip_snapshot' => $sf['user']->nip,
                    'sip_snapshot' => $sf['user']->sip,
                    'signed_at' => $sf['at'],
                ],
            );
        }

        if (! PostopObservation::query()->where('case_id', $case3->id)->exists()) {
            PostopObservation::query()->create([
                'case_id' => $case3->id,
                'recorded_at' => $baseTime->copy()->addMinutes(125),
                'gcs_eye' => 4,
                'gcs_verbal' => 5,
                'gcs_motor' => 6,
                'general_condition' => GeneralCondition::Baik,
                'response' => PatientResponse::Stabil,
                'airway_patency' => AirwayPatency::Paten,
                'wound_location' => 'Temporal kanan',
                'dressing' => DressingCondition::Kering,
                'bleeding_seepage' => false,
                'swelling_hematoma' => false,
                'suture_condition' => SutureCondition::RapiUtuh,
                'pain_nrs' => 3,
                'pain_location' => 'Area luka kraniotomi temporal kanan',
                'anesthesia_recovery' => Consciousness::ComposMentis,
                'nausea_vomiting' => false,
                'shivering_hypothermia' => false,
                'complications' => [],
                'notes' => 'Pasien sadar penuh post evakuasi EDH, pupil isokor kembali.',
                'clinical_details' => [
                    'pupil_size_right' => '2.5 mm',
                    'pupil_size_left' => '2.5 mm',
                    'pupil_light_reaction' => 'Isokor +/+ cepat',
                    'extremity_motor' => 'Keempat ekstremitas bergerak aktif (5/5/5/5)',
                    'anesthesia_recovery_score' => 'Aldrete Score 9/10',
                    'medications_given' => 'Paracetamol 1g IV, Ondansetron 4 mg IV, Phenytoin 100 mg IV',
                    'doctor_postop_instructions' => 'Head up 30 derajat, pantau GCS & pupil ketat tiap 15 menit',
                    'neuro_followup_monitoring' => 'Observasi tanda peningkatan TIK dan produksi drain subgaleal',
                ],
                'created_by' => $sirkuler1->id,
            ]);

            VitalSign::query()->create([
                'case_id' => $case3->id,
                'phase' => VitalSignPhase::Post,
                'recorded_at' => $baseTime->copy()->addMinutes(125),
                'bp_systolic' => 124,
                'bp_diastolic' => 78,
                'pulse' => 76,
                'temperature' => 36.6,
                'respiratory_rate' => 18,
                'spo2' => 99,
                'breathing_pattern' => BreathingPattern::Eupnea,
                'oxygen_device' => OxygenDevice::NasalKanul,
                'oxygen_flow_lpm' => 3,
                'consciousness' => Consciousness::ComposMentis,
                'created_by' => $sirkuler1->id,
            ]);
        }

        PostopSummary::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'ready_to_transfer' => true,
                'pre_transfer_condition_notes' => 'GCS 15 compos mentis, pupil isokor 2.5/2.5 mm +/+, TTV stabil, layak pindah ke ICU.',
                'followup_plan' => [
                    'medications_given' => 'Ceftriaxone 2x1g IV, Phenytoin 3x100mg IV, Paracetamol 3x1g IV',
                    'iv_fluid_plan' => 'NaCl 0,9% 20 tpm',
                    'doctor_postop_instructions' => 'Head up 30 derajat, puasa bertahap hingga sadar penuh bebas mual',
                    'neuro_followup_monitoring' => 'Pantau GCS, pupil, kekuatan motorik, dan balans cairan tiap jam di ICU',
                ],
                'assessed_at' => $baseTime->copy()->addMinutes(150),
                'assessed_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        if (! Handover::query()->where('case_id', $case3->id)->exists()) {
            $summarySnapshot = app(HandoverSummaryBuilder::class)->build($case3->fresh() ?? $case3);
            Handover::query()->create([
                'case_id' => $case3->id,
                'destination' => HandoverDestination::Icu,
                'destination_detail' => 'ICU Bed 02',
                'documents_complete' => true,
                'handed_by_id' => $sirkuler1->id,
                'received_by_name' => 'Ns. Budi Santoso, S.Kep (Perawat ICU)',
                'handed_over_at' => $baseTime->copy()->addHours(3),
                'ongoing_issues' => 'Pantau luka kraniotomi temporal kanan & drain subgaleal.',
                'follow_up_instructions' => 'Lanjutkan infus NaCl 0,9% 20 tpm, pantau GCS & pupil tiap jam, pertahankan head up 30 derajat.',
                'summary_snapshot' => $summarySnapshot,
                'created_by' => $sirkuler1->id,
            ]);
        }
    }

    /**
     * @param  Collection<int, Instrument>  $ortoInstruments
     */
    private function seedIntraopOrthoDemoCase(
        User $sirkuler1,
        User $instrumen1,
        User $perawatAnestesi1,
        User $operator1,
        User $anestesi1,
        ?Room $room2,
        ?MedicalDiagnosis $diagFemur,
        ?SurgeryType $surgOrtopedi,
        ?AnesthesiaType $anestGa,
        $ortoInstruments,
    ): void {
        $patientD = Patient::query()->firstOrCreate(
            ['rm_number' => 'DEMO-004'],
            [
                'name' => 'Pasien Contoh D',
                'birth_date' => '1988-07-05',
                'gender' => Gender::Female,
                'identity_number' => '3509124507880004',
                'address' => 'Jl. Contoh Kenanga No. 17, Jember',
                'phone' => '081200000004',
                'guardian_name' => 'Tn. Agus Hartono',
                'guardian_relation' => 'Saudara Kandung',
                'guardian_phone' => '081299000004',
                'created_by' => $sirkuler1->id,
            ],
        );

        $baseTime = now('Asia/Jakarta')->subHours(2);

        $case4 = SurgeryCase::query()->where('case_number', 'OK-20261119-004')->first();
        if (! $case4) {
            $case4 = new SurgeryCase([
                'patient_id' => $patientD->id,
                'case_number' => 'OK-20261119-004',
                'igd_visit_number' => 'IGD-20261119-004',
                'admitted_at' => $baseTime->copy()->subHours(2),
                'igd_admitted_at' => $baseTime->copy()->subHours(4),
                'origin_ward' => 'IGD',
                'origin_class' => 'Kelas 2',
                'urgency_status' => 'emergency',
                'scheduled_at' => $baseTime->copy(),
                'medical_diagnosis_id' => $diagFemur?->id,
                'surgery_type_id' => $surgOrtopedi?->id,
                'surgery_site' => 'Femur Sinistra 1/3 Distal',
                'anesthesia_type_id' => $anestGa?->id,
                'dpjp_name' => $operator1->name,
                'operation_plan_actual' => 'ORIF Locking Plate Femur Distal Sinistra',
                'room_id' => $room2?->id,
                'surgeon_id' => $operator1->id,
                'anesthesiologist_id' => $anestesi1->id,
                'circulating_nurse_id' => $sirkuler1->id,
                'scrub_nurse_id' => $instrumen1->id,
                'anesthesia_nurse_id' => $perawatAnestesi1->id,
                'team_assigned_at' => $baseTime->copy()->subMinutes(45),
                'ready_at' => $baseTime->copy()->subMinutes(15),
                'entered_or_at' => $baseTime->copy(),
                'anesthesia_start_at' => $baseTime->copy()->addMinutes(10),
                'incision_at' => $baseTime->copy()->addMinutes(22),
                'created_by' => $sirkuler1->id,
            ]);
            $case4->forceFill(['status' => CaseStatus::IntraOp])->save();
        }

        foreach ($ortoInstruments as $instrument) {
            InstrumentCheck::query()->firstOrCreate(
                ['case_id' => $case4->id, 'instrument_id' => $instrument->id],
                [
                    'is_available' => true,
                    'is_sterile' => true,
                    'is_functional' => true,
                    'is_rusty' => false,
                    'checked_by' => $instrumen1->id,
                    'checked_at' => $baseTime->copy()->subMinutes(25),
                ],
            );
        }

        SscSignIn::query()->firstOrCreate(
            ['case_id' => $case4->id],
            [
                'status' => SscStatus::Selesai,
                'identity_confirmed' => SscMatchStatus::Sesuai,
                'procedure_confirmed' => SscMatchStatus::Sesuai,
                'site_confirmed' => SscMatchStatus::Sesuai,
                'consent_surgery' => SscConsentStatus::Ada,
                'consent_anesthesia' => SscConsentStatus::Ada,
                'site_marking' => SiteMarkingStatus::Ada,
                'readiness' => [
                    'anesthesia_machine' => SscReadiness::Siap->value,
                    'oxygen_source' => SscReadiness::Siap->value,
                    'anesthesia_drugs' => SscReadiness::Siap->value,
                    'airway_aids' => SscReadiness::Siap->value,
                    'suction' => SscReadiness::Siap->value,
                    'monitoring_device' => SscReadiness::Siap->value,
                    'emergency_drugs' => SscReadiness::Siap->value,
                ],
                'pulse_ox_attached' => true,
                'pulse_ox_functional' => true,
                'spo2' => 98,
                'pulse' => 84,
                'allergy_status' => AllergyStatus::TidakAda,
                'difficult_airway_risk' => RiskPresence::TidakAda,
                'aspiration_risk' => RiskPresence::TidakAda,
                'airway_equipment_ready' => SscReadiness::Siap,
                'bleeding_risk' => RiskPresence::Ada,
                'estimated_blood_loss_ml' => 350,
                'iv_access' => IvAccessStatus::Memadai,
                'fluids_ready' => SscReadiness::Siap,
                'blood_ready' => BloodReadiness::Siap,
                'signed_at' => $baseTime->copy()->addMinutes(6),
                'filled_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        SscTimeOut::query()->firstOrCreate(
            ['case_id' => $case4->id],
            [
                'status' => SscStatus::Selesai,
                'intro_circulating' => true,
                'intro_scrub' => true,
                'intro_anesthesia_nurse' => true,
                'intro_surgeon' => true,
                'intro_anesthesiologist' => true,
                'identity_procedure_site_confirmed' => true,
                'antibiotic_prophylaxis' => 'ya',
                'antibiotic_name' => 'Cefazolin 2g IV',
                'antibiotic_given_at' => $baseTime->copy()->addMinutes(12),
                'antibiotic_confirmed_by' => $perawatAnestesi1->name,
                'surgeon_critical_steps' => 'tidak',
                'surgeon_estimated_duration_min' => 90,
                'surgeon_estimated_blood_loss_ml' => 350,
                'anesthesia_special_condition' => 'tidak',
                'anesthesia_risk' => 'tidak',
                'nurse_sterility_confirmed' => true,
                'nurse_indicator_checked' => true,
                'equipment_problem' => 'tidak',
                'other_concern' => 'tidak',
                'imaging_needed' => 'ya',
                'imaging_displayed' => 'ya',
                'signed_at' => $baseTime->copy()->addMinutes(18),
                'filled_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        IntraopRecord::query()->firstOrCreate(
            ['case_id' => $case4->id],
            [
                'position' => SurgicalPosition::Supine,
                'pressure_protection' => ['ganjal_kepala_leher', 'safety_belt'],
                'skin_antiseptic' => ['povidone_iodine'],
                'grounding_plate_site' => 'Paha kanan lateral',
                'skin_condition_pre' => SkinIntegrity::Utuh,
                'draping_done' => true,
                'warming' => ['blanket_warmer'],
                'room_temperature_c' => 20.5,
                'count_verification' => CountVerification::TidakSesuai,
                'count_discrepancy_actions' => ['pencarian_ulang', 'lapor_operator'],
                'has_complication' => false,
                'procedure_details' => [
                    'incision_location_side' => 'Femur distal lateral sinistra',
                    'fracture_location' => 'Femur Distal Sinistra',
                    'bone_tissue_condition' => 'Garis fraktur kominutif ringan, vaskularisasi periosteum baik',
                    'reduction_type' => 'Terbuka',
                    'reduction_process' => 'Reduksi fragmen tulang menggunakan reduction forceps dan traction device',
                    'fixation_method' => 'ORIF Locking Compression Plate (LCP) Distal Femur',
                    'implant_placement_notes' => 'Terpasang 1 plate distal femur 9 hole dengan 7 locking/cortical screw',
                    'fluoroscopy_check' => 'Evaluasi C-arm AP & Lateral: reduksi anatomis, posisi plate & screw baik',
                    'drain_placement' => 'Rencana pasang redon drain sebelum penutupan',
                    'wound_closure' => 'Dalam proses fiksasi akhir',
                    'postop_wound_condition' => 'Masih berlangsung',
                    'blood_transfusion_given' => false,
                    'blood_product_type_amount' => '',
                    'cautery_usage' => 'Monopolar 35 W koagulasi',
                    'equipment_usage_notes' => 'Fracture table, C-arm fluoroscopy, mesin bor ortopedi berfungsi baik',
                ],
                'implants' => [
                    [
                        'implant_type' => 'Plate',
                        'size' => '9 Hole Distal Femur Left',
                        'planned_qty' => '1',
                        'brand' => 'OrthoMed Ti',
                        'serial_lot' => 'LOT-DF2026-091',
                        'placement_site' => 'Femur Distal Lateral Sinistra',
                        'used_qty' => '1',
                        'unused_doc' => '-',
                    ],
                    [
                        'implant_type' => 'Locking Screw',
                        'size' => '5.0 mm x 42 mm',
                        'planned_qty' => '8',
                        'brand' => 'OrthoMed Ti',
                        'serial_lot' => 'LOT-LS5042-118',
                        'placement_site' => 'Distal Femur Sinistra',
                        'used_qty' => '7',
                        'unused_doc' => '1 sekrup ukuran 42mm tidak jadi dipasang, diganti ukuran 38mm',
                    ],
                ],
                'created_by' => $sirkuler1->id,
            ],
        );

        if (! VitalSign::query()->where('case_id', $case4->id)->where('phase', VitalSignPhase::Intra)->exists()) {
            VitalSign::query()->create([
                'case_id' => $case4->id,
                'phase' => VitalSignPhase::Intra,
                'recorded_at' => $baseTime->copy()->addMinutes(30),
                'bp_systolic' => 116,
                'bp_diastolic' => 74,
                'pulse' => 80,
                'temperature' => 36.5,
                'respiratory_rate' => 15,
                'spo2' => 99,
                'breathing_pattern' => BreathingPattern::Eupnea,
                'oxygen_device' => OxygenDevice::Ventilator,
                'oxygen_flow_lpm' => 3,
                'consciousness' => Consciousness::ComposMentis,
                'created_by' => $perawatAnestesi1->id,
            ]);
            VitalSign::query()->create([
                'case_id' => $case4->id,
                'phase' => VitalSignPhase::Intra,
                'recorded_at' => $baseTime->copy()->addMinutes(60),
                'bp_systolic' => 112,
                'bp_diastolic' => 70,
                'pulse' => 86,
                'temperature' => 36.4,
                'respiratory_rate' => 16,
                'spo2' => 99,
                'breathing_pattern' => BreathingPattern::Eupnea,
                'oxygen_device' => OxygenDevice::Ventilator,
                'oxygen_flow_lpm' => 3,
                'consciousness' => Consciousness::ComposMentis,
                'created_by' => $perawatAnestesi1->id,
            ]);
        }

        foreach (InstrumentCountItem::cases() as $item) {
            $isMismatch = $item === InstrumentCountItem::KasaSteril;
            InstrumentCount::query()->firstOrCreate(
                ['case_id' => $case4->id, 'item' => $item],
                [
                    'initial_count' => 10,
                    'additional_count' => 0,
                    'final_count' => $isMismatch ? 9 : 10,
                    'status' => $isMismatch ? InstrumentCountStatus::TidakSesuai : InstrumentCountStatus::Sesuai,
                    'created_by' => $instrumen1->id,
                ],
            );
        }

        SscSignOut::query()->firstOrCreate(
            ['case_id' => $case4->id],
            [
                'status' => SignOutStatus::PerluTindakLanjut,
                'q1_procedure_confirmed' => true,
                'q2_counts_correct' => false,
                'q3_specimen_labeled' => 'tidak_berlaku',
                'q4_equipment_problem' => false,
                'q5_recovery_discussed' => true,
                'extra_8_discrepancy_followup' => 'belum',
                'issues_and_actions' => 'Selisih 1 lembar kasa steril pada hitungan akhir; sedang dilakukan pencarian ulang lapangan operasi dan koordinasi operator.',
                'filled_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );
    }
}
