<?php

namespace Database\Seeders;

use App\Models\AnesthesiaType;
use App\Models\Instrument;
use App\Models\MedicalDiagnosis;
use App\Models\Room;
use App\Models\Setting;
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
            'Apendisitis Akut',
            'Kolelitiasis (Batu Empedu)',
            'Hernia Inguinalis',
            'Sectio Caesarea (SC)',
            'Kanker Payudara',
            'Atresia Ani',
            'Ileus Obstruktif',
            'Fraktur Femur',
            'Kista Ovarium',
            'Tumor Otak',
        ];

        foreach ($medicalDiagnoses as $diagnosis) {
            MedicalDiagnosis::query()->updateOrCreate(
                ['name' => $diagnosis],
                ['is_active' => true],
            );
        }

        $surgeryTypes = [
            ['name' => 'Apendektomi', 'specialty' => 'Bedah Umum'],
            ['name' => 'Kolesistektomi', 'specialty' => 'Bedah Digestif'],
            ['name' => 'Herniorafi', 'specialty' => 'Bedah Umum'],
            ['name' => 'Sectio Caesarea', 'specialty' => 'Obstetri & Ginekologi'],
            ['name' => 'Mastektomi', 'specialty' => 'Bedah Onkologi'],
            ['name' => 'Anoplasti', 'specialty' => 'Bedah Anak'],
            ['name' => 'Laparotomi', 'specialty' => 'Bedah Umum'],
            ['name' => 'ORIF Femur', 'specialty' => 'Ortopedi'],
            ['name' => 'Kistektomi Ovarium', 'specialty' => 'Obstetri & Ginekologi'],
            ['name' => 'Kraniotomi', 'specialty' => 'Bedah Saraf'],
        ];

        foreach ($surgeryTypes as $surgeryType) {
            SurgeryType::query()->updateOrCreate(
                ['name' => $surgeryType['name']],
                ['specialty' => $surgeryType['specialty'], 'is_active' => true],
            );
        }

        $instruments = [
            ['name' => 'Set Dasar Bedah', 'category' => 'Instrumen Bedah'],
            ['name' => 'Mesin ESU', 'category' => 'Elektromedik'],
            ['name' => 'Suction', 'category' => 'Elektromedik'],
            ['name' => 'Lampu Operasi', 'category' => 'Peralatan Ruangan'],
            ['name' => 'Meja Operasi', 'category' => 'Peralatan Ruangan'],
            ['name' => 'Mesin Anestesi', 'category' => 'Anestesi'],
            ['name' => 'Monitor Pasien', 'category' => 'Monitoring'],
            ['name' => 'Laringoskop', 'category' => 'Anestesi'],
            ['name' => 'Set Laparotomi', 'category' => 'Instrumen Bedah'],
            ['name' => 'Set Orthopedi', 'category' => 'Instrumen Bedah'],
        ];

        foreach ($instruments as $instrument) {
            Instrument::query()->updateOrCreate(
                ['name' => $instrument['name']],
                ['category' => $instrument['category'], 'is_active' => true],
            );
        }

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
}
