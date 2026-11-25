import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type CaseHeaderData } from '@/layouts/case-layout';
import { Link } from '@inertiajs/react';
import { Activity, Bone, ClipboardCheck, FlaskConical, HeartPulse, ShieldAlert, ShieldCheck, Syringe, UserCheck, Wrench } from 'lucide-react';
import { type PreopClinicalDetails, type UpdateClinicalFieldFn } from './types';

type OptionItem = {
    value: string;
    label: string;
};

type MasterItem = {
    id: number;
    name: string;
    code?: string;
};

export type PreopOrthoMainData = {
    medical_diagnosis_id: string;
    medical_diagnosis_other: string;
    surgery_site: string;
    anesthesia_type_id: string;
    chief_complaint: string;
    past_illness_history: string;
    medication_history: string;
    surgery_history: string;
    allergy_status: string;
    allergy_detail: string;
    anesthesia_history: string;
    fasting_status: boolean;
    fasting_since: string;
    preparation: {
        iv_access: boolean;
        informed_consent_surgery: boolean;
        site_marked: boolean | null;
    };
    readiness_status: string;
    clinical_details: PreopClinicalDetails;
    vital_signs: {
        bp_systolic: number | string;
        bp_diastolic: number | string;
        pulse: number | string;
        temperature: number | string;
        respiratory_rate: number | string;
        spo2: number | string;
        weight_kg: number | string;
        height_cm: number | string;
        consciousness: string;
    };
};

interface PreopOrthoFormProps {
    caseHeader: CaseHeaderData;
    prepProgress: {
        instrument_checks_count: number;
        instrument_checks_ready: boolean;
        ssc_sign_in_status: string;
        ssc_sign_in_label: string;
    };
    data: PreopOrthoMainData;
    errors: Record<string, string | undefined>;
    options: {
        medicalDiagnoses: MasterItem[];
        anesthesiaTypes: MasterItem[];
        allergyStatuses: OptionItem[];
        readinessStatuses: OptionItem[];
        consciousnessList: OptionItem[];
    };
    canManage: boolean;
    onSetField: <K extends keyof PreopOrthoMainData>(field: K, value: PreopOrthoMainData[K]) => void;
    onUpdateClinical: UpdateClinicalFieldFn;
    onUpdateVital: (field: keyof PreopOrthoMainData['vital_signs'], value: string) => void;
    onTogglePrep: (field: 'iv_access' | 'informed_consent_surgery' | 'site_marked', checked: boolean) => void;
}

