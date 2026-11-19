<?php

namespace Database\Factories;

use App\Enums\AllergyStatus;
use App\Enums\AnxietyLevel;
use App\Enums\ReadinessStatus;
use App\Models\PreopAssessment;
use App\Models\SurgeryCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreopAssessment>
 */
class PreopAssessmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_id' => SurgeryCase::factory(),
            'chief_complaint' => 'Nyeri perut bagian kanan bawah',
            'current_illness_history' => 'Nyeri dirasakan sejak 2 hari yang lalu',
            'past_illness_history' => 'Tidak ada riwayat penyakit berat sebelumnya',
            'surgery_history' => 'Belum pernah operasi',
            'allergy_status' => AllergyStatus::TidakAda,
            'allergy_detail' => null,
            'medication_history' => 'Tidak sedang mengonsumsi obat rutin',
            'anesthesia_history' => 'Belum pernah dibius',
            'fasting_status' => true,
            'fasting_since' => now()->subHours(6),
            'skin_condition' => 'Utuh, tidak ada lecet',
            'anxiety_level' => AnxietyLevel::Ringan,
            'surgery_knowledge' => 'Pasien memahami garis besar prosedur',
            'family_support' => 'Keluarga mendukung penuh',
            'mental_readiness' => 'Siap menjalani tindakan',
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
            'implementation_actions' => 'Pemasangan IV line dan pengecekan identitas',
            'implementation_education' => 'Edukasi napas dalam dan relaksasi',
            'implementation_monitoring' => 'Observasi TTV pra-bedah',
            'implementation_response' => 'Pasien tenang dan kooperatif',
            'evaluation_condition' => 'Compos mentis, TTV stabil',
            'evaluation_completeness' => 'Persiapan pra-operasi lengkap',
            'readiness_status' => ReadinessStatus::Siap,
            'evaluation_notes' => 'Pasien siap dibawa ke ruang operasi',
            'created_by' => User::factory(),
        ];
    }

    public function notReady(): static
    {
        return $this->state(fn () => [
            'readiness_status' => ReadinessStatus::BelumSiap,
            'preparation' => [
                'identity_verified' => true,
                'informed_consent_surgery' => false,
                'anesthesia_consent' => false,
                'fasting_verified' => false,
                'allergy_assessed' => true,
                'lab_available' => false,
                'iv_access' => false,
                'operation_area_prepared' => false,
                'jewelry_removed' => false,
                'site_marked' => false,
            ],
        ]);
    }
}
