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
        $diagHernia = MedicalDiagnosis::query()->where('name', 'Hernia Inguinalis')->first();
        $diagApendisitis = MedicalDiagnosis::query()->where('name', 'Apendisitis Akut')->first();
        $surgHernia = SurgeryType::query()->where('name', 'Herniorafi')->first();
        $surgApendektomi = SurgeryType::query()->where('name', 'Apendektomi')->first();
        $anestGa = AnesthesiaType::query()->where('code', 'GA')->first();
        $anestSab = AnesthesiaType::query()->where('code', 'SAB')->first();

        AuditContext::run(function () use (
            $sirkuler1,
            $instrumen1,
            $perawatAnestesi1,
            $operator1,
            $anestesi1,
            $room1,
            $diagHernia,
            $diagApendisitis,
            $surgHernia,
            $surgApendektomi,
            $anestGa,
            $anestSab,
        ): void {
            $patientA = Patient::query()->firstOrCreate(
                ['rm_number' => 'DEMO-001'],
                [
                    'name' => 'Pasien Contoh A',
                    'birth_date' => '1985-04-12',
                    'gender' => Gender::Male,
                    'address' => 'Jl. Contoh Sehat No. 10, Jember',
                    'phone' => '081200000001',
                    'created_by' => $sirkuler1->id,
                ],
            );

            $patientB = Patient::query()->firstOrCreate(
                ['rm_number' => 'DEMO-002'],
                [
                    'name' => 'Pasien Contoh B',
                    'birth_date' => '1994-09-21',
                    'gender' => Gender::Female,
                    'address' => 'Jl. Contoh Harmoni No. 25, Jember',
                    'phone' => '081200000002',
                    'created_by' => $sirkuler1->id,
                ],
            );

            if (! SurgeryCase::query()->where('case_number', 'OK-20261119-001')->exists()) {
                $case1 = new SurgeryCase([
                    'patient_id' => $patientA->id,
                    'case_number' => 'OK-20261119-001',
                    'admitted_at' => now('Asia/Jakarta')->subHours(18),
                    'origin_ward' => 'Mawar 1',
                    'origin_class' => 'Kelas 1',
                    'scheduled_at' => now('Asia/Jakarta')->addHours(2),
                    'medical_diagnosis_id' => $diagHernia?->id,
                    'surgery_type_id' => $surgHernia?->id,
                    'surgery_site' => 'Inguinal kanan',
                    'anesthesia_type_id' => $anestGa?->id,
                    'created_by' => $sirkuler1->id,
                ]);
                $case1->forceFill(['status' => CaseStatus::PreOp])->save();
            }

            $case2 = SurgeryCase::query()->where('case_number', 'OK-20261119-002')->first();

            if (! $case2) {
                $case2 = new SurgeryCase([
                    'patient_id' => $patientB->id,
                    'case_number' => 'OK-20261119-002',
                    'admitted_at' => now('Asia/Jakarta')->subDays(1),
                    'origin_ward' => 'Melati 2',
                    'origin_class' => 'Kelas 2',
                    'scheduled_at' => now('Asia/Jakarta')->addHour(),
                    'medical_diagnosis_id' => $diagApendisitis?->id,
                    'surgery_type_id' => $surgApendektomi?->id,
                    'surgery_site' => 'Abdomen kuadran kanan bawah',
                    'anesthesia_type_id' => $anestSab?->id,
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
                    'chief_complaint' => 'Nyeri perut kanan bawah sejak 2 hari lalu',
                    'current_illness_history' => 'Pasien mengeluhkan nyeri perut kanan bawah mendadak disertai demam ringan dan mual.',
                    'past_illness_history' => 'Tidak ada riwayat penyakit kronis atau hipertensi.',
                    'surgery_history' => 'Belum pernah menjalani operasi sebelumnya.',
                    'allergy_status' => AllergyStatus::TidakAda,
                    'allergy_detail' => null,
                    'medication_history' => 'Paracetamol tablet 500mg bila demam.',
                    'anesthesia_history' => 'Tidak ada riwayat alergi obat anestesi.',
                    'fasting_status' => true,
                    'fasting_since' => now('Asia/Jakarta')->subHours(8),
                    'skin_condition' => 'Kulit utuh, tidak ada lesi',
                    'anxiety_level' => AnxietyLevel::Ringan,
                    'surgery_knowledge' => 'Pasien telah memahami tujuan dan prosedur operasi.',
                    'family_support' => 'Didampingi keluarga inti.',
                    'mental_readiness' => 'Pasien menyatakan siap dan tenang menghadapi operasi.',
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
                    'implementation_actions' => 'Pemasangan IV line RL 20 tpm, verifikasi identitas dan persetujuan bedah.',
                    'implementation_education' => 'Edukasi napas dalam dan prosedur di kamar operasi.',
                    'implementation_monitoring' => 'TTV dalam batas normal stabil.',
                    'implementation_response' => 'Pasien kooperatif dan memahami instruksi.',
                    'evaluation_condition' => 'Kondisi umum sedang, compos mentis, nyeri perut skala 4.',
                    'evaluation_completeness' => 'Seluruh berkas persiapan pra-operasi telah lengkap.',
                    'readiness_status' => ReadinessStatus::Siap,
                    'evaluation_notes' => 'Pasien siap dipindahkan ke kamar operasi.',
                    'created_by' => $sirkuler1->id,
                ],
            );

            VitalSign::query()->firstOrCreate(
                ['case_id' => $case2->id, 'phase' => VitalSignPhase::Pre],
                [
                    'recorded_at' => now('Asia/Jakarta')->subMinutes(20),
                    'bp_systolic' => 120,
                    'bp_diastolic' => 80,
                    'pulse' => 82,
                    'temperature' => 36.8,
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
                ['case_id' => $case2->id, 'exam_type' => ExamType::Hemoglobin],
                [
                    'result' => '13.5 g/dL (Normal)',
                    'exam_date' => now('Asia/Jakarta')->subDays(1)->toDateString(),
                    'created_by' => $sirkuler1->id,
                ],
            );

            SupportingExam::query()->firstOrCreate(
                ['case_id' => $case2->id, 'exam_type' => ExamType::Leukosit],
                [
                    'result' => '12.400 /uL (Meningkat ringan)',
                    'exam_date' => now('Asia/Jakarta')->subDays(1)->toDateString(),
                    'created_by' => $sirkuler1->id,
                ],
            );

            $demoInstruments = Instrument::query()->active()->orderBy('id')->take(3)->get();
            foreach ($demoInstruments as $instrument) {
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
                    'pulse' => 82,
                    'pulse_ox_note' => 'Terpasang pada jari telunjuk kiri, sinyal baik.',
                    'allergy_status' => AllergyStatus::TidakAda,
                    'difficult_airway_risk' => RiskPresence::TidakAda,
                    'aspiration_risk' => RiskPresence::TidakAda,
                    'anesthesiologist_assessment' => 'Jalan napas Mallampati I, puasa > 8 jam, risiko aspirasi rendah.',
                    'anticipation_plan' => 'Persiapan anestesi spinal standar, pantau hemodinamik.',
                    'airway_equipment_ready' => SscReadiness::Siap,
                    'bleeding_risk' => RiskPresence::TidakAda,
                    'estimated_blood_loss_ml' => 150,
                    'iv_access' => IvAccessStatus::Memadai,
                    'fluids_ready' => SscReadiness::Siap,
                    'blood_ready' => BloodReadiness::TidakDiindikasikan,
                    'bleeding_note' => 'Terpasang IV line 18G paten di tangan kanan.',
                    'notes_followup' => null,
                    'signed_at' => now('Asia/Jakarta')->subMinutes(12),
                    'filled_by' => $sirkuler1->id,
                    'created_by' => $sirkuler1->id,
                ],
            );

            $this->seedCompletedDemoCase(
                $sirkuler1,
                $instrumen1,
                $perawatAnestesi1,
                $operator1,
                $anestesi1,
                $room1,
                $diagHernia,
                $surgHernia,
                $anestGa,
                $demoInstruments,
            );

            $this->seedIntraopMismatchDemoCase(
                $sirkuler1,
                $instrumen1,
                $perawatAnestesi1,
                $operator1,
                $anestesi1,
                $anestGa,
                $demoInstruments,
            );
        }, null, null, $sirkuler1);
    }

    /**
     * @param  Collection<int, Instrument>  $demoInstruments
     */
    private function seedCompletedDemoCase(
        User $sirkuler1,
        User $instrumen1,
        User $perawatAnestesi1,
        User $operator1,
        User $anestesi1,
        ?Room $room1,
        ?MedicalDiagnosis $diagHernia,
        ?SurgeryType $surgHernia,
        ?AnesthesiaType $anestGa,
        $demoInstruments,
    ): void {
        $patientC = Patient::query()->firstOrCreate(
            ['rm_number' => 'DEMO-003'],
            [
                'name' => 'Pasien Contoh C',
                'birth_date' => '1979-02-18',
                'gender' => Gender::Male,
                'address' => 'Jl. Contoh Kamboja No. 8, Jember',
                'phone' => '081200000003',
                'created_by' => $sirkuler1->id,
            ],
        );

        $baseTime = now('Asia/Jakarta')->subDays(2)->setTime(8, 0);

        $case3 = SurgeryCase::query()->where('case_number', 'OK-20261119-003')->first();
        if (! $case3) {
            $case3 = new SurgeryCase([
                'patient_id' => $patientC->id,
                'case_number' => 'OK-20261119-003',
                'admitted_at' => $baseTime->copy()->subHours(14),
                'origin_ward' => 'Dahlia 1',
                'origin_class' => 'Kelas 1',
                'scheduled_at' => $baseTime->copy(),
                'medical_diagnosis_id' => $diagHernia?->id,
                'surgery_type_id' => $surgHernia?->id,
                'surgery_site' => 'Inguinal kanan',
                'anesthesia_type_id' => $anestGa?->id,
                'operation_plan_actual' => 'Herniorafi Inguinalis Dextra (Mesh)',
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
                'surgery_end_at' => $baseTime->copy()->addMinutes(85),
                'left_or_at' => $baseTime->copy()->addMinutes(100),
                'completed_at' => $baseTime->copy()->addHours(3),
                'created_by' => $sirkuler1->id,
            ]);
            $case3->forceFill(['status' => CaseStatus::Selesai])->save();
        }

        PreopAssessment::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'chief_complaint' => 'Benjolan di lipat paha kanan yang membesar saat batuk.',
                'current_illness_history' => 'Benjolan muncul 6 bulan terakhir, masih dapat dimasukkan kembali.',
                'past_illness_history' => 'Tidak ada hipertensi maupun diabetes melitus.',
                'surgery_history' => 'Belum pernah menjalani operasi.',
                'allergy_status' => AllergyStatus::TidakAda,
                'fasting_status' => true,
                'fasting_since' => $baseTime->copy()->subHours(8),
                'skin_condition' => 'Kulit area inguinal utuh, sudah dicukur bersih.',
                'anxiety_level' => AnxietyLevel::Ringan,
                'surgery_knowledge' => 'Pasien memahami prosedur herniorafi.',
                'family_support' => 'Istri dan anak mendampingi.',
                'mental_readiness' => 'Siap menjalani tindakan operasi.',
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
                'implementation_actions' => 'Verifikasi identitas, pasang infus RL 20 tpm, edukasi pra-op.',
                'evaluation_condition' => 'Kondisi umum baik, compos mentis.',
                'evaluation_completeness' => 'Seluruh checklist persiapan lengkap.',
                'readiness_status' => ReadinessStatus::Siap,
                'created_by' => $sirkuler1->id,
            ],
        );

        VitalSign::query()->firstOrCreate(
            ['case_id' => $case3->id, 'phase' => VitalSignPhase::Pre],
            [
                'recorded_at' => $baseTime->copy()->subMinutes(30),
                'bp_systolic' => 122,
                'bp_diastolic' => 78,
                'pulse' => 76,
                'temperature' => 36.6,
                'respiratory_rate' => 18,
                'spo2' => 99,
                'weight_kg' => 68.0,
                'height_cm' => 170.0,
                'consciousness' => Consciousness::ComposMentis,
                'created_by' => $sirkuler1->id,
            ],
        );

        SupportingExam::query()->firstOrCreate(
            ['case_id' => $case3->id, 'exam_type' => ExamType::Hemoglobin],
            [
                'result' => '14.2 g/dL (Normal)',
                'exam_date' => $baseTime->copy()->subDay()->toDateString(),
                'created_by' => $sirkuler1->id,
            ],
        );

        foreach ($demoInstruments as $instrument) {
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
                'spo2' => 99,
                'pulse' => 76,
                'allergy_status' => AllergyStatus::TidakAda,
                'difficult_airway_risk' => RiskPresence::TidakAda,
                'aspiration_risk' => RiskPresence::TidakAda,
                'anesthesiologist_assessment' => 'ASA I, jalan napas baik.',
                'airway_equipment_ready' => SscReadiness::Siap,
                'bleeding_risk' => RiskPresence::TidakAda,
                'estimated_blood_loss_ml' => 100,
                'iv_access' => IvAccessStatus::Memadai,
                'fluids_ready' => SscReadiness::Siap,
                'blood_ready' => BloodReadiness::TidakDiindikasikan,
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
                'antibiotic_name' => 'Cefazolin 2g IV',
                'antibiotic_given_at' => $baseTime->copy()->addMinutes(12),
                'antibiotic_confirmed_by' => $perawatAnestesi1->name,
                'surgeon_critical_steps' => 'tidak',
                'surgeon_estimated_duration_min' => 60,
                'surgeon_estimated_blood_loss_ml' => 100,
                'anesthesia_special_condition' => 'tidak',
                'anesthesia_risk' => 'tidak',
                'nurse_sterility_confirmed' => true,
                'nurse_indicator_checked' => true,
                'equipment_problem' => 'tidak',
                'other_concern' => 'tidak',
                'imaging_needed' => 'tidak',
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
                'room_temperature_c' => 21.0,
                'count_verification' => CountVerification::LengkapSesuai,
                'count_discrepancy_actions' => [],
                'has_complication' => false,
                'created_by' => $sirkuler1->id,
            ],
        );

        $intraVitals = [
            ['offset' => 25, 'sys' => 118, 'dia' => 74, 'pulse' => 74, 'temp' => 36.5, 'rr' => 14, 'spo2' => 99],
            ['offset' => 50, 'sys' => 114, 'dia' => 72, 'pulse' => 72, 'temp' => 36.4, 'rr' => 14, 'spo2' => 100],
            ['offset' => 80, 'sys' => 120, 'dia' => 76, 'pulse' => 78, 'temp' => 36.5, 'rr' => 16, 'spo2' => 99],
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
                'name' => 'Cefazolin',
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
                'volume_ml' => 500,
                'note' => 'Ringer Laktat',
                'created_by' => $sirkuler1->id,
            ]);
            FluidRecord::query()->create([
                'case_id' => $case3->id,
                'phase' => VitalSignPhase::Intra,
                'recorded_at' => $baseTime->copy()->addMinutes(80),
                'direction' => FluidDirection::Output,
                'kind' => FluidKind::Perdarahan,
                'volume_ml' => 80,
                'note' => 'Perdarahan minimal',
                'created_by' => $sirkuler1->id,
            ]);
        }

        if (! NursingAction::query()->where('case_id', $case3->id)->exists()) {
            NursingAction::query()->create([
                'case_id' => $case3->id,
                'phase' => NursingPhase::Intra,
                'performed_at' => $baseTime->copy()->addMinutes(15),
                'performed_by_role' => 'sirkuler',
                'action' => 'Desinfeksi area operasi dan pemasangan draping steril',
                'evaluation' => 'Area operasi steril terjaga.',
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
                'recovery_notes' => 'Pantau TTV tiap 15 menit di PACU dan evaluasi nyeri.',
                'signed_at' => $baseTime->copy()->addMinutes(90),
                'filled_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        $signoffsToEnsure = [
            ['section' => SignoffSection::PreOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'perawat', 'user' => $sirkuler1, 'at' => $baseTime->copy()->subMinutes(20)],
            ['section' => SignoffSection::PreOp, 'kind' => SignoffKind::Verification, 'role_label' => 'dokter_operator', 'user' => $operator1, 'at' => $baseTime->copy()->subMinutes(16)],
            ['section' => SignoffSection::SignIn, 'kind' => SignoffKind::Verification, 'role_label' => 'dokter_anestesi', 'user' => $anestesi1, 'at' => $baseTime->copy()->addMinutes(7)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'perawat_sirkuler', 'user' => $sirkuler1, 'at' => $baseTime->copy()->addMinutes(92)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'perawat_instrumen', 'user' => $instrumen1, 'at' => $baseTime->copy()->addMinutes(93)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'dokter_anestesi', 'user' => $anestesi1, 'at' => $baseTime->copy()->addMinutes(94)],
            ['section' => SignoffSection::IntraOp, 'kind' => SignoffKind::Signoff, 'role_label' => 'dokter_operator', 'user' => $operator1, 'at' => $baseTime->copy()->addMinutes(95)],
            ['section' => SignoffSection::SignOut, 'kind' => SignoffKind::Verification, 'role_label' => 'dokter_operator', 'user' => $operator1, 'at' => $baseTime->copy()->addMinutes(96)],
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
                'recorded_at' => $baseTime->copy()->addMinutes(120),
                'gcs_eye' => 4,
                'gcs_verbal' => 5,
                'gcs_motor' => 6,
                'general_condition' => GeneralCondition::Baik,
                'response' => PatientResponse::Stabil,
                'airway_patency' => AirwayPatency::Paten,
                'wound_location' => 'Inguinal kanan',
                'dressing' => DressingCondition::Kering,
                'bleeding_seepage' => false,
                'swelling_hematoma' => false,
                'suture_condition' => SutureCondition::RapiUtuh,
                'pain_nrs' => 2,
                'pain_location' => 'Area luka operasi inguinal kanan',
                'anesthesia_recovery' => Consciousness::ComposMentis,
                'nausea_vomiting' => false,
                'shivering_hypothermia' => false,
                'complications' => [],
                'notes' => 'Pasien sadar penuh, hemodinamik stabil, balutan bersih kering.',
                'created_by' => $sirkuler1->id,
            ]);

            VitalSign::query()->create([
                'case_id' => $case3->id,
                'phase' => VitalSignPhase::Post,
                'recorded_at' => $baseTime->copy()->addMinutes(120),
                'bp_systolic' => 122,
                'bp_diastolic' => 78,
                'pulse' => 76,
                'temperature' => 36.6,
                'respiratory_rate' => 18,
                'spo2' => 99,
                'breathing_pattern' => BreathingPattern::Eupnea,
                'oxygen_device' => OxygenDevice::NasalKanul,
                'oxygen_flow_lpm' => 2,
                'consciousness' => Consciousness::ComposMentis,
                'created_by' => $sirkuler1->id,
            ]);
        }

        PostopSummary::query()->firstOrCreate(
            ['case_id' => $case3->id],
            [
                'ready_to_transfer' => true,
                'pre_transfer_condition_notes' => 'Compos mentis, TTV stabil, nyeri terkontrol NRS 2, layak pindah ke ruang rawat inap.',
                'assessed_at' => $baseTime->copy()->addMinutes(150),
                'assessed_by' => $sirkuler1->id,
                'created_by' => $sirkuler1->id,
            ],
        );

        if (! Handover::query()->where('case_id', $case3->id)->exists()) {
            $summarySnapshot = app(HandoverSummaryBuilder::class)->build($case3->fresh() ?? $case3);
            Handover::query()->create([
                'case_id' => $case3->id,
                'destination' => HandoverDestination::RuangRawatInap,
                'destination_detail' => 'Ruang Dahlia 1 - Bed 04',
                'documents_complete' => true,
                'handed_by_id' => $sirkuler1->id,
                'received_by_name' => 'Ns. Budi Santoso, S.Kep (Perawat Dahlia 1)',
                'handed_over_at' => $baseTime->copy()->addHours(3),
                'ongoing_issues' => 'Nyeri ringan area luka operasi (NRS 2).',
                'follow_up_instructions' => 'Lanjutkan infus RL 20 tpm, observasi TTV dan balutan tiap 4 jam, mobilisasi bertahap sesuai instruksi operator.',
                'summary_snapshot' => $summarySnapshot,
                'created_by' => $sirkuler1->id,
            ]);
        }
    }

    /**
     * @param  Collection<int, Instrument>  $demoInstruments
     */
    private function seedIntraopMismatchDemoCase(
        User $sirkuler1,
        User $instrumen1,
        User $perawatAnestesi1,
        User $operator1,
        User $anestesi1,
        ?AnesthesiaType $anestGa,
        $demoInstruments,
    ): void {
        $room2 = Room::query()->where('code', 'OK 2')->first();
        $diagKolesistitis = MedicalDiagnosis::query()->where('name', 'like', '%Kolesistitis%')->first()
            ?? MedicalDiagnosis::query()->first();
        $surgKolesistektomi = SurgeryType::query()->where('name', 'Kolesistektomi')->first()
            ?? SurgeryType::query()->first();

        $patientD = Patient::query()->firstOrCreate(
            ['rm_number' => 'DEMO-004'],
            [
                'name' => 'Pasien Contoh D',
                'birth_date' => '1988-07-05',
                'gender' => Gender::Female,
                'address' => 'Jl. Contoh Kenanga No. 17, Jember',
                'phone' => '081200000004',
                'created_by' => $sirkuler1->id,
            ],
        );

        $baseTime = now('Asia/Jakarta')->subHours(2);

        $case4 = SurgeryCase::query()->where('case_number', 'OK-20261119-004')->first();
        if (! $case4) {
            $case4 = new SurgeryCase([
                'patient_id' => $patientD->id,
                'case_number' => 'OK-20261119-004',
                'admitted_at' => $baseTime->copy()->subHours(20),
                'origin_ward' => 'Anggrek 3',
                'origin_class' => 'Kelas 2',
                'scheduled_at' => $baseTime->copy(),
                'medical_diagnosis_id' => $diagKolesistitis?->id,
                'surgery_type_id' => $surgKolesistektomi?->id,
                'surgery_site' => 'Abdomen kuadran kanan atas',
                'anesthesia_type_id' => $anestGa?->id,
                'operation_plan_actual' => 'Kolesistektomi Terbuka',
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

        foreach ($demoInstruments as $instrument) {
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
                'bleeding_risk' => RiskPresence::TidakAda,
                'iv_access' => IvAccessStatus::Memadai,
                'fluids_ready' => SscReadiness::Siap,
                'blood_ready' => BloodReadiness::TidakDiindikasikan,
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
                'antibiotic_name' => 'Ceftriaxone 1g IV',
                'antibiotic_given_at' => $baseTime->copy()->addMinutes(12),
                'antibiotic_confirmed_by' => $perawatAnestesi1->name,
                'surgeon_critical_steps' => 'tidak',
                'surgeon_estimated_duration_min' => 90,
                'surgeon_estimated_blood_loss_ml' => 250,
                'anesthesia_special_condition' => 'tidak',
                'anesthesia_risk' => 'tidak',
                'nurse_sterility_confirmed' => true,
                'nurse_indicator_checked' => true,
                'equipment_problem' => 'tidak',
                'other_concern' => 'tidak',
                'imaging_needed' => 'tidak',
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