export function PreopOrthoForm({
    caseHeader,
    prepProgress,
    data,
    errors,
    options,
    canManage,
    onSetField,
    onUpdateClinical,
    onUpdateVital,
    onTogglePrep,
}: PreopOrthoFormProps) {
    const cd = data.clinical_details;
    const vs = data.vital_signs;

    const selectedDiagnosisName =
        data.medical_diagnosis_id === 'other'
            ? data.medical_diagnosis_other || '-'
            : (options.medicalDiagnoses.find((d) => String(d.id) === data.medical_diagnosis_id)?.name ?? caseHeader.diagnosis);

    const allergySummary =
        data.allergy_status === 'ada'
            ? `Ada (${data.allergy_detail || 'lihat detail di B.1.h'})`
            : data.allergy_status === 'tidak_ada'
              ? 'Tidak ada riwayat alergi'
              : 'Belum diketahui';

    const fractureLocationSideSummary = [cd.extremity_physical.fracture_location, cd.extremity_physical.fracture_side].filter(Boolean).join(' / ');

    return (
        <div className="space-y-6">
            {/* BAGIAN A: Data Identitas dan Administrasi Pasien (18 item SKPL) */}
            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-2 text-base font-semibold">
                        <UserCheck className="text-primary h-4 w-4" />
                        A. Data Identitas dan Administrasi Pasien
                    </CardTitle>
                    <CardDescription>Data identitas pasien, asal IGD, status tindakan, dan tim penanggung jawab (Bedah Ortopedi).</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="bg-muted/30 grid grid-cols-1 gap-3 rounded-lg border p-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <span className="text-muted-foreground block text-xs">1. Nomor Rekam Medis</span>
                            <span className="font-mono font-semibold">{caseHeader.patient.rm_number}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">2. Nomor Registrasi Operasi</span>
                            <span className="font-mono font-semibold">{caseHeader.case_number}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">3. Nomor Kunjungan IGD</span>
                            <span className="font-mono font-semibold">{caseHeader.igd_visit_number ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">4. Nama Lengkap Pasien</span>
                            <span className="font-semibold">{caseHeader.patient.name}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">5. Tanggal Lahir &amp; Umur</span>
                            <span className="font-medium">
                                {caseHeader.patient.birth_date} ({caseHeader.patient.age_label})
                            </span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">6. Jenis Kelamin</span>
                            <span className="font-medium">{caseHeader.patient.gender_label}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">7. Alamat Pasien</span>
                            <span className="font-medium">{caseHeader.patient.address ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">8. Nomor Identitas Pasien</span>
                            <span className="font-mono font-medium">{caseHeader.patient.identity_number ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">9. Nama Keluarga / Penanggung Jawab</span>
                            <span className="font-medium">{caseHeader.patient.guardian_name ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">10. Hubungan dengan Pasien</span>
                            <span className="font-medium">{caseHeader.patient.guardian_relation ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">11. Nomor Telepon Keluarga</span>
                            <span className="font-mono font-medium">{caseHeader.patient.guardian_phone ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">12. Tanggal &amp; Jam Masuk IGD</span>
                            <span className="font-medium">{caseHeader.igd_admitted_at ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">13. Asal Pasien</span>
                            <span className="font-medium">{caseHeader.origin_ward || 'IGD'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">14. Tanggal &amp; Jam Masuk Kamar Operasi</span>
                            <span className="font-medium">{caseHeader.admitted_at}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">15. Status Tindakan Operasi</span>
                            <Badge variant="outline" className="mt-0.5">
                                {caseHeader.urgency_label ?? 'Terjadwal / Darurat'}
                            </Badge>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">17. Nama Dokter Penanggung Jawab</span>
                            <span className="font-medium">{caseHeader.dpjp_name ?? caseHeader.team.surgeon ?? '-'}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block text-xs">18. Nama Petugas Input Data</span>
                            <span className="font-medium">{caseHeader.created_by_name ?? '-'}</span>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="ortho_med_diag">16. Diagnosis Medis</Label>
                            <Select
                                value={data.medical_diagnosis_id}
                                onValueChange={(val) => onSetField('medical_diagnosis_id', val)}
                                disabled={!canManage}
                            >
                                <SelectTrigger id="ortho_med_diag" className="min-h-11">
                                    <SelectValue placeholder="Pilih Diagnosis Medis" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.medicalDiagnoses.map((d) => (
                                        <SelectItem key={d.id} value={String(d.id)}>
                                            {d.name}
                                        </SelectItem>
                                    ))}
                                    <SelectItem value="other">Diagnosis Lainnya (Ketik Manual)</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.medical_diagnosis_id} />
                        </div>

                        {data.medical_diagnosis_id === 'other' && (
                            <div className="space-y-2">
                                <Label htmlFor="ortho_med_diag_other">Diagnosis Medis Lainnya</Label>
                                <Input
                                    id="ortho_med_diag_other"
                                    value={data.medical_diagnosis_other}
                                    onChange={(e) => onSetField('medical_diagnosis_other', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Tulis diagnosis medis..."
                                    className="min-h-11"
                                />
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* BAGIAN B: Data Pengkajian (B.1 - B.6) */}
            <div className="space-y-6">
                {/* B.1 Riwayat Kejadian dan Keluhan Pasien (a-i) */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <ShieldAlert className="text-primary h-4 w-4" />
                            B.1. Riwayat Kejadian dan Keluhan Pasien
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_a">a. Penyebab Cedera</Label>
                            <Input
                                id="ortho_b1_a"
                                value={cd.incident.injury_cause}
                                onChange={(e) => onUpdateClinical('incident', 'injury_cause', e.target.value)}
                                disabled={!canManage}
                                placeholder="Kecelakaan lalu lintas / jatuh / benturan"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_b">b. Tanggal &amp; Waktu Kejadian</Label>
                            <Input
                                id="ortho_b1_b"
                                value={cd.incident.incident_datetime}
                                onChange={(e) => onUpdateClinical('incident', 'incident_datetime', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: 25 Nov 2026, 08:30 WIB"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_c">c. Lokasi Kejadian</Label>
                            <Input
                                id="ortho_b1_c"
                                value={cd.incident.incident_location}
                                onChange={(e) => onUpdateClinical('incident', 'incident_location', e.target.value)}
                                disabled={!canManage}
                                placeholder="Lokasi kejadian cedera"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5 md:col-span-2">
                            <Label htmlFor="ortho_b1_d">d. Keluhan Utama</Label>
                            <Input
                                id="ortho_b1_d"
                                value={data.chief_complaint}
                                onChange={(e) => onSetField('chief_complaint', e.target.value)}
                                disabled={!canManage}
                                placeholder="Nyeri hebat paha dan tidak dapat menggerakkan tungkai"
                                className="min-h-11"
                            />
                            <InputError message={errors.chief_complaint} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_e">e. Lokasi Nyeri</Label>
                            <Input
                                id="ortho_b1_e"
                                value={cd.incident.pain_location}
                                onChange={(e) => onUpdateClinical('incident', 'pain_location', e.target.value)}
                                disabled={!canManage}
                                placeholder="Paha kanan / kiri 1/3 tengah"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_f">f. Riwayat Penyakit Sebelumnya</Label>
                            <Input
                                id="ortho_b1_f"
                                value={data.past_illness_history}
                                onChange={(e) => onSetField('past_illness_history', e.target.value)}
                                disabled={!canManage}
                                placeholder="Hipertensi, DM, Asma, atau Tidak ada"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_g">g. Riwayat Penggunaan Obat</Label>
                            <Input
                                id="ortho_b1_g"
                                value={data.medication_history}
                                onChange={(e) => onSetField('medication_history', e.target.value)}
                                disabled={!canManage}
                                placeholder="Obat rutin yang sedang/pernah dikonsumsi"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_i">i. Riwayat Operasi Sebelumnya</Label>
                            <Input
                                id="ortho_b1_i"
                                value={data.surgery_history}
                                onChange={(e) => onSetField('surgery_history', e.target.value)}
                                disabled={!canManage}
                                placeholder="Belum pernah / riwayat operasi terdahulu"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b1_h_status">h. Riwayat Alergi Obat &amp; Makanan</Label>
                            <Select value={data.allergy_status} onValueChange={(v) => onSetField('allergy_status', v)} disabled={!canManage}>
                                <SelectTrigger id="ortho_b1_h_status" className="min-h-11">
                                    <SelectValue placeholder="Pilih Status Alergi" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.allergyStatuses.map((s) => (
                                        <SelectItem key={s.value} value={s.value}>
                                            {s.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.allergy_status} />
                        </div>
                        {data.allergy_status === 'ada' && (
                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="ortho_b1_h_detail">Detail Alergi &amp; Reaksi Alergi *</Label>
                                <Input
                                    id="ortho_b1_h_detail"
                                    value={data.allergy_detail}
                                    onChange={(e) => onSetField('allergy_detail', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Nama obat/makanan penyebab & reaksinya"
                                    className="min-h-11"
                                />
                                <InputError message={errors.allergy_detail} />
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* B.2 Pemeriksaan Fisik Ekstremitas (a-k) */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <Bone className="text-primary h-4 w-4" />
                            B.2. Pemeriksaan Fisik Ekstremitas (Fraktur Femur)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_a">a. Lokasi Fraktur (Proksimal / Shaft / Distal)</Label>
                            <Input
                                id="ortho_b2_a"
                                value={cd.extremity_physical.fracture_location}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'fracture_location', e.target.value)}
                                disabled={!canManage}
                                placeholder="Femur proksimal / shaft / distal"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_b">b. Sisi Fraktur (Kanan / Kiri)</Label>
                            <Input
                                id="ortho_b2_b"
                                value={cd.extremity_physical.fracture_side}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'fracture_side', e.target.value)}
                                disabled={!canManage}
                                placeholder="Kanan (Dekstra) / Kiri (Sinistra)"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_c">c. Jenis Fraktur (Terbuka / Tertutup)</Label>
                            <Input
                                id="ortho_b2_c"
                                value={cd.extremity_physical.fracture_type}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'fracture_type', e.target.value)}
                                disabled={!canManage}
                                placeholder="Tertutup / Terbuka"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_d">d. Pola Fraktur</Label>
                            <Input
                                id="ortho_b2_d"
                                value={cd.extremity_physical.fracture_pattern}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'fracture_pattern', e.target.value)}
                                disabled={!canManage}
                                placeholder="Transversal / oblik / spiral / kominutif / lainnya"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_e">e. Kondisi Kulit</Label>
                            <Input
                                id="ortho_b2_e"
                                value={cd.extremity_physical.skin_condition}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'skin_condition', e.target.value)}
                                disabled={!canManage}
                                placeholder="Utuh, memar / hematom lokal"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_f">f. Adanya Luka Terbuka</Label>
                            <Input
                                id="ortho_b2_f"
                                value={cd.extremity_physical.open_wound}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'open_wound', e.target.value)}
                                disabled={!canManage}
                                placeholder="Tidak ada / ukuran & karakteristik luka"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_g">g. Adanya Perdarahan</Label>
                            <Input
                                id="ortho_b2_g"
                                value={cd.extremity_physical.bleeding}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'bleeding', e.target.value)}
                                disabled={!canManage}
                                placeholder="Tidak ada perdarahan aktif"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_h">h. Pembengkakan</Label>
                            <Input
                                id="ortho_b2_h"
                                value={cd.extremity_physical.swelling}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'swelling', e.target.value)}
                                disabled={!canManage}
                                placeholder="Edema regio femur"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_i">i. Deformitas / Perubahan Bentuk Paha</Label>
                            <Input
                                id="ortho_b2_i"
                                value={cd.extremity_physical.deformity}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'deformity', e.target.value)}
                                disabled={!canManage}
                                placeholder="Angulasi / rotasi / pemendekan tungkai"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b2_j">j. Skala Nyeri (NRS 0-10)</Label>
                            <Input
                                id="ortho_b2_j"
                                value={cd.extremity_physical.pain_nrs}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'pain_nrs', e.target.value)}
                                disabled={!canManage}
                                placeholder="0-10"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5 md:col-span-2">
                            <Label htmlFor="ortho_b2_k">k. Kemampuan Menggerakkan Ekstremitas</Label>
                            <Input
                                id="ortho_b2_k"
                                value={cd.extremity_physical.extremity_movement}
                                onChange={(e) => onUpdateClinical('extremity_physical', 'extremity_movement', e.target.value)}
                                disabled={!canManage}
                                placeholder="Terbatas akibat nyeri / tidak dapat mengangkat tungkai"
                                className="min-h-11"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* B.3 Pemeriksaan Neurovaskular Ekstremitas (a-h) */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <Activity className="text-primary h-4 w-4" />
                            B.3. Pemeriksaan Neurovaskular Ekstremitas
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_a">a. Warna Kulit Kaki</Label>
                            <Input
                                id="ortho_b3_a"
                                value={cd.neurovascular.foot_skin_color}
                                onChange={(e) => onUpdateClinical('neurovascular', 'foot_skin_color', e.target.value)}
                                disabled={!canManage}
                                placeholder="Kemerahan / tidak pucat"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_b">b. Suhu Kulit Kaki</Label>
                            <Input
                                id="ortho_b3_b"
                                value={cd.neurovascular.foot_skin_temp}
                                onChange={(e) => onUpdateClinical('neurovascular', 'foot_skin_temp', e.target.value)}
                                disabled={!canManage}
                                placeholder="Hangat / dingin"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_c">c. Pengisian Kapiler (CRT)</Label>
                            <Input
                                id="ortho_b3_c"
                                value={cd.neurovascular.crt}
                                onChange={(e) => onUpdateClinical('neurovascular', 'crt', e.target.value)}
                                disabled={!canManage}
                                placeholder="< 2 detik"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_d">d. Denyut Nadi Perifer</Label>
                            <Input
                                id="ortho_b3_d"
                                value={cd.neurovascular.peripheral_pulse}
                                onChange={(e) => onUpdateClinical('neurovascular', 'peripheral_pulse', e.target.value)}
                                disabled={!canManage}
                                placeholder="Dorsalis pedis teraba kuat"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_e">e. Sensasi / Rasa pada Kaki</Label>
                            <Input
                                id="ortho_b3_e"
                                value={cd.neurovascular.foot_sensation}
                                onChange={(e) => onUpdateClinical('neurovascular', 'foot_sensation', e.target.value)}
                                disabled={!canManage}
                                placeholder="Baik (intak)"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_f">f. Kemampuan Menggerakkan Jari Kaki</Label>
                            <Input
                                id="ortho_b3_f"
                                value={cd.neurovascular.toe_movement}
                                onChange={(e) => onUpdateClinical('neurovascular', 'toe_movement', e.target.value)}
                                disabled={!canManage}
                                placeholder="Dapat menggerakkan jari kaki"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_g">g. Ada / Tidaknya Kesemutan</Label>
                            <Input
                                id="ortho_b3_g"
                                value={cd.neurovascular.tingling}
                                onChange={(e) => onUpdateClinical('neurovascular', 'tingling', e.target.value)}
                                disabled={!canManage}
                                placeholder="Tidak ada / ada kesemutan"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b3_h">h. Ada / Tidaknya Mati Rasa</Label>
                            <Input
                                id="ortho_b3_h"
                                value={cd.neurovascular.numbness}
                                onChange={(e) => onUpdateClinical('neurovascular', 'numbness', e.target.value)}
                                disabled={!canManage}
                                placeholder="Tidak ada / ada baal"
                                className="min-h-11"
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* B.4 Pemeriksaan Tanda-Tanda Vital (a-f) */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <HeartPulse className="text-primary h-4 w-4" />
                            B.4. Pemeriksaan Tanda-Tanda Vital
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b4_sys">a1. TD Sistolik (mmHg)</Label>
                            <Input
                                id="ortho_b4_sys"
                                type="number"
                                value={vs.bp_systolic}
                                onChange={(e) => onUpdateVital('bp_systolic', e.target.value)}
                                disabled={!canManage}
                                placeholder="120"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b4_dia">a2. TD Diastolik (mmHg)</Label>
                            <Input
                                id="ortho_b4_dia"
                                type="number"
                                value={vs.bp_diastolic}
                                onChange={(e) => onUpdateVital('bp_diastolic', e.target.value)}
                                disabled={!canManage}
                                placeholder="80"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b4_pulse">b. Frekuensi Nadi (x/mnt)</Label>
                            <Input
                                id="ortho_b4_pulse"
                                type="number"
                                value={vs.pulse}
                                onChange={(e) => onUpdateVital('pulse', e.target.value)}
                                disabled={!canManage}
                                placeholder="80"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b4_rr">c. Frekuensi Napas (x/mnt)</Label>
                            <Input
                                id="ortho_b4_rr"
                                type="number"
                                value={vs.respiratory_rate}
                                onChange={(e) => onUpdateVital('respiratory_rate', e.target.value)}
                                disabled={!canManage}
                                placeholder="18"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b4_temp">d. Suhu Tubuh (C)</Label>
                            <Input
                                id="ortho_b4_temp"
                                type="number"
                                step="0.1"
                                value={vs.temperature}
                                onChange={(e) => onUpdateVital('temperature', e.target.value)}
                                disabled={!canManage}
                                placeholder="36.6"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="ortho_b4_spo2">e. Saturasi O2 (%)</Label>
                            <Input
                                id="ortho_b4_spo2"
                                type="number"
                                value={vs.spo2}
                                onChange={(e) => onUpdateVital('spo2', e.target.value)}
                                disabled={!canManage}
                                placeholder="98"
                                className="min-h-11"
                            />
                        </div>
                        <div className="col-span-2 space-y-1.5 sm:col-span-3 lg:col-span-6">
                            <Label htmlFor="ortho_b4_f">f. Tingkat Kesadaran</Label>
                            <Select value={vs.consciousness} onValueChange={(v) => onUpdateVital('consciousness', v)} disabled={!canManage}>
                                <SelectTrigger id="ortho_b4_f" className="min-h-11">
                                    <SelectValue placeholder="Pilih tingkat kesadaran" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.consciousnessList.map((c) => (
                                        <SelectItem key={c.value} value={c.value}>
                                            {c.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors['vital_signs.consciousness']} />
                        </div>
                    </CardContent>
                </Card>

                {/* B.5 Pemeriksaan Penunjang (a-n) */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <FlaskConical className="text-primary h-4 w-4" />
                            B.5. Pemeriksaan Penunjang
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="ortho_b5_a">a. Hasil Rontgen Femur</Label>
                                <Input
                                    id="ortho_b5_a"
                                    value={cd.supporting_detail.xray_femur_result}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'xray_femur_result', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Diskontinuitas os femur dgn displacement"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_b">b. Lokasi &amp; Sisi Fraktur Berdasarkan Rontgen</Label>
                                <Input
                                    id="ortho_b5_b"
                                    value={cd.supporting_detail.xray_location_side}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'xray_location_side', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Diafisis 1/3 tengah femur kanan"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_c">c. Pola Fraktur</Label>
                                <Input
                                    id="ortho_b5_c"
                                    value={cd.supporting_detail.xray_pattern}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'xray_pattern', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Transversal / oblik / kominutif"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="ortho_b5_d">d. CT Scan (jika diperlukan)</Label>
                                <Input
                                    id="ortho_b5_d"
                                    value={cd.supporting_detail.ct_scan_note}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'ct_scan_note', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Tidak dilakukan / hasil CT Scan"
                                    className="min-h-11"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 border-t pt-4 sm:grid-cols-3 lg:grid-cols-5">
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_e">e. Hemoglobin (Hb)</Label>
                                <Input
                                    id="ortho_b5_e"
                                    value={cd.supporting_detail.hb}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'hb', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="12.0 g/dL"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_f">f. Hematokrit (Ht)</Label>
                                <Input
                                    id="ortho_b5_f"
                                    value={cd.supporting_detail.ht}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'ht', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="36%"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_g">g. Leukosit</Label>
                                <Input
                                    id="ortho_b5_g"
                                    value={cd.supporting_detail.leukosit}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'leukosit', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="9.500 /uL"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_h">h. Trombosit</Label>
                                <Input
                                    id="ortho_b5_h"
                                    value={cd.supporting_detail.trombosit}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'trombosit', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="250.000 /uL"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_i">i. Golongan Darah</Label>
                                <Input
                                    id="ortho_b5_i"
                                    value={cd.supporting_detail.blood_type}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'blood_type', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="A / B / AB / O"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_j">j. PT / INR</Label>
                                <Input
                                    id="ortho_b5_j"
                                    value={cd.supporting_detail.pt_inr}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'pt_inr', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="11.2 dtk / 1.0"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_k">k. APTT</Label>
                                <Input
                                    id="ortho_b5_k"
                                    value={cd.supporting_detail.aptt}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'aptt', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="28.5 dtk"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_l">l. Gula Darah</Label>
                                <Input
                                    id="ortho_b5_l"
                                    value={cd.supporting_detail.blood_glucose}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'blood_glucose', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="115 mg/dL"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_m">m. Elektrolit</Label>
                                <Input
                                    id="ortho_b5_m"
                                    value={cd.supporting_detail.electrolytes}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'electrolytes', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Na 140, K 4.1, Cl 102"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b5_n">n. Fungsi Ginjal (jika perlu)</Label>
                                <Input
                                    id="ortho_b5_n"
                                    value={cd.supporting_detail.renal_function}
                                    onChange={(e) => onUpdateClinical('supporting_detail', 'renal_function', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Ur 22 / Cr 0.8"
                                    className="min-h-11"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* B.6 Persiapan Operasi (a-n) */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <ClipboardCheck className="text-primary h-4 w-4" />
                            B.6. Persiapan Operasi
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="bg-muted/30 grid grid-cols-1 gap-3 rounded-lg border p-3.5 text-sm md:grid-cols-3">
                            <div>
                                <span className="text-muted-foreground block text-xs">a. Diagnosis Praoperasi</span>
                                <span className="font-medium">{selectedDiagnosisName}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground block text-xs">b. Lokasi &amp; Sisi Fraktur</span>
                                <span className="font-medium">{fractureLocationSideSummary || data.surgery_site || '-'}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground block text-xs">f. Riwayat Alergi &amp; Reaksi</span>
                                <span className="font-medium">{allergySummary}</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_site">b. Lokasi / Sisi Operasi</Label>
                                <Input
                                    id="ortho_b6_site"
                                    value={data.surgery_site}
                                    onChange={(e) => onSetField('surgery_site', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Femur Dekstra / Sinistra"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_c">c. Jam Keputusan Operasi</Label>
                                <Input
                                    id="ortho_b6_c"
                                    value={cd.prep_extra.decision_time}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'decision_time', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="contoh: 25 Nov 2026, 10:00 WIB"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_e_bb">e1. Berat Badan (kg)</Label>
                                <Input
                                    id="ortho_b6_e_bb"
                                    type="number"
                                    step="0.1"
                                    value={vs.weight_kg}
                                    onChange={(e) => onUpdateVital('weight_kg', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="65"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_e_tb">e2. Tinggi Badan (cm)</Label>
                                <Input
                                    id="ortho_b6_e_tb"
                                    type="number"
                                    step="0.1"
                                    value={vs.height_cm}
                                    onChange={(e) => onUpdateVital('height_cm', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="168"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_g">g. Pemasangan Infus</Label>
                                <Input
                                    id="ortho_b6_g"
                                    value={cd.prep_extra.iv_fluid_detail}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'iv_fluid_detail', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="IV line lancar, RL 20 tpm"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_h">h. Persiapan Darah (jika perlu)</Label>
                                <Input
                                    id="ortho_b6_h"
                                    value={cd.prep_extra.blood_prep}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'blood_prep', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Crossmatch PRC 2 kolf"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_i">i. Pemberian Antibiotik Sesuai Instruksi</Label>
                                <Input
                                    id="ortho_b6_i"
                                    value={cd.prep_extra.antibiotic_instruction}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'antibiotic_instruction', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Cefazolin 2 g IV profilaksis"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_k_nama">k1. Nama Pemberi Persetujuan</Label>
                                <Input
                                    id="ortho_b6_k_nama"
                                    value={cd.prep_extra.consent_signer}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'consent_signer', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Nama keluarga / penanggung jawab"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_k_rel">k2. Hubungan Pemberi Persetujuan</Label>
                                <Input
                                    id="ortho_b6_k_rel"
                                    value={cd.prep_extra.consent_relation}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'consent_relation', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Istri / Suami / Anak / Orang Tua"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="ortho_b6_m">m. Persiapan Alat &amp; Implan</Label>
                                <Input
                                    id="ortho_b6_m"
                                    value={cd.prep_extra.implant_instrument_prep}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'implant_instrument_prep', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Set instrumen ORIF femur, plate/screw/nail, dan C-Arm siap"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_b6_j_doc">j. Catatan Informed Consent Operasi</Label>
                                <Input
                                    id="ortho_b6_j_doc"
                                    value={cd.prep_extra.emergency_consent_doc}
                                    onChange={(e) => onUpdateClinical('prep_extra', 'emergency_consent_doc', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Informed consent ditandatangani lengkap"
                                    className="min-h-11"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-3 rounded-lg border p-3.5 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="flex cursor-pointer items-center gap-2 text-sm font-medium">
                                    <Checkbox
                                        checked={data.fasting_status}
                                        onCheckedChange={(c) => onSetField('fasting_status', c === true)}
                                        disabled={!canManage}
                                    />
                                    <span>d. Pasien Sudah Puasa</span>
                                </label>
                                {data.fasting_status && (
                                    <div className="space-y-1">
                                        <Label htmlFor="ortho_b6_d_since" className="text-xs">
                                            Waktu Terakhir Makan / Minum
                                        </Label>
                                        <Input
                                            id="ortho_b6_d_since"
                                            type="datetime-local"
                                            value={data.fasting_since}
                                            onChange={(e) => onSetField('fasting_since', e.target.value)}
                                            disabled={!canManage}
                                            className="min-h-11"
                                        />
                                        <InputError message={errors.fasting_since} />
                                    </div>
                                )}
                            </div>

                            <div className="space-y-2.5 text-sm">
                                <label className="flex cursor-pointer items-center gap-2 font-medium">
                                    <Checkbox
                                        checked={data.preparation.iv_access}
                                        onCheckedChange={(c) => onTogglePrep('iv_access', c === true)}
                                        disabled={!canManage}
                                    />
                                    <span>g. Akses Infus Terpasang &amp; Lancar</span>
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 font-medium">
                                    <Checkbox
                                        checked={data.preparation.informed_consent_surgery}
                                        onCheckedChange={(c) => onTogglePrep('informed_consent_surgery', c === true)}
                                        disabled={!canManage}
                                    />
                                    <span>j. Persetujuan Tindakan Operasi (Informed Consent) Lengkap</span>
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 font-medium">
                                    <Checkbox
                                        checked={data.preparation.site_marked === true}
                                        onCheckedChange={(c) => onTogglePrep('site_marked', c === true)}
                                        disabled={!canManage}
                                    />
                                    <span>l. Penandaan Lokasi Operasi (Site Marking) Sudah Dilakukan</span>
                                </label>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 rounded-lg border p-3.5 md:grid-cols-3">
                            <div className="flex items-center justify-between gap-2 md:col-span-2">
                                <div className="space-y-1">
                                    <div className="text-muted-foreground text-xs font-medium">
                                        m/n. Cek Alat Ortopedi (66 item) &amp; SSC Sign In
                                    </div>
                                    <div className="flex flex-wrap items-center gap-3 text-sm font-medium">
                                        <span className="inline-flex items-center gap-1.5">
                                            <Wrench className="text-primary h-4 w-4" />
                                            Cek Alat: {prepProgress.instrument_checks_count} item
                                        </span>
                                        <span className="inline-flex items-center gap-1.5">
                                            <ShieldCheck className="text-primary h-4 w-4" />
                                            Sign In: {prepProgress.ssc_sign_in_label}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button asChild size="sm" variant="outline" className="min-h-10">
                                        <Link href={route('cases.preparation', caseHeader.id)}>Tab Persiapan</Link>
                                    </Button>
                                    <Button asChild size="sm" variant="outline" className="min-h-10">
                                        <Link href={route('cases.ssc.sign-in', caseHeader.id)}>Tab SSC Sign In</Link>
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_readiness">Status Kesiapan Operasi *</Label>
                                <Select value={data.readiness_status} onValueChange={(v) => onSetField('readiness_status', v)} disabled={!canManage}>
                                    <SelectTrigger id="ortho_readiness" className="min-h-11">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.readinessStatuses.map((r) => (
                                            <SelectItem key={r.value} value={r.value}>
                                                {r.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.readiness_status} />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* BAGIAN C: Data Anestesi (1. Pengkajian Pra-Anestesi & 2. Pelaksanaan Anestesi) */}
            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-2 text-base font-semibold">
                        <Syringe className="text-primary h-4 w-4" />
                        C. Data Anestesi (Pra-Anestesi &amp; Pelaksanaan Anestesi)
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                    {/* C.1 Pengkajian Pra-Anestesi (a-h) */}
                    <div className="space-y-3">
                        <h4 className="border-b pb-1.5 text-sm font-semibold">1. Pengkajian Pra-Anestesi</h4>
                        <div className="bg-muted/30 grid grid-cols-1 gap-3 rounded-lg border p-3.5 text-sm md:grid-cols-3">
                            <div>
                                <span className="text-muted-foreground block text-xs">a. Riwayat Penyakit Pasien</span>
                                <span className="font-medium">{data.past_illness_history || 'Sesuai data B.1.f'}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground block text-xs">b. Riwayat Alergi</span>
                                <span className="font-medium">{allergySummary}</span>
                            </div>
                            <div>
                                <span className="text-muted-foreground block text-xs">f. Berat Badan</span>
                                <span className="font-medium">{vs.weight_kg ? `${vs.weight_kg} kg` : 'Sesuai B.6.e'}</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c1_c">c. Riwayat Anestesi Sebelumnya</Label>
                                <Input
                                    id="ortho_c1_c"
                                    value={data.anesthesia_history}
                                    onChange={(e) => onSetField('anesthesia_history', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Belum pernah / jenis anestesi terdahulu"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c1_d">d. Pemeriksaan Jalan Napas</Label>
                                <Input
                                    id="ortho_c1_d"
                                    value={cd.anesthesia_pre.airway_exam}
                                    onChange={(e) => onUpdateClinical('anesthesia_pre', 'airway_exam', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Mallampati I-II, buka mulut >= 3 jari"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c1_e">e. Kondisi Jantung &amp; Pernapasan</Label>
                                <Input
                                    id="ortho_c1_e"
                                    value={cd.anesthesia_pre.cardiopulmonary}
                                    onChange={(e) => onUpdateClinical('anesthesia_pre', 'cardiopulmonary', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="BJ I-II reguler, vesikuler +/+"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c1_g">g. Klasifikasi ASA</Label>
                                <Input
                                    id="ortho_c1_g"
                                    value={cd.anesthesia_pre.asa_class}
                                    onChange={(e) => onUpdateClinical('anesthesia_pre', 'asa_class', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="ASA I / II / III E"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c1_h_select">h1. Pilihan Jenis Anestesi</Label>
                                <Select
                                    value={data.anesthesia_type_id}
                                    onValueChange={(v) => onSetField('anesthesia_type_id', v)}
                                    disabled={!canManage}
                                >
                                    <SelectTrigger id="ortho_c1_h_select" className="min-h-11">
                                        <SelectValue placeholder="Pilih Jenis Anestesi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.anesthesiaTypes.map((a) => (
                                            <SelectItem key={a.id} value={String(a.id)}>
                                                {a.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c1_h_plan">h2. Rencana Jenis Anestesi</Label>
                                <Input
                                    id="ortho_c1_h_plan"
                                    value={cd.anesthesia_pre.planned_anesthesia}
                                    onChange={(e) => onUpdateClinical('anesthesia_pre', 'planned_anesthesia', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Regional Spinal / General Anesthesia"
                                    className="min-h-11"
                                />
                            </div>
                        </div>
                    </div>

                    {/* C.2 Pelaksanaan Anestesi (a-n) */}
                    <div className="space-y-3 border-t pt-4">
                        <h4 className="border-b pb-1.5 text-sm font-semibold">2. Pelaksanaan Anestesi</h4>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_a">a. Jenis Anestesi (Spinal / Umum)</Label>
                                <Input
                                    id="ortho_c2_a"
                                    value={cd.anesthesia_exec.anesthesia_method}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'anesthesia_method', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Spinal / Anestesi Umum (ETT)"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_b">b. Waktu Mulai Anestesi</Label>
                                <Input
                                    id="ortho_c2_b"
                                    value={cd.anesthesia_exec.anesthesia_start_time}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'anesthesia_start_time', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="10:45 WIB"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_c">c. Waktu Selesai Anestesi</Label>
                                <Input
                                    id="ortho_c2_c"
                                    value={cd.anesthesia_exec.anesthesia_end_time}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'anesthesia_end_time', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="13:15 WIB"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_d">d. Obat Anestesi</Label>
                                <Input
                                    id="ortho_c2_d"
                                    value={cd.anesthesia_exec.medications_summary}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'medications_summary', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Bupivacaine 0.5% / Propofol, Fentanyl"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_e">e. Dosis Obat</Label>
                                <Input
                                    id="ortho_c2_e"
                                    value={cd.anesthesia_exec.medication_dose}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'medication_dose', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Bupivacaine 15 mg"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_f">f. Waktu Pemberian Obat</Label>
                                <Input
                                    id="ortho_c2_f"
                                    value={cd.anesthesia_exec.medication_time}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'medication_time', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="10:50 WIB"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_g">g. Alat Bantu Jalan Napas (jika perlu)</Label>
                                <Input
                                    id="ortho_c2_g"
                                    value={cd.anesthesia_exec.airway_aid}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'airway_aid', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Nasal kanul / ETT / LMA / Tidak diperlukan"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_h">h. Pengaturan Ventilator (jika digunakan)</Label>
                                <Input
                                    id="ortho_c2_h"
                                    value={cd.anesthesia_exec.ventilator_setting}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'ventilator_setting', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="VCV TV 450 mL, RR 12x/mnt / Tidak digunakan"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_i">i. Cairan yang Diberikan</Label>
                                <Input
                                    id="ortho_c2_i"
                                    value={cd.anesthesia_exec.fluids_given}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'fluids_given', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="RL 1000 mL"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_j">j. Tekanan Darah (mmHg)</Label>
                                <Input
                                    id="ortho_c2_j"
                                    value={cd.anesthesia_exec.vitals_bp ?? ''}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'vitals_bp', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="120/80 mmHg"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_k">k. Frekuensi Nadi (x/mnt)</Label>
                                <Input
                                    id="ortho_c2_k"
                                    value={cd.anesthesia_exec.vitals_pulse ?? ''}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'vitals_pulse', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="82 x/mnt"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_l">l. Frekuensi Pernapasan (x/mnt)</Label>
                                <Input
                                    id="ortho_c2_l"
                                    value={cd.anesthesia_exec.vitals_rr ?? ''}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'vitals_rr', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="16 x/mnt"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="ortho_c2_m">m. Saturasi Oksigen (%)</Label>
                                <Input
                                    id="ortho_c2_m"
                                    value={cd.anesthesia_exec.vitals_spo2 ?? ''}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'vitals_spo2', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="99%"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="ortho_c2_n">n. Komplikasi Anestesi (jika ada)</Label>
                                <Input
                                    id="ortho_c2_n"
                                    value={cd.anesthesia_exec.complications}
                                    onChange={(e) => onUpdateClinical('anesthesia_exec', 'complications', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Tidak ada komplikasi anestesi"
                                    className="min-h-11"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
