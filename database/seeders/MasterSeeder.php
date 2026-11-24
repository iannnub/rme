<?php

namespace Database\Seeders;

use App\Enums\SurgeryProfile;
use App\Models\AnesthesiaType;
use App\Models\Instrument;
use App\Models\InstrumentCheck;
use App\Models\MedicalDiagnosis;
use App\Models\Room;
use App\Models\Setting;
use App\Models\SurgeryCase;
use App\Models\SurgeryType;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['code' => 'OK 1', 'name' => 'Kamar Operasi 1'],
            ['code' => 'OK 2', 'name' => 'Kamar Operasi 2'],
            ['code' => 'OK 3', 'name' => 'Kamar Operasi 3'],
        ];

        foreach ($rooms as $room) {
            Room::query()->updateOrCreate(
                ['code' => $room['code']],
                ['name' => $room['name'], 'is_active' => true],
            );
        }

        $anesthesiaTypes = [
            ['code' => 'GA', 'name' => 'General Anesthesia (Umum)'],
            ['code' => 'SAB', 'name' => 'Spinal Anesthesia (SAB)'],
            ['code' => 'EPIDURAL', 'name' => 'Epidural'],
            ['code' => 'BLOK_PERIFER', 'name' => 'Blok Perifer'],
            ['code' => 'LOKAL', 'name' => 'Lokal'],
        ];

        foreach ($anesthesiaTypes as $type) {
            AnesthesiaType::query()->updateOrCreate(
                ['code' => $type['code']],
                ['name' => $type['name'], 'is_active' => true],
            );
        }

        $medicalDiagnoses = [
            'Epidural Hematoma (EDH)',
            'Fraktur Femur',
        ];

        $usedDiagnosisIds = SurgeryCase::withTrashed()
            ->whereNotNull('medical_diagnosis_id')
            ->pluck('medical_diagnosis_id')
            ->merge(
                SurgeryType::query()
                    ->whereNotNull('default_medical_diagnosis_id')
                    ->pluck('default_medical_diagnosis_id')
            )
            ->unique()
            ->all();

        $diagnosisMap = [];
        foreach ($medicalDiagnoses as $diagnosis) {
            $record = MedicalDiagnosis::query()->updateOrCreate(
                ['name' => $diagnosis],
                ['is_active' => true],
            );
            $diagnosisMap[$diagnosis] = $record->id;
        }

        $surgeryTypes = [
            [
                'code' => SurgeryProfile::BedahSaraf->value,
                'name' => 'Bedah Saraf',
                'specialty' => 'Bedah Saraf',
                'default_medical_diagnosis_id' => $diagnosisMap['Epidural Hematoma (EDH)'],
                'default_procedure_name' => SurgeryProfile::BedahSaraf->defaultProcedureName(),
            ],
            [
                'code' => SurgeryProfile::BedahOrtopedi->value,
                'name' => 'Bedah Ortopedi',
                'specialty' => 'Bedah Ortopedi',
                'default_medical_diagnosis_id' => $diagnosisMap['Fraktur Femur'],
                'default_procedure_name' => SurgeryProfile::BedahOrtopedi->defaultProcedureName(),
            ],
        ];

        foreach ($surgeryTypes as $surgeryType) {
            SurgeryType::query()->updateOrCreate(
                ['code' => $surgeryType['code']],
                [
                    'name' => $surgeryType['name'],
                    'specialty' => $surgeryType['specialty'],
                    'default_medical_diagnosis_id' => $surgeryType['default_medical_diagnosis_id'],
                    'default_procedure_name' => $surgeryType['default_procedure_name'],
                    'is_active' => true,
                ],
            );
        }

        $usedSurgeryTypeIds = SurgeryCase::withTrashed()
            ->whereNotNull('surgery_type_id')
            ->pluck('surgery_type_id')
            ->unique()
            ->all();

        SurgeryType::query()
            ->whereNotIn('name', collect($surgeryTypes)->pluck('name')->all())
            ->whereNotIn('id', $usedSurgeryTypeIds)
            ->delete();

        MedicalDiagnosis::query()
            ->whereNotIn('name', $medicalDiagnoses)
            ->whereNotIn('id', $usedDiagnosisIds)
            ->whereNotIn('id', SurgeryType::query()->whereNotNull('default_medical_diagnosis_id')->pluck('default_medical_diagnosis_id')->all())
            ->delete();

        $instruments = $this->instrumentCatalog();
        $instrumentNames = collect($instruments)->pluck('name')->all();

        foreach ($instruments as $instrument) {
            Instrument::query()->updateOrCreate(
                ['name' => $instrument['name']],
                [
                    'category' => $instrument['category'],
                    'surgery_profile' => $instrument['surgery_profile'],
                    'description' => $instrument['description'],
                    'is_active' => true,
                ],
            );
        }

        $usedInstrumentIds = InstrumentCheck::withTrashed()
            ->pluck('instrument_id')
            ->unique()
            ->all();

        Instrument::query()
            ->whereNotIn('name', $instrumentNames)
            ->whereNotIn('id', $usedInstrumentIds)
            ->delete();

        $settings = [
            'hospital_name' => 'RS Simulasi RME OKA',
            'hospital_address' => 'Jl. Kesehatan No. 1, Jawa Timur',
            'hospital_phone' => '(0331) 500123',
            'hospital_email' => 'oka@rme-simulasi.test',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }

        $this->call(NursingDiagnosisSeeder::class);
    }

    /**
     * Daftar lengkap 81 alat, penunjang, implan, dan BHP untuk Bedah Saraf (50 item) dan Bedah Ortopedi (66 item).
     *
     * @return list<array{name: string, category: string, surgery_profile: string, description: string|null}>
     */
    private function instrumentCatalog(): array
    {
        $saraf = SurgeryProfile::BedahSaraf->value;
        $orto = SurgeryProfile::BedahOrtopedi->value;

        return [
            // 1. Instrumen Bersama (9 item -> masuk ke Bedah Saraf & Bedah Ortopedi)
            ['name' => 'Scalpel/handle pisau bedah', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Membuat sayatan pada area operasi'],
            ['name' => 'Pinset anatomis', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Memegang jaringan secara atraumatik'],
            ['name' => 'Pinset chirurgis', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Memegang jaringan dengan kuat'],
            ['name' => 'Gunting jaringan', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Memotong jaringan'],
            ['name' => 'Gunting benang', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Memotong benang jahit'],
            ['name' => 'Klem arteri', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Menjepit pembuluh darah'],
            ['name' => 'Periosteal elevator', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Mengangkat periosteum dari permukaan tulang'],
            ['name' => 'Suction tip', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Menghisap darah dan cairan dari area operasi'],
            ['name' => 'Needle holder', 'category' => 'Alat & Instrumen Bedah', 'surgery_profile' => 'semua', 'description' => 'Memegang jarum jahit'],

            // 2. Instrumen Khusus Bedah Saraf (11 item, total instrumen saraf = 20)
            ['name' => 'Set instrumen kraniotomi', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Instrumen utama tindakan kraniotomi evakuasi EDH'],
            ['name' => 'Retractor kulit kepala', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Membuka dan menahan flap kulit kepala'],
            ['name' => 'Cranial drill', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Bor tulang tengkorak'],
            ['name' => 'Perforator', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Membuat burr hole pada tulang tengkorak'],
            ['name' => 'Craniotome', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Membuat bone flap kraniotomi'],
            ['name' => 'Bone rongeur', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Menggigit/merapikan tepi tulang tengkorak'],
            ['name' => 'Dura hook', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Mengangkat/menahan duramater'],
            ['name' => 'Dural scissors', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Gunting khusus jaringan duramater'],
            ['name' => 'Bipolar forceps', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Koagulasi perdarahan jaringan saraf/otak'],
            ['name' => 'Suction irrigation', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Irigasi dan aspirasi simultan lapangan kraniotomi'],
            ['name' => 'Retractor otak', 'category' => 'Alat & Instrumen Bedah Saraf', 'surgery_profile' => $saraf, 'description' => 'Retraksi lembut jaringan otak'],

            // 3. Instrumen Khusus Bedah Ortopedi (21 item, total instrumen ortopedi = 30)
            ['name' => 'Set instrumen ortopedi', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Instrumen utama untuk operasi tulang'],
            ['name' => 'Retraktor jaringan', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membuka dan mempertahankan area operasi'],
            ['name' => 'Bone holding forceps', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Memegang dan mempertahankan posisi tulang'],
            ['name' => 'Reduction forceps', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membantu menyusun kembali posisi tulang yang patah'],
            ['name' => 'Bone clamp', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Menjepit tulang selama proses fiksasi'],
            ['name' => 'Bone drill', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membuat lubang pada tulang'],
            ['name' => 'Drill bit', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Mata bor untuk membuat lubang pada tulang'],
            ['name' => 'Screwdriver', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Memasang dan mengencangkan sekrup'],
            ['name' => 'Depth gauge', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Mengukur kedalaman lubang untuk menentukan ukuran sekrup'],
            ['name' => 'Tap', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membuat ulir pada tulang sebelum pemasangan sekrup jika diperlukan'],
            ['name' => 'Plate bender', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membentuk plate sesuai anatomi tulang'],
            ['name' => 'Plate holder', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Memegang plate saat pemasangan'],
            ['name' => 'Bone cutter', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Memotong tulang jika diperlukan'],
            ['name' => 'Osteotome', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membantu pemotongan atau pembentukan tulang'],
            ['name' => 'Mallet', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Memukul osteotome atau instrumen tertentu'],
            ['name' => 'Intramedullary nail instrument set', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Instrumen untuk pemasangan paku intrameduler'],
            ['name' => 'Guide wire (Instrumen)', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membantu menentukan jalur pemasangan implan'],
            ['name' => 'Reamer', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Memperlebar saluran intrameduler jika diperlukan'],
            ['name' => 'Nail insertion handle', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membantu memasukkan intramedullary nail'],
            ['name' => 'Targeting device', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membantu menentukan posisi lubang penguncian nail'],
            ['name' => 'Screw inserter', 'category' => 'Alat & Instrumen Ortopedi', 'surgery_profile' => $orto, 'description' => 'Membantu pemasangan sekrup pengunci'],

            // 4. Alat Penunjang Operasi Bersama (9 item)
            ['name' => 'Mesin anestesi', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Membantu pemberian anestesi'],
            ['name' => 'Monitor pasien', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Memantau tanda-tanda vital'],
            ['name' => 'Ventilator', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Membantu pernapasan pasien'],
            ['name' => 'Elektrokauter', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Membantu menghentikan perdarahan'],
            ['name' => 'Mesin suction', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Menghisap darah dan cairan'],
            ['name' => 'Infusion pump', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Mengatur pemberian cairan'],
            ['name' => 'Syringe pump', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Mengatur pemberian obat'],
            ['name' => 'Alat penghangat pasien', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Mempertahankan suhu tubuh pasien'],
            ['name' => 'Defibrillator', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => 'semua', 'description' => 'Penanganan kegawatdaruratan jantung'],

            // 5. Alat Penunjang Khusus Bedah Saraf (4 item, total penunjang saraf = 13)
            ['name' => 'Meja operasi', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $saraf, 'description' => 'Meja operasi bedah saraf'],
            ['name' => 'Head ring/head holder', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $saraf, 'description' => 'Penyangga dan fiksasi kepala pasien'],
            ['name' => 'Lampu operasi', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $saraf, 'description' => 'Pencahayaan utama meja operasi'],
            ['name' => 'Lampu kepala (headlight)', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $saraf, 'description' => 'Pencahayaan fokus operator bedah saraf'],

            // 6. Alat Penunjang Khusus Bedah Ortopedi (5 item, total penunjang ortopedi = 14)
            ['name' => 'Meja operasi ortopedi', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $orto, 'description' => 'Tempat pasien selama operasi tulang'],
            ['name' => 'Fracture table', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $orto, 'description' => 'Membantu mempertahankan posisi dan traksi tungkai'],
            ['name' => 'Traction device', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $orto, 'description' => 'Memberikan traksi pada tungkai'],
            ['name' => 'C-arm fluoroscopy', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $orto, 'description' => 'Memantau posisi tulang dan implan selama operasi'],
            ['name' => 'Mesin bor ortopedi', 'category' => 'Alat Penunjang Operasi', 'surgery_profile' => $orto, 'description' => 'Membantu pengeboran tulang'],

            // 7. Implan yang Perlu Disediakan - Khusus Bedah Ortopedi (5 item)
            ['name' => 'Plate', 'category' => 'Implan Ortopedi', 'surgery_profile' => $orto, 'description' => 'Lempeng logam untuk mempertahankan posisi tulang'],
            ['name' => 'Screw', 'category' => 'Implan Ortopedi', 'surgery_profile' => $orto, 'description' => 'Sekrup untuk mengunci plate pada tulang'],
            ['name' => 'Intramedullary nail', 'category' => 'Implan Ortopedi', 'surgery_profile' => $orto, 'description' => 'Paku yang dipasang di dalam saluran sumsum tulang femur'],
            ['name' => 'Locking screw', 'category' => 'Implan Ortopedi', 'surgery_profile' => $orto, 'description' => 'Sekrup pengunci untuk mempertahankan stabilitas fiksasi'],
            ['name' => 'Guide wire (Implan)', 'category' => 'Implan Ortopedi', 'surgery_profile' => $orto, 'description' => 'Kawat pemandu pemasangan implan'],

            // 8. Bahan Habis Pakai (BHP) (17 item - dipakai Bedah Saraf & Bedah Ortopedi)
            ['name' => 'Sarung tangan steril', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Gaun operasi steril', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Masker dan penutup kepala', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Duk operasi steril', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Kasa steril', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Kom / mangkuk steril', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Spuit berbagai ukuran', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Cairan NaCl 0,9% untuk irigasi', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Benang jahit operasi', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Pisau bedah', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Selang suction', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Kateter suction', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Elektrode elektrokauter', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Cairan antiseptik kulit', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Balutan luka steril', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Drain (jika diperlukan)', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
            ['name' => 'Bahan hemostatik (jika diperlukan)', 'category' => 'Bahan Habis Pakai (BHP)', 'surgery_profile' => 'semua', 'description' => null],
        ];
    }
}
