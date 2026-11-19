<?php

namespace App\Services;

use App\Enums\FluidDirection;
use App\Enums\FluidKind;
use App\Enums\VitalSignPhase;
use App\Models\FluidRecord;
use App\Models\Medication;
use App\Models\PostopObservation;
use App\Models\SurgeryCase;
use App\Models\VitalSign;

class HandoverSummaryBuilder
{
    /**
     * Merangkum 12 item data serah terima pasien otomatis sesuai SKPL bagian V.E.
     *
     * @return array<string, string>
     */
    public function build(SurgeryCase $case, ?string $ongoingIssues = null, ?string $followUpInstructions = null): array
    {
        $case->loadMissing([
            'patient',
            'medicalDiagnosis',
            'surgeryType',
            'anesthesiaType',
            'vitalSigns',
            'medications',
            'fluidRecords',
            'postopObservations',
            'postopSummary',
            'sscSignOut',
        ]);

        $patient = $case->patient;
        $identitas = $patient !== null
            ? "{$patient->name} | No. RM: {$patient->rm_number} | {$patient->ageLabel()} | {$patient->gender->label()}"
            : '-';

        $diagnosis = $case->diagnosisDisplay();

        $jenisOperasi = $case->surgeryTypeDisplay();
        if ($case->operation_plan_actual !== null && trim($case->operation_plan_actual) !== '') {
            $jenisOperasi .= " (Aktual: {$case->operation_plan_actual})";
        }

        $jenisAnestesi = $case->anesthesiaType !== null
            ? "{$case->anesthesiaType->code} - {$case->anesthesiaType->name}"
            : '-';

        $latestObs = $case->postopObservations->sortByDesc('recorded_at')->first();
        $kesadaran = 'Belum ada observasi pasca-operasi.';
        if ($latestObs instanceof PostopObservation) {
            $recovery = $latestObs->anesthesia_recovery !== null ? $latestObs->anesthesia_recovery->label() : '-';
            $kesadaran = "GCS {$latestObs->gcsTotal()} (E{$latestObs->gcs_eye} V{$latestObs->gcs_verbal} M{$latestObs->gcs_motor}), "
                ."Kondisi: {$latestObs->general_condition->label()}, Respons: {$latestObs->response->label()}, Pemulihan Anestesi: {$recovery}";
        }

        $latestPostVital = $case->vitalSigns
            ->where('phase', VitalSignPhase::Post)
            ->sortByDesc('recorded_at')
            ->first();
        $latestVital = $latestPostVital ?? $case->vitalSigns->sortByDesc('recorded_at')->first();

        $ttvTerakhir = 'Belum ada catatan TTV.';
        $alatNapas = 'Belum tercatat.';
        if ($latestVital instanceof VitalSign) {
            $timeStr = $latestVital->recorded_at->timezone('Asia/Jakarta')->format('d M Y H:i');
            $ttvTerakhir = "TD {$latestVital->bp_systolic}/{$latestVital->bp_diastolic} mmHg, "
                ."Nadi {$latestVital->pulse} x/mnt, Suhu {$latestVital->temperature}°C, "
                ."RR {$latestVital->respiratory_rate} x/mnt, SpO₂ {$latestVital->spo2}% ({$timeStr} WIB)";

            $airway = $latestObs instanceof PostopObservation ? "Jalan napas: {$latestObs->airway_patency->label()}, " : '';
            $pattern = $latestVital->breathing_pattern !== null ? "Pola: {$latestVital->breathing_pattern->label()}, " : '';
            $device = $latestVital->oxygen_device !== null ? $latestVital->oxygen_device->label() : 'Tanpa Oksigen';
            $lpm = $latestVital->oxygen_flow_lpm !== null ? " ({$latestVital->oxygen_flow_lpm} LPM)" : '';
            $alatNapas = "{$airway}{$pattern}Alat O₂: {$device}{$lpm}";
        }

        $medList = $case->medications->map(fn (Medication $m): string => "{$m->name} {$m->dose}")->all();
        $totalIntake = (int) $case->fluidRecords->where('direction', FluidDirection::Intake)->sum('volume_ml');
        $totalOutput = (int) $case->fluidRecords->where('direction', FluidDirection::Output)->sum('volume_ml');
        $obatText = $medList !== [] ? implode(', ', $medList) : 'Tidak ada obat tercatat';
        $obatCairan = "Obat: {$obatText} | Total Cairan Masuk: {$totalIntake} ml, Keluar: {$totalOutput} ml";

        $totalBleeding = (int) $case->fluidRecords->where('kind', FluidKind::Perdarahan)->sum('volume_ml');
        $jumlahPerdarahan = "{$totalBleeding} ml";

        $kondisiLuka = 'Belum ada catatan kondisi luka.';
        if ($latestObs instanceof PostopObservation) {
            $parts = [];
            if ($latestObs->wound_location !== null) {
                $parts[] = "Lokasi: {$latestObs->wound_location}";
            }
            if ($latestObs->dressing !== null) {
                $parts[] = "Balutan: {$latestObs->dressing->label()}";
            }
            $parts[] = 'Rembesan/Perdarahan: '.($latestObs->bleeding_seepage ? 'Ya' : 'Tidak');
            $parts[] = 'Hematoma/Bengkak: '.($latestObs->swelling_hematoma ? 'Ya' : 'Tidak');
            if ($latestObs->suture_condition !== null) {
                $parts[] = "Jahitan: {$latestObs->suture_condition->label()}";
            }

            $drainRecord = $case->fluidRecords
                ->where('phase', VitalSignPhase::Post)
                ->where('kind', FluidKind::SuctionDrain)
                ->sortByDesc('id')
                ->first();
            if ($drainRecord instanceof FluidRecord) {
                $color = $drainRecord->color !== null ? $drainRecord->color->label() : '-';
                $odor = $drainRecord->odor !== null ? $drainRecord->odor->label() : '-';
                $parts[] = "Drain: {$drainRecord->volume_ml} ml (Warna: {$color}, Bau: {$odor})";
            }

            $kondisiLuka = implode(' | ', $parts);
        }

        $keluhanParts = [];
        if ($latestObs instanceof PostopObservation) {
            $keluhanParts[] = "Nyeri NRS {$latestObs->pain_nrs}/10";
            if ($latestObs->nausea_vomiting) {
                $keluhanParts[] = 'Mual/muntah';
            }
            if ($latestObs->shivering_hypothermia) {
                $keluhanParts[] = 'Menggigil/hipotermia';
            }
            if ($latestObs->complications !== null && $latestObs->complications !== []) {
                $keluhanParts[] = 'Komplikasi: '.implode(', ', $latestObs->complications);
            }
        }
        if ($ongoingIssues !== null && trim($ongoingIssues) !== '') {
            $keluhanParts[] = trim($ongoingIssues);
        }
        $keluhanMasalah = $keluhanParts !== [] ? implode(' | ', $keluhanParts) : 'Tidak ada keluhan khusus.';

        $instruksiParts = [];
        if ($case->sscSignOut !== null && $case->sscSignOut->recovery_notes !== null && trim($case->sscSignOut->recovery_notes) !== '') {
            $instruksiParts[] = 'Sign Out: '.trim($case->sscSignOut->recovery_notes);
        }
        if ($case->postopSummary !== null && $case->postopSummary->pre_transfer_condition_notes !== null && trim($case->postopSummary->pre_transfer_condition_notes) !== '') {
            $instruksiParts[] = 'Kondisi Pindah: '.trim($case->postopSummary->pre_transfer_condition_notes);
        }
        if ($followUpInstructions !== null && trim($followUpInstructions) !== '') {
            $instruksiParts[] = trim($followUpInstructions);
        }
        $instruksiLanjutan = $instruksiParts !== [] ? implode(' | ', $instruksiParts) : 'Lanjutkan pemantauan pasca-operasi standar.';

        return [
            'identitas_pasien' => $identitas,
            'diagnosis_medis' => $diagnosis,
            'jenis_operasi' => $jenisOperasi,
            'jenis_anestesi' => $jenisAnestesi,
            'kondisi_kesadaran' => $kesadaran,
            'ttv_terakhir' => $ttvTerakhir,
            'alat_bantu_pernapasan' => $alatNapas,
            'obat_dan_cairan' => $obatCairan,
            'jumlah_perdarahan' => $jumlahPerdarahan,
            'kondisi_luka' => $kondisiLuka,
            'keluhan_atau_masalah' => $keluhanMasalah,
            'instruksi_tindak_lanjut' => $instruksiLanjutan,
        ];
    }

    /**
     * Label Indonesia untuk 12 item ringkasan serah terima SKPL V.E.
     *
     * @return array<string, string>
     */
    public static function itemLabels(): array
    {
        return [
            'identitas_pasien' => '1. Identitas Pasien',
            'diagnosis_medis' => '2. Diagnosis Medis',
            'jenis_operasi' => '3. Jenis Operasi',
            'jenis_anestesi' => '4. Jenis Anestesi',
            'kondisi_kesadaran' => '5. Kondisi Kesadaran',
            'ttv_terakhir' => '6. Tanda-Tanda Vital Terakhir',
            'alat_bantu_pernapasan' => '7. Alat Bantu Pernapasan',
            'obat_dan_cairan' => '8. Obat & Cairan yang Diberikan',
            'jumlah_perdarahan' => '9. Jumlah Perdarahan',
            'kondisi_luka' => '10. Kondisi Luka Operasi',
            'keluhan_atau_masalah' => '11. Keluhan / Masalah yang Masih Ada',
            'instruksi_tindak_lanjut' => '12. Instruksi & Tindak Lanjut Perawatan',
        ];
    }
}
