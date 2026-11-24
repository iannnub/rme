import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Brain } from 'lucide-react';
import { type PreopClinicalDetails, type UpdateClinicalFieldFn } from './types';

interface PreopNeuroDetailsCardProps {
    details: PreopClinicalDetails;
    onChange: UpdateClinicalFieldFn;
    disabled: boolean;
}

export function PreopNeuroDetailsCard({ details, onChange, disabled }: PreopNeuroDetailsCardProps) {
    return (
        <Card className="border-primary/25 border">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                    <Brain className="text-primary h-5 w-5" />
                    Pengkajian Khusus Bedah Saraf: Epidural Hematoma (EDH)
                </CardTitle>
                <CardDescription>
                    Lengkapi riwayat cedera kepala, pemeriksaan neurologis (GCS dan pupil), hasil CT scan kepala, laboratorium, serta persiapan
                    kraniotomi.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">B. Riwayat Kejadian &amp; Keluhan Cedera Kepala</h4>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_injury_cause">1. Penyebab Cedera</Label>
                            <Input
                                id="neuro_injury_cause"
                                value={details.incident.injury_cause}
                                onChange={(e) => onChange('incident', 'injury_cause', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Kecelakaan lalu lintas tanpa helm"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_incident_datetime">2. Tanggal &amp; Waktu Kejadian</Label>
                            <Input
                                id="neuro_incident_datetime"
                                value={details.incident.incident_datetime}
                                onChange={(e) => onChange('incident', 'incident_datetime', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 18 Nov 2026, 09:30 WIB"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_incident_location">3. Lokasi Kejadian</Label>
                            <Input
                                id="neuro_incident_location"
                                value={details.incident.incident_location}
                                onChange={(e) => onChange('incident', 'incident_location', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Jalan raya / rumah"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_headache_history">4. Riwayat Sakit Kepala</Label>
                            <Input
                                id="neuro_headache_history"
                                value={details.incident.headache_history}
                                onChange={(e) => onChange('incident', 'headache_history', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Nyeri kepala progresif setelah benturan"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_nausea_vomiting">5. Riwayat Mual / Muntah</Label>
                            <Input
                                id="neuro_nausea_vomiting"
                                value={details.incident.nausea_vomiting}
                                onChange={(e) => onChange('incident', 'nausea_vomiting', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Muntah proyektil 2x"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_loss_of_consciousness">6. Riwayat Kehilangan Kesadaran</Label>
                            <Input
                                id="neuro_loss_of_consciousness"
                                value={details.incident.loss_of_consciousness}
                                onChange={(e) => onChange('incident', 'loss_of_consciousness', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Pingsan 10 menit, sempat sadar (lucid interval)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_seizure_history">7. Riwayat Kejang</Label>
                            <Input
                                id="neuro_seizure_history"
                                value={details.incident.seizure_history}
                                onChange={(e) => onChange('incident', 'seizure_history', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Tidak ada riwayat kejang"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_anticoagulant_use">8. Penggunaan Obat Pengencer Darah</Label>
                            <Input
                                id="neuro_anticoagulant_use"
                                value={details.incident.anticoagulant_use}
                                onChange={(e) => onChange('incident', 'anticoagulant_use', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Tidak mengonsumsi antikoagulan"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">C. Pemeriksaan Neurologis &amp; GCS</h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5 sm:col-span-2 lg:col-span-1">
                            <Label htmlFor="neuro_consciousness_level">Tingkat Kesadaran</Label>
                            <Input
                                id="neuro_consciousness_level"
                                value={details.neurological.consciousness_level}
                                onChange={(e) => onChange('neurological', 'consciousness_level', e.target.value)}
                                disabled={disabled}
                                placeholder="Somnolen / Apatis / Koma"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_gcs_e">GCS Eye (E)</Label>
                            <Input
                                id="neuro_gcs_e"
                                value={details.neurological.gcs_e}
                                onChange={(e) => onChange('neurological', 'gcs_e', e.target.value)}
                                disabled={disabled}
                                placeholder="1 - 4"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_gcs_v">GCS Verbal (V)</Label>
                            <Input
                                id="neuro_gcs_v"
                                value={details.neurological.gcs_v}
                                onChange={(e) => onChange('neurological', 'gcs_v', e.target.value)}
                                disabled={disabled}
                                placeholder="1 - 5"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_gcs_m">GCS Motorik (M)</Label>
                            <Input
                                id="neuro_gcs_m"
                                value={details.neurological.gcs_m}
                                onChange={(e) => onChange('neurological', 'gcs_m', e.target.value)}
                                disabled={disabled}
                                placeholder="1 - 6"
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_pupil_right">Ukuran Pupil Kanan (mm)</Label>
                            <Input
                                id="neuro_pupil_right"
                                value={details.neurological.pupil_size_right}
                                onChange={(e) => onChange('neurological', 'pupil_size_right', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 5 mm (anisokor)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_pupil_left">Ukuran Pupil Kiri (mm)</Label>
                            <Input
                                id="neuro_pupil_left"
                                value={details.neurological.pupil_size_left}
                                onChange={(e) => onChange('neurological', 'pupil_size_left', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 3 mm"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_pupil_light">Reaksi Pupil thd Cahaya</Label>
                            <Input
                                id="neuro_pupil_light"
                                value={details.neurological.pupil_light_reaction}
                                onChange={(e) => onChange('neurological', 'pupil_light_reaction', e.target.value)}
                                disabled={disabled}
                                placeholder="Kanan lambat / kiri positif"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_motor_right">Kekuatan Motorik Kanan</Label>
                            <Input
                                id="neuro_motor_right"
                                value={details.neurological.motor_right}
                                onChange={(e) => onChange('neurological', 'motor_right', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 5 / 5"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_motor_left">Kekuatan Motorik Kiri</Label>
                            <Input
                                id="neuro_motor_left"
                                value={details.neurological.motor_left}
                                onChange={(e) => onChange('neurological', 'motor_left', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 3 / 3 (hemiparesis)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_sensory">Respons Sensorik</Label>
                            <Input
                                id="neuro_sensory"
                                value={details.neurological.sensory_response}
                                onChange={(e) => onChange('neurological', 'sensory_response', e.target.value)}
                                disabled={disabled}
                                placeholder="Respons terhadap rangsang nyeri"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_seizure_status">Status Kejang Saat Ini</Label>
                            <Input
                                id="neuro_seizure_status"
                                value={details.neurological.seizure_status}
                                onChange={(e) => onChange('neurological', 'seizure_status', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak ada kejang"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_gds">GDS (mg/dL)</Label>
                            <Input
                                id="neuro_gds"
                                value={details.vital_extra.gds}
                                onChange={(e) => onChange('vital_extra', 'gds', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 132 mg/dL"
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2 lg:col-span-3">
                            <Label htmlFor="neuro_change_monitoring">Perubahan Kondisi Neurologis selama Pemantauan</Label>
                            <Input
                                id="neuro_change_monitoring"
                                value={details.neurological.neuro_change_during_monitoring}
                                onChange={(e) => onChange('neurological', 'neuro_change_during_monitoring', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Penurunan GCS dari E3V4M5 menjadi E2V3M5 dalam 30 menit"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">E. Detail CT Scan Kepala &amp; Laboratorium</h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5 sm:col-span-2 lg:col-span-3">
                            <Label htmlFor="neuro_ct_scan_result">Hasil CT Scan Kepala</Label>
                            <Input
                                id="neuro_ct_scan_result"
                                value={details.supporting_detail.ct_scan_result}
                                onChange={(e) => onChange('supporting_detail', 'ct_scan_result', e.target.value)}
                                disabled={disabled}
                                placeholder="Lesi hiperdens bikonveks (lensa cembung) regio temporoparietal"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_hematoma_loc">Lokasi Perdarahan / Hematoma</Label>
                            <Input
                                id="neuro_hematoma_loc"
                                value={details.supporting_detail.hematoma_location}
                                onChange={(e) => onChange('supporting_detail', 'hematoma_location', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Temporoparietal"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_hematoma_side">Sisi Hematoma (Kanan/Kiri)</Label>
                            <Input
                                id="neuro_hematoma_side"
                                value={details.supporting_detail.hematoma_side}
                                onChange={(e) => onChange('supporting_detail', 'hematoma_side', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Kanan (Dekstra)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_hematoma_thick">Ketebalan / Volume Hematoma</Label>
                            <Input
                                id="neuro_hematoma_thick"
                                value={details.supporting_detail.hematoma_thickness}
                                onChange={(e) => onChange('supporting_detail', 'hematoma_thickness', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 2.8 cm (estimasi 45 mL)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_midline_shift">Midline Shift</Label>
                            <Input
                                id="neuro_midline_shift"
                                value={details.supporting_detail.midline_shift}
                                onChange={(e) => onChange('supporting_detail', 'midline_shift', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Shift ke kiri 8 mm"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_skull_fracture">Fraktur Tengkorak</Label>
                            <Input
                                id="neuro_skull_fracture"
                                value={details.supporting_detail.skull_fracture}
                                onChange={(e) => onChange('supporting_detail', 'skull_fracture', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Fraktur linear os temporal kanan"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_associated_bleed">Perdarahan Penyerta Lainnya</Label>
                            <Input
                                id="neuro_associated_bleed"
                                value={details.supporting_detail.associated_bleeding}
                                onChange={(e) => onChange('supporting_detail', 'associated_bleeding', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Tidak tampak SAH/ICH signifikan"
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 pt-2 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_hb">Hb</Label>
                            <Input
                                id="neuro_hb"
                                value={details.supporting_detail.hb}
                                onChange={(e) => onChange('supporting_detail', 'hb', e.target.value)}
                                disabled={disabled}
                                placeholder="12.4 g/dL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_ht">Ht</Label>
                            <Input
                                id="neuro_ht"
                                value={details.supporting_detail.ht}
                                onChange={(e) => onChange('supporting_detail', 'ht', e.target.value)}
                                disabled={disabled}
                                placeholder="37%"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_leukosit">Leukosit</Label>
                            <Input
                                id="neuro_leukosit"
                                value={details.supporting_detail.leukosit}
                                onChange={(e) => onChange('supporting_detail', 'leukosit', e.target.value)}
                                disabled={disabled}
                                placeholder="11.200 /uL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_trombosit">Trombosit</Label>
                            <Input
                                id="neuro_trombosit"
                                value={details.supporting_detail.trombosit}
                                onChange={(e) => onChange('supporting_detail', 'trombosit', e.target.value)}
                                disabled={disabled}
                                placeholder="240.000 /uL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_blood_type">Golongan Darah</Label>
                            <Input
                                id="neuro_blood_type"
                                value={details.supporting_detail.blood_type}
                                onChange={(e) => onChange('supporting_detail', 'blood_type', e.target.value)}
                                disabled={disabled}
                                placeholder="A / B / AB / O"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_pt_inr">PT / INR</Label>
                            <Input
                                id="neuro_pt_inr"
                                value={details.supporting_detail.pt_inr}
                                onChange={(e) => onChange('supporting_detail', 'pt_inr', e.target.value)}
                                disabled={disabled}
                                placeholder="11.5 dtk / 1.0"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_aptt">aPTT</Label>
                            <Input
                                id="neuro_aptt"
                                value={details.supporting_detail.aptt}
                                onChange={(e) => onChange('supporting_detail', 'aptt', e.target.value)}
                                disabled={disabled}
                                placeholder="29.0 dtk"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_electrolytes">Elektrolit (Na/K/Cl)</Label>
                            <Input
                                id="neuro_electrolytes"
                                value={details.supporting_detail.electrolytes}
                                onChange={(e) => onChange('supporting_detail', 'electrolytes', e.target.value)}
                                disabled={disabled}
                                placeholder="Na 139, K 4.0, Cl 101"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_blood_glucose">Gula Darah</Label>
                            <Input
                                id="neuro_blood_glucose"
                                value={details.supporting_detail.blood_glucose}
                                onChange={(e) => onChange('supporting_detail', 'blood_glucose', e.target.value)}
                                disabled={disabled}
                                placeholder="132 mg/dL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_renal">Fungsi Ginjal (Ur/Cr)</Label>
                            <Input
                                id="neuro_renal"
                                value={details.supporting_detail.renal_function}
                                onChange={(e) => onChange('supporting_detail', 'renal_function', e.target.value)}
                                disabled={disabled}
                                placeholder="Ur 24 / Cr 0.9"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">F. Persiapan Kraniotomi &amp; Persetujuan Darurat</h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_decision_time">Waktu Keputusan Operasi</Label>
                            <Input
                                id="neuro_decision_time"
                                value={details.prep_extra.decision_time}
                                onChange={(e) => onChange('prep_extra', 'decision_time', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 18 Nov 2026, 10:15 WIB"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_iv_fluid">Cairan Infus Terpasang</Label>
                            <Input
                                id="neuro_iv_fluid"
                                value={details.prep_extra.iv_fluid_detail}
                                onChange={(e) => onChange('prep_extra', 'iv_fluid_detail', e.target.value)}
                                disabled={disabled}
                                placeholder="NaCl 0.9% 20 tpm, 2 line IV"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_blood_prep">Persiapan Darah (PRC/Crossmatch)</Label>
                            <Input
                                id="neuro_blood_prep"
                                value={details.prep_extra.blood_prep}
                                onChange={(e) => onChange('prep_extra', 'blood_prep', e.target.value)}
                                disabled={disabled}
                                placeholder="2 kolf PRC standby di PMI/BDRS"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_antibiotic">Instruksi Antibiotik Profilaksis</Label>
                            <Input
                                id="neuro_antibiotic"
                                value={details.prep_extra.antibiotic_instruction}
                                onChange={(e) => onChange('prep_extra', 'antibiotic_instruction', e.target.value)}
                                disabled={disabled}
                                placeholder="Ceftriaxone 2 g IV pre-insisi"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_consent_signer">Penanda Tangan Persetujuan Operasi</Label>
                            <Input
                                id="neuro_consent_signer"
                                value={details.prep_extra.consent_signer}
                                onChange={(e) => onChange('prep_extra', 'consent_signer', e.target.value)}
                                disabled={disabled}
                                placeholder="Nama keluarga/penanggung jawab"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="neuro_consent_rel">Hubungan dengan Pasien</Label>
                            <Input
                                id="neuro_consent_rel"
                                value={details.prep_extra.consent_relation}
                                onChange={(e) => onChange('prep_extra', 'consent_relation', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Istri / Orang Tua"
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2 lg:col-span-3">
                            <Label htmlFor="neuro_emergency_consent">Dokumentasi Persetujuan Tindakan Darurat</Label>
                            <Input
                                id="neuro_emergency_consent"
                                value={details.prep_extra.emergency_consent_doc}
                                onChange={(e) => onChange('prep_extra', 'emergency_consent_doc', e.target.value)}
                                disabled={disabled}
                                placeholder="Nomor berkas / keterangan persetujuan cito"
                            />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
