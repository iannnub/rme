import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Bone } from 'lucide-react';
import { type PreopClinicalDetails, type UpdateClinicalFieldFn } from './types';

interface PreopOrthoDetailsCardProps {
    details: PreopClinicalDetails;
    onChange: UpdateClinicalFieldFn;
    disabled: boolean;
}

export function PreopOrthoDetailsCard({ details, onChange, disabled }: PreopOrthoDetailsCardProps) {
    return (
        <Card className="border-primary/25 border">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                    <Bone className="text-primary h-5 w-5" />
                    Pengkajian Khusus Bedah Ortopedi: Fraktur Femur
                </CardTitle>
                <CardDescription>
                    Lengkapi riwayat trauma, pemeriksaan status lokalis fraktur femur, neurovaskular distal (5P), rontgen femur, laboratorium, serta
                    persiapan ORIF &amp; implan.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">B. Riwayat Trauma &amp; Keluhan</h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_injury_cause">1. Penyebab Kecelakaan / Trauma</Label>
                            <Input
                                id="ortho_injury_cause"
                                value={details.incident.injury_cause}
                                onChange={(e) => onChange('incident', 'injury_cause', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Kecelakaan motor / jatuh"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_incident_datetime">2. Tanggal &amp; Waktu Kejadian</Label>
                            <Input
                                id="ortho_incident_datetime"
                                value={details.incident.incident_datetime}
                                onChange={(e) => onChange('incident', 'incident_datetime', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 18 Nov 2026, 13:00 WIB"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_incident_location">3. Lokasi Kejadian</Label>
                            <Input
                                id="ortho_incident_location"
                                value={details.incident.incident_location}
                                onChange={(e) => onChange('incident', 'incident_location', e.target.value)}
                                disabled={disabled}
                                placeholder="Lokasi kejadian trauma"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_pain_location">4. Lokasi Nyeri</Label>
                            <Input
                                id="ortho_pain_location"
                                value={details.incident.pain_location}
                                onChange={(e) => onChange('incident', 'pain_location', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Paha kanan 1/3 tengah"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_loss_consciousness">5. Riwayat Kehilangan Kesadaran</Label>
                            <Input
                                id="ortho_loss_consciousness"
                                value={details.incident.loss_of_consciousness}
                                onChange={(e) => onChange('incident', 'loss_of_consciousness', e.target.value)}
                                disabled={disabled}
                                placeholder="Sadar penuh sejak kejadian"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_anticoagulant">6. Obat Pengencer Darah</Label>
                            <Input
                                id="ortho_anticoagulant"
                                value={details.incident.anticoagulant_use}
                                onChange={(e) => onChange('incident', 'anticoagulant_use', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak ada riwayat antikoagulan"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">
                        C. Pemeriksaan Fisik Ekstremitas &amp; Status Lokalis Fraktur
                    </h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_fracture_loc">Lokasi Fraktur</Label>
                            <Input
                                id="ortho_fracture_loc"
                                value={details.extremity_physical.fracture_location}
                                onChange={(e) => onChange('extremity_physical', 'fracture_location', e.target.value)}
                                disabled={disabled}
                                placeholder="Proksimal / 1/3 Tengah / Distal Femur"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_fracture_side">Sisi Fraktur (Kanan/Kiri)</Label>
                            <Input
                                id="ortho_fracture_side"
                                value={details.extremity_physical.fracture_side}
                                onChange={(e) => onChange('extremity_physical', 'fracture_side', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Dekstra (Kanan)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_fracture_type">Jenis Fraktur (Tertutup/Terbuka)</Label>
                            <Input
                                id="ortho_fracture_type"
                                value={details.extremity_physical.fracture_type}
                                onChange={(e) => onChange('extremity_physical', 'fracture_type', e.target.value)}
                                disabled={disabled}
                                placeholder="Tertutup / Terbuka Gustilo..."
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_fracture_pattern">Pola / Bentuk Fraktur</Label>
                            <Input
                                id="ortho_fracture_pattern"
                                value={details.extremity_physical.fracture_pattern}
                                onChange={(e) => onChange('extremity_physical', 'fracture_pattern', e.target.value)}
                                disabled={disabled}
                                placeholder="Transversal / Oblik / Kominutif"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_skin_cond">Kondisi Kulit Area Fraktur</Label>
                            <Input
                                id="ortho_skin_cond"
                                value={details.extremity_physical.skin_condition}
                                onChange={(e) => onChange('extremity_physical', 'skin_condition', e.target.value)}
                                disabled={disabled}
                                placeholder="Utuh, hematoma ringan"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_open_wound">Ada/Tidaknya Luka Terbuka</Label>
                            <Input
                                id="ortho_open_wound"
                                value={details.extremity_physical.open_wound}
                                onChange={(e) => onChange('extremity_physical', 'open_wound', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak ada luka terbuka"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_bleeding">Perdarahan Aktif</Label>
                            <Input
                                id="ortho_bleeding"
                                value={details.extremity_physical.bleeding}
                                onChange={(e) => onChange('extremity_physical', 'bleeding', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak ada perdarahan aktif"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_swelling">Pembengkakan</Label>
                            <Input
                                id="ortho_swelling"
                                value={details.extremity_physical.swelling}
                                onChange={(e) => onChange('extremity_physical', 'swelling', e.target.value)}
                                disabled={disabled}
                                placeholder="Edema paha kanan terlokalisir"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_deformity">Deformitas / Pemendekan</Label>
                            <Input
                                id="ortho_deformity"
                                value={details.extremity_physical.deformity}
                                onChange={(e) => onChange('extremity_physical', 'deformity', e.target.value)}
                                disabled={disabled}
                                placeholder="Tampak angulasi dan pemendekan 2 cm"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_pain_nrs">Skala Nyeri (NRS 0-10)</Label>
                            <Input
                                id="ortho_pain_nrs"
                                value={details.extremity_physical.pain_nrs}
                                onChange={(e) => onChange('extremity_physical', 'pain_nrs', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 7/10 saat digerakkan"
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="ortho_movement">Kemampuan Menggerakkan Ekstremitas</Label>
                            <Input
                                id="ortho_movement"
                                value={details.extremity_physical.extremity_movement}
                                onChange={(e) => onChange('extremity_physical', 'extremity_movement', e.target.value)}
                                disabled={disabled}
                                placeholder="Terbatas akibat nyeri dan ketidakstabilan fragmen tulang"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">D. Pemeriksaan Neurovaskular Distal (5P) &amp; GDS</h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_foot_color">Warna Kulit Kaki</Label>
                            <Input
                                id="ortho_foot_color"
                                value={details.neurovascular.foot_skin_color}
                                onChange={(e) => onChange('neurovascular', 'foot_skin_color', e.target.value)}
                                disabled={disabled}
                                placeholder="Kemerahan / tidak pucat"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_foot_temp">Suhu Kulit Kaki / Akral</Label>
                            <Input
                                id="ortho_foot_temp"
                                value={details.neurovascular.foot_skin_temp}
                                onChange={(e) => onChange('neurovascular', 'foot_skin_temp', e.target.value)}
                                disabled={disabled}
                                placeholder="Hangat"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_crt">CRT (Capillary Refill Time)</Label>
                            <Input
                                id="ortho_crt"
                                value={details.neurovascular.crt}
                                onChange={(e) => onChange('neurovascular', 'crt', e.target.value)}
                                disabled={disabled}
                                placeholder="< 2 detik"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_pulse">Denyut Nadi Kaki (Dorsalis Pedis)</Label>
                            <Input
                                id="ortho_pulse"
                                value={details.neurovascular.peripheral_pulse}
                                onChange={(e) => onChange('neurovascular', 'peripheral_pulse', e.target.value)}
                                disabled={disabled}
                                placeholder="Teraba kuat dan teratur"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_sensation">Sensasi Kaki</Label>
                            <Input
                                id="ortho_sensation"
                                value={details.neurovascular.foot_sensation}
                                onChange={(e) => onChange('neurovascular', 'foot_sensation', e.target.value)}
                                disabled={disabled}
                                placeholder="Baik (intak)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_toe_move">Gerakan Jari Kaki</Label>
                            <Input
                                id="ortho_toe_move"
                                value={details.neurovascular.toe_movement}
                                onChange={(e) => onChange('neurovascular', 'toe_movement', e.target.value)}
                                disabled={disabled}
                                placeholder="Dapat menggerakkan jari kaki aktif"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_tingling">Kesemutan (Parestesia)</Label>
                            <Input
                                id="ortho_tingling"
                                value={details.neurovascular.tingling}
                                onChange={(e) => onChange('neurovascular', 'tingling', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak ada kesemutan"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_numbness">Mati Rasa (Baal)</Label>
                            <Input
                                id="ortho_numbness"
                                value={details.neurovascular.numbness}
                                onChange={(e) => onChange('neurovascular', 'numbness', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak ada mati rasa"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_gds">GDS (mg/dL)</Label>
                            <Input
                                id="ortho_gds"
                                value={details.vital_extra.gds}
                                onChange={(e) => onChange('vital_extra', 'gds', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 118 mg/dL"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">E. Detail Rontgen Femur &amp; Laboratorium</h4>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="ortho_xray_res">Hasil Foto Rontgen Femur</Label>
                            <Input
                                id="ortho_xray_res"
                                value={details.supporting_detail.xray_femur_result}
                                onChange={(e) => onChange('supporting_detail', 'xray_femur_result', e.target.value)}
                                disabled={disabled}
                                placeholder="Diskontinuitas komplet diafisis os femur kanan dengan displacement"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_xray_loc">Lokasi &amp; Sisi Tulang Femur</Label>
                            <Input
                                id="ortho_xray_loc"
                                value={details.supporting_detail.xray_location_side}
                                onChange={(e) => onChange('supporting_detail', 'xray_location_side', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Diafisis 1/3 tengah femur kanan"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_xray_pat">Bentuk &amp; Pergeseran Fragmen</Label>
                            <Input
                                id="ortho_xray_pat"
                                value={details.supporting_detail.xray_pattern}
                                onChange={(e) => onChange('supporting_detail', 'xray_pattern', e.target.value)}
                                disabled={disabled}
                                placeholder="Transversal dengan overriding 1.5 cm"
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="ortho_ct_note">Catatan CT Scan / Pemeriksaan Penunjang Lain (bila dilakukan)</Label>
                            <Input
                                id="ortho_ct_note"
                                value={details.supporting_detail.ct_scan_note}
                                onChange={(e) => onChange('supporting_detail', 'ct_scan_note', e.target.value)}
                                disabled={disabled}
                                placeholder="Tidak dilakukan / catatan tambahan"
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 pt-2 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_hb">Hb</Label>
                            <Input
                                id="ortho_hb"
                                value={details.supporting_detail.hb}
                                onChange={(e) => onChange('supporting_detail', 'hb', e.target.value)}
                                disabled={disabled}
                                placeholder="11.8 g/dL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_ht">Ht</Label>
                            <Input
                                id="ortho_ht"
                                value={details.supporting_detail.ht}
                                onChange={(e) => onChange('supporting_detail', 'ht', e.target.value)}
                                disabled={disabled}
                                placeholder="35%"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_leukosit">Leukosit</Label>
                            <Input
                                id="ortho_leukosit"
                                value={details.supporting_detail.leukosit}
                                onChange={(e) => onChange('supporting_detail', 'leukosit', e.target.value)}
                                disabled={disabled}
                                placeholder="9.800 /uL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_trombosit">Trombosit</Label>
                            <Input
                                id="ortho_trombosit"
                                value={details.supporting_detail.trombosit}
                                onChange={(e) => onChange('supporting_detail', 'trombosit', e.target.value)}
                                disabled={disabled}
                                placeholder="265.000 /uL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_blood_type">Golongan Darah</Label>
                            <Input
                                id="ortho_blood_type"
                                value={details.supporting_detail.blood_type}
                                onChange={(e) => onChange('supporting_detail', 'blood_type', e.target.value)}
                                disabled={disabled}
                                placeholder="A / B / AB / O"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_pt_inr">PT / INR</Label>
                            <Input
                                id="ortho_pt_inr"
                                value={details.supporting_detail.pt_inr}
                                onChange={(e) => onChange('supporting_detail', 'pt_inr', e.target.value)}
                                disabled={disabled}
                                placeholder="11.2 dtk / 1.0"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_aptt">aPTT</Label>
                            <Input
                                id="ortho_aptt"
                                value={details.supporting_detail.aptt}
                                onChange={(e) => onChange('supporting_detail', 'aptt', e.target.value)}
                                disabled={disabled}
                                placeholder="28.5 dtk"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_electrolytes">Elektrolit</Label>
                            <Input
                                id="ortho_electrolytes"
                                value={details.supporting_detail.electrolytes}
                                onChange={(e) => onChange('supporting_detail', 'electrolytes', e.target.value)}
                                disabled={disabled}
                                placeholder="Na 140, K 4.1, Cl 102"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_blood_glucose">Gula Darah</Label>
                            <Input
                                id="ortho_blood_glucose"
                                value={details.supporting_detail.blood_glucose}
                                onChange={(e) => onChange('supporting_detail', 'blood_glucose', e.target.value)}
                                disabled={disabled}
                                placeholder="118 mg/dL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_renal">Fungsi Ginjal</Label>
                            <Input
                                id="ortho_renal"
                                value={details.supporting_detail.renal_function}
                                onChange={(e) => onChange('supporting_detail', 'renal_function', e.target.value)}
                                disabled={disabled}
                                placeholder="Ur 22 / Cr 0.8"
                            />
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-foreground border-b pb-1 text-sm font-semibold">F. Persiapan Khusus ORIF &amp; Implan Ortopedi</h4>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_decision_time">Waktu Keputusan Operasi</Label>
                            <Input
                                id="ortho_decision_time"
                                value={details.prep_extra.decision_time}
                                onChange={(e) => onChange('prep_extra', 'decision_time', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: 18 Nov 2026, 15:00 WIB"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_iv_fluid">Cairan Infus Terpasang</Label>
                            <Input
                                id="ortho_iv_fluid"
                                value={details.prep_extra.iv_fluid_detail}
                                onChange={(e) => onChange('prep_extra', 'iv_fluid_detail', e.target.value)}
                                disabled={disabled}
                                placeholder="RL 500 mL 20 tpm"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_blood_prep">Persiapan Darah (PRC/Crossmatch)</Label>
                            <Input
                                id="ortho_blood_prep"
                                value={details.prep_extra.blood_prep}
                                onChange={(e) => onChange('prep_extra', 'blood_prep', e.target.value)}
                                disabled={disabled}
                                placeholder="2 kolf PRC siap di BDRS"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_antibiotic">Instruksi Antibiotik Profilaksis</Label>
                            <Input
                                id="ortho_antibiotic"
                                value={details.prep_extra.antibiotic_instruction}
                                onChange={(e) => onChange('prep_extra', 'antibiotic_instruction', e.target.value)}
                                disabled={disabled}
                                placeholder="Cefazolin 2 g IV pre-insisi"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_consent_signer">Penanda Tangan Persetujuan Operasi</Label>
                            <Input
                                id="ortho_consent_signer"
                                value={details.prep_extra.consent_signer}
                                onChange={(e) => onChange('prep_extra', 'consent_signer', e.target.value)}
                                disabled={disabled}
                                placeholder="Nama keluarga/wali"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_consent_rel">Hubungan dengan Pasien</Label>
                            <Input
                                id="ortho_consent_rel"
                                value={details.prep_extra.consent_relation}
                                onChange={(e) => onChange('prep_extra', 'consent_relation', e.target.value)}
                                disabled={disabled}
                                placeholder="contoh: Ayah Kandung"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_emergency_consent">Dokumentasi Persetujuan Darurat</Label>
                            <Input
                                id="ortho_emergency_consent"
                                value={details.prep_extra.emergency_consent_doc}
                                onChange={(e) => onChange('prep_extra', 'emergency_consent_doc', e.target.value)}
                                disabled={disabled}
                                placeholder="Informed consent bedah ortopedi lengkap"
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="ortho_implant_prep">Persiapan Alat Khusus &amp; Implan ORIF</Label>
                            <Input
                                id="ortho_implant_prep"
                                value={details.prep_extra.implant_instrument_prep}
                                onChange={(e) => onChange('prep_extra', 'implant_instrument_prep', e.target.value)}
                                disabled={disabled}
                                placeholder="Set instrumen ORIF femur, broad DCP/intramedullary nail, C-Arm siap"
                            />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
