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
import { AlertTriangle, Brain, ClipboardCheck, FlaskConical, HeartPulse, ShieldAlert, ShieldCheck, Syringe, UserCheck, Wrench } from 'lucide-react';
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

export type PreopEdhMainData = {
    medical_diagnosis_id: string;
    medical_diagnosis_other: string;
    surgery_site: string;
    anesthesia_type_id: string;
    chief_complaint: string;
    past_illness_history: string;
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

interface PreopEdhFormProps {
    caseHeader: CaseHeaderData;
    prepProgress: {
        instrument_checks_count: number;
        instrument_checks_ready: boolean;
        ssc_sign_in_status: string;
        ssc_sign_in_label: string;
    };
    data: PreopEdhMainData;
    errors: Record<string, string | undefined>;
    options: {
        medicalDiagnoses: MasterItem[];
        anesthesiaTypes: MasterItem[];
        allergyStatuses: OptionItem[];
        readinessStatuses: OptionItem[];
        consciousnessList: OptionItem[];
    };
    canManage: boolean;
    onSetField: <K extends keyof PreopEdhMainData>(field: K, value: PreopEdhMainData[K]) => void;
    onUpdateClinical: UpdateClinicalFieldFn;
    onUpdateVital: (field: keyof PreopEdhMainData['vital_signs'], value: string) => void;
    onTogglePrep: (field: 'iv_access' | 'informed_consent_surgery' | 'site_marked', checked: boolean) => void;
}

export function PreopEdhForm({
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
}: PreopEdhFormProps) {
    const bpSys = Number(data.vital_signs.bp_systolic);
    const bpDia = Number(data.vital_signs.bp_diastolic);
    const pulse = Number(data.vital_signs.pulse);
    const temp = Number(data.vital_signs.temperature);
    const rr = Number(data.vital_signs.respiratory_rate);
    const spo2 = Number(data.vital_signs.spo2);

    const bpWarning = Boolean((bpSys && (bpSys > 140 || bpSys < 90)) || (bpDia && (bpDia > 90 || bpDia < 60)));
    const pulseWarning = pulse && (pulse > 100 || pulse < 60);
    const tempWarning = temp && (temp > 37.5 || temp < 36.0);
    const rrWarning = rr && (rr > 22 || rr < 12);
    const spo2Warning = spo2 && spo2 < 95;

    const gcsTotal =
        (Number(data.clinical_details.neurological.gcs_e) || 0) +
        (Number(data.clinical_details.neurological.gcs_v) || 0) +
        (Number(data.clinical_details.neurological.gcs_m) || 0);

    const allergyLabel = options.allergyStatuses.find((s) => s.value === data.allergy_status)?.label ?? 'Belum Diketahui';

    return (
        <div className="space-y-6">
            {/* A. Data Identitas dan Administrasi Pasien (18 Item SKPL) */}
            <Card>
                <CardHeader className="flex flex-col gap-2 pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <UserCheck className="text-primary h-5 w-5" />
                            A. Data Identitas dan Administrasi Pasien
                        </CardTitle>
                        <CardDescription>Data registrasi pasien dan kasus Bedah Saraf Epidural Hematoma (EDH).</CardDescription>
                    </div>
                    <Button asChild variant="outline" size="sm" className="min-h-11">
                        <Link href={route('cases.show', caseHeader.id)}>Lihat / Sunting di Ringkasan Kasus</Link>
                    </Button>
                </CardHeader>
                <CardContent className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">1. Nomor Rekam Medis</div>
                        <div className="font-mono font-semibold tabular-nums">{caseHeader.patient.rm_number}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">2. Nomor Registrasi Operasi</div>
                        <div className="font-mono font-semibold tabular-nums">{caseHeader.case_number}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">3. Nomor Kunjungan IGD</div>
                        <div className="font-mono font-medium tabular-nums">{caseHeader.igd_visit_number || '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">4. Nama Lengkap Pasien</div>
                        <div className="font-semibold">{caseHeader.patient.name}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">5. Tanggal Lahir &amp; Umur</div>
                        <div className="font-medium">
                            {caseHeader.patient.birth_date} ({caseHeader.patient.age_label})
                        </div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">6. Jenis Kelamin</div>
                        <div className="font-medium">{caseHeader.patient.gender_label}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">7. Alamat Pasien</div>
                        <div className="font-medium">{caseHeader.patient.address || '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">8. Nomor Identitas Pasien</div>
                        <div className="font-mono font-medium tabular-nums">{caseHeader.patient.identity_number || '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">9. Nama Keluarga / Penanggung Jawab</div>
                        <div className="font-medium">{caseHeader.patient.guardian_name || '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">10. Hubungan dengan Pasien</div>
                        <div className="font-medium">{caseHeader.patient.guardian_relation || '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">11. Nomor Telepon Keluarga</div>
                        <div className="font-mono font-medium tabular-nums">{caseHeader.patient.guardian_phone || '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">12. Tanggal &amp; Jam Masuk IGD</div>
                        <div className="font-medium tabular-nums">{caseHeader.igd_admitted_at ? `${caseHeader.igd_admitted_at} WIB` : '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">13. Asal Pasien</div>
                        <div className="font-medium">{caseHeader.origin_ward || 'IGD'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">14. Tanggal &amp; Jam Masuk Kamar Operasi</div>
                        <div className="font-medium tabular-nums">{caseHeader.admitted_at} WIB</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">15. Status Tindakan</div>
                        <div className="font-medium">{caseHeader.urgency_label ?? 'Operasi Darurat (Emergency)'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">16. Diagnosis Medis</div>
                        <div className="font-semibold">{caseHeader.diagnosis}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">17. Nama Dokter Penanggung Jawab</div>
                        <div className="font-medium">{caseHeader.dpjp_name || '-'}</div>
                    </div>
                    <div className="rounded-lg border p-2.5">
                        <div className="text-muted-foreground text-xs">18. Nama Petugas Input Data</div>
                        <div className="font-medium">{caseHeader.created_by_name || '-'}</div>
                    </div>
                </CardContent>
            </Card>

            {/* B. Data Pengkajian Praoperasi: Riwayat Kejadian dan Keluhan Pasien (11 Item SKPL) */}
            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="text-base font-semibold">B. Riwayat Kejadian dan Keluhan Pasien</CardTitle>
                    <CardDescription>Pengkajian praoperasi kejadian cedera kepala, keluhan neurologis, riwayat penyakit, dan alergi.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_injury_cause">1. Penyebab Cedera</Label>
                            <Input
                                id="edh_injury_cause"
                                value={data.clinical_details.incident.injury_cause}
                                onChange={(e) => onUpdateClinical('incident', 'injury_cause', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Kecelakaan lalu lintas / jatuh"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_incident_datetime">2. Tanggal &amp; Waktu Kejadian</Label>
                            <Input
                                id="edh_incident_datetime"
                                type="datetime-local"
                                value={data.clinical_details.incident.incident_datetime}
                                onChange={(e) => onUpdateClinical('incident', 'incident_datetime', e.target.value)}
                                disabled={!canManage}
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_incident_location">3. Lokasi Kejadian</Label>
                            <Input
                                id="edh_incident_location"
                                value={data.clinical_details.incident.incident_location}
                                onChange={(e) => onUpdateClinical('incident', 'incident_location', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Jalan raya / rumah"
                            />
                        </div>
                        <div className="space-y-1.5 md:col-span-2 lg:col-span-3">
                            <Label htmlFor="chief_complaint">4. Keluhan Utama</Label>
                            <Input
                                id="chief_complaint"
                                value={data.chief_complaint}
                                onChange={(e) => onSetField('chief_complaint', e.target.value)}
                                disabled={!canManage}
                                placeholder="Keluhan utama pasien saat masuk..."
                            />
                            <InputError message={errors.chief_complaint} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_headache_history">5. Riwayat Sakit Kepala</Label>
                            <Input
                                id="edh_headache_history"
                                value={data.clinical_details.incident.headache_history}
                                onChange={(e) => onUpdateClinical('incident', 'headache_history', e.target.value)}
                                disabled={!canManage}
                                placeholder="Nyeri kepala progresif / tidak ada"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_nausea_vomiting">6. Riwayat Mual Muntah</Label>
                            <Input
                                id="edh_nausea_vomiting"
                                value={data.clinical_details.incident.nausea_vomiting}
                                onChange={(e) => onUpdateClinical('incident', 'nausea_vomiting', e.target.value)}
                                disabled={!canManage}
                                placeholder="Muntah proyektil / tidak ada"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_loss_of_consciousness">7. Riwayat Kehilangan Kesadaran</Label>
                            <Input
                                id="edh_loss_of_consciousness"
                                value={data.clinical_details.incident.loss_of_consciousness}
                                onChange={(e) => onUpdateClinical('incident', 'loss_of_consciousness', e.target.value)}
                                disabled={!canManage}
                                placeholder="Pingsan / lucid interval (+)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_seizure_history">8. Riwayat Kejang</Label>
                            <Input
                                id="edh_seizure_history"
                                value={data.clinical_details.incident.seizure_history}
                                onChange={(e) => onUpdateClinical('incident', 'seizure_history', e.target.value)}
                                disabled={!canManage}
                                placeholder="Ada / tidak ada riwayat kejang"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="past_illness_history">9. Riwayat Penyakit Sebelumnya</Label>
                            <Input
                                id="past_illness_history"
                                value={data.past_illness_history}
                                onChange={(e) => onSetField('past_illness_history', e.target.value)}
                                disabled={!canManage}
                                placeholder="Hipertensi, DM, gangguan koagulasi, atau tidak ada"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_anticoagulant_use">10. Riwayat Penggunaan Obat Pengencer Darah</Label>
                            <Input
                                id="edh_anticoagulant_use"
                                value={data.clinical_details.incident.anticoagulant_use}
                                onChange={(e) => onUpdateClinical('incident', 'anticoagulant_use', e.target.value)}
                                disabled={!canManage}
                                placeholder="Aspirin, Warfarin, Clopidogrel, atau tidak ada"
                            />
                        </div>
                    </div>

                    <div className="bg-muted/30 grid grid-cols-1 items-start gap-4 rounded-xl border p-3.5 md:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="allergy_status" className="flex items-center gap-1.5 font-semibold">
                                <ShieldAlert className="h-4 w-4 text-amber-600" />
                                11. Riwayat Alergi Obat dan Makanan
                            </Label>
                            <Select value={data.allergy_status} onValueChange={(val) => onSetField('allergy_status', val)} disabled={!canManage}>
                                <SelectTrigger id="allergy_status">
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
                        <div className="space-y-1.5 md:col-span-2">
                            <Label htmlFor="allergy_detail">
                                Detail Obat / Makanan &amp; Reaksi Alergi{' '}
                                {data.allergy_status === 'ada' && <span className="text-destructive">* (Wajib diisi)</span>}
                            </Label>
                            <Input
                                id="allergy_detail"
                                value={data.allergy_detail}
                                onChange={(e) => onSetField('allergy_detail', e.target.value)}
                                disabled={!canManage || data.allergy_status !== 'ada'}
                                placeholder={data.allergy_status === 'ada' ? 'Sebutkan alergi & reaksinya...' : 'Tidak ada alergi'}
                            />
                            <InputError message={errors.allergy_detail} />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* C. Pemeriksaan Neurologis (8 Item SKPL) */}
            <Card>
                <CardHeader className="flex flex-col gap-2 pb-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-base font-semibold">
                            <Brain className="text-primary h-5 w-5" />
                            C. Pemeriksaan Neurologis
                        </CardTitle>
                        <CardDescription>
                            Penilaian kesadaran, GCS (E, V, M), ukuran &amp; reaksi pupil, motorik, sensorik, kejang, dan pemantauan.
                        </CardDescription>
                    </div>
                    {gcsTotal > 0 && (
                        <Badge variant="secondary" className="text-xs font-semibold tabular-nums">
                            Total GCS: {gcsTotal}
                        </Badge>
                    )}
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_consciousness">1. Tingkat Kesadaran</Label>
                            <Select
                                value={data.vital_signs.consciousness}
                                onValueChange={(val) => {
                                    onUpdateVital('consciousness', val);
                                    const matched = options.consciousnessList.find((c) => c.value === val);
                                    if (matched) {
                                        onUpdateClinical('neurological', 'consciousness_level', matched.label);
                                    }
                                }}
                                disabled={!canManage}
                            >
                                <SelectTrigger id="edh_consciousness">
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
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_gcs_e">2a. Nilai GCS Eye (E)</Label>
                            <Input
                                id="edh_gcs_e"
                                value={data.clinical_details.neurological.gcs_e}
                                onChange={(e) => onUpdateClinical('neurological', 'gcs_e', e.target.value)}
                                disabled={!canManage}
                                placeholder="1 - 4"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_gcs_v">2b. Nilai GCS Verbal (V)</Label>
                            <Input
                                id="edh_gcs_v"
                                value={data.clinical_details.neurological.gcs_v}
                                onChange={(e) => onUpdateClinical('neurological', 'gcs_v', e.target.value)}
                                disabled={!canManage}
                                placeholder="1 - 5"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_gcs_m">2c. Nilai GCS Motorik (M)</Label>
                            <Input
                                id="edh_gcs_m"
                                value={data.clinical_details.neurological.gcs_m}
                                onChange={(e) => onUpdateClinical('neurological', 'gcs_m', e.target.value)}
                                disabled={!canManage}
                                placeholder="1 - 6"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_pupil_right">3a. Ukuran Pupil Kanan (mm)</Label>
                            <Input
                                id="edh_pupil_right"
                                value={data.clinical_details.neurological.pupil_size_right}
                                onChange={(e) => onUpdateClinical('neurological', 'pupil_size_right', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: 5 mm"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_pupil_left">3b. Ukuran Pupil Kiri (mm)</Label>
                            <Input
                                id="edh_pupil_left"
                                value={data.clinical_details.neurological.pupil_size_left}
                                onChange={(e) => onUpdateClinical('neurological', 'pupil_size_left', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: 3 mm"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_pupil_light">4. Reaksi Pupil terhadap Cahaya</Label>
                            <Input
                                id="edh_pupil_light"
                                value={data.clinical_details.neurological.pupil_light_reaction}
                                onChange={(e) => onUpdateClinical('neurological', 'pupil_light_reaction', e.target.value)}
                                disabled={!canManage}
                                placeholder="Kanan lambat / kiri cepat (+)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_motor_right">5a. Kekuatan Motorik Ekstremitas Kanan</Label>
                            <Input
                                id="edh_motor_right"
                                value={data.clinical_details.neurological.motor_right}
                                onChange={(e) => onUpdateClinical('neurological', 'motor_right', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: 5 / 5"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_motor_left">5b. Kekuatan Motorik Ekstremitas Kiri</Label>
                            <Input
                                id="edh_motor_left"
                                value={data.clinical_details.neurological.motor_left}
                                onChange={(e) => onUpdateClinical('neurological', 'motor_left', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: 3 / 3"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_sensory">6. Respons Sensorik</Label>
                            <Input
                                id="edh_sensory"
                                value={data.clinical_details.neurological.sensory_response}
                                onChange={(e) => onUpdateClinical('neurological', 'sensory_response', e.target.value)}
                                disabled={!canManage}
                                placeholder="Respons terhadap rangsang nyeri"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_seizure_status">7. Ada Kejang atau Tidak</Label>
                            <Input
                                id="edh_seizure_status"
                                value={data.clinical_details.neurological.seizure_status}
                                onChange={(e) => onUpdateClinical('neurological', 'seizure_status', e.target.value)}
                                disabled={!canManage}
                                placeholder="Tidak ada kejang"
                            />
                        </div>
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="edh_neuro_change">8. Perubahan Kondisi Neurologis Selama Pemantauan</Label>
                            <Input
                                id="edh_neuro_change"
                                value={data.clinical_details.neurological.neuro_change_during_monitoring}
                                onChange={(e) => onUpdateClinical('neurological', 'neuro_change_during_monitoring', e.target.value)}
                                disabled={!canManage}
                                placeholder="Penurunan kesadaran / stabil selama pemantauan"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* D. Pemeriksaan TTV (6 Item SKPL: TD, Nadi, Pernapasan, Suhu, SpO2, GDS) */}
            <Card>
                <CardHeader className="pb-4">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-base font-semibold">
                                <HeartPulse className="h-5 w-5 text-rose-600" />
                                D. Pemeriksaan Tanda-Tanda Vital (TTV)
                            </CardTitle>
                            <CardDescription>Pengukuran tekanan darah, nadi, pernapasan, suhu, saturasi oksigen, dan GDS praoperasi.</CardDescription>
                        </div>
                        {(bpWarning || pulseWarning || tempWarning || rrWarning || spo2Warning) && (
                            <Badge variant="outline" className="border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                                <AlertTriangle className="mr-1 h-3.5 w-3.5" />
                                Peringatan: TTV di luar batas normal
                            </Badge>
                        )}
                    </div>
                </CardHeader>
                <CardContent className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7">
                    <div className="space-y-1.5">
                        <Label htmlFor="bp_systolic" className="text-xs">
                            1a. TD Sistolik (mmHg)
                        </Label>
                        <Input
                            id="bp_systolic"
                            type="number"
                            value={data.vital_signs.bp_systolic}
                            onChange={(e) => onUpdateVital('bp_systolic', e.target.value)}
                            disabled={!canManage}
                            placeholder="120"
                        />
                        {bpWarning && <p className="text-[11px] font-medium text-amber-700">Di luar 90-140</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="bp_diastolic" className="text-xs">
                            1b. TD Diastolik (mmHg)
                        </Label>
                        <Input
                            id="bp_diastolic"
                            type="number"
                            value={data.vital_signs.bp_diastolic}
                            onChange={(e) => onUpdateVital('bp_diastolic', e.target.value)}
                            disabled={!canManage}
                            placeholder="80"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="pulse" className="text-xs">
                            2. Frekuensi Nadi (x/mnt)
                        </Label>
                        <Input
                            id="pulse"
                            type="number"
                            value={data.vital_signs.pulse}
                            onChange={(e) => onUpdateVital('pulse', e.target.value)}
                            disabled={!canManage}
                            placeholder="80"
                        />
                        {pulseWarning && <p className="text-[11px] font-medium text-amber-700">Di luar 60-100</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="rr" className="text-xs">
                            3. Frekuensi Pernapasan (x/mnt)
                        </Label>
                        <Input
                            id="rr"
                            type="number"
                            value={data.vital_signs.respiratory_rate}
                            onChange={(e) => onUpdateVital('respiratory_rate', e.target.value)}
                            disabled={!canManage}
                            placeholder="18"
                        />
                        {rrWarning && <p className="text-[11px] font-medium text-amber-700">Di luar 12-22</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="temperature" className="text-xs">
                            4. Suhu Tubuh (°C)
                        </Label>
                        <Input
                            id="temperature"
                            type="number"
                            step="0.1"
                            value={data.vital_signs.temperature}
                            onChange={(e) => onUpdateVital('temperature', e.target.value)}
                            disabled={!canManage}
                            placeholder="36.5"
                        />
                        {tempWarning && <p className="text-[11px] font-medium text-amber-700">Di luar 36.0-37.5</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="spo2" className="text-xs">
                            5. Saturasi Oksigen (SpO₂ %)
                        </Label>
                        <Input
                            id="spo2"
                            type="number"
                            value={data.vital_signs.spo2}
                            onChange={(e) => onUpdateVital('spo2', e.target.value)}
                            disabled={!canManage}
                            placeholder="98"
                        />
                        {spo2Warning && <p className="text-[11px] font-medium text-amber-700">SpO₂ &lt; 95%</p>}
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_gds" className="text-xs">
                            6. GDS (mg/dL)
                        </Label>
                        <Input
                            id="edh_gds"
                            value={data.clinical_details.vital_extra.gds}
                            onChange={(e) => onUpdateClinical('vital_extra', 'gds', e.target.value)}
                            disabled={!canManage}
                            placeholder="132 mg/dL"
                        />
                    </div>
                </CardContent>
            </Card>

            {/* E. Pemeriksaan Penunjang (15 Item SKPL: 7 CT Scan Kepala + 8 Lab tanpa PT/INR & aPTT) */}
            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-2 text-base font-semibold">
                        <FlaskConical className="text-primary h-5 w-5" />
                        E. Pemeriksaan Penunjang
                    </CardTitle>
                    <CardDescription>Hasil CT scan kepala dan pemeriksaan laboratorium praoperasi EDH.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5 sm:col-span-2 lg:col-span-3">
                            <Label htmlFor="edh_ct_scan">1. Hasil CT Scan Kepala</Label>
                            <Input
                                id="edh_ct_scan"
                                value={data.clinical_details.supporting_detail.ct_scan_result}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'ct_scan_result', e.target.value)}
                                disabled={!canManage}
                                placeholder="Lesi hiperdens bikonveks epidural"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_hematoma_loc">2. Lokasi Hematoma (Frontal / Temporal / Parietal / Oksipital)</Label>
                            <Input
                                id="edh_hematoma_loc"
                                value={data.clinical_details.supporting_detail.hematoma_location}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'hematoma_location', e.target.value)}
                                disabled={!canManage}
                                placeholder="Temporal / Temporoparietal"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_hematoma_side">3. Sisi Hematoma (Kanan / Kiri / Bilateral)</Label>
                            <Input
                                id="edh_hematoma_side"
                                value={data.clinical_details.supporting_detail.hematoma_side}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'hematoma_side', e.target.value)}
                                disabled={!canManage}
                                placeholder="Kanan / Kiri / Bilateral"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_hematoma_thick">4. Ketebalan / Ukuran Hematoma</Label>
                            <Input
                                id="edh_hematoma_thick"
                                value={data.clinical_details.supporting_detail.hematoma_thickness}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'hematoma_thickness', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: 24 mm (estimasi 45 mL)"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_midline_shift">5. Ada / Tidak Midline Shift</Label>
                            <Input
                                id="edh_midline_shift"
                                value={data.clinical_details.supporting_detail.midline_shift}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'midline_shift', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Ada (bergeser ke kiri 7 mm) / Tidak ada"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_skull_fracture">6. Ada / Tidak Fraktur Tengkorak</Label>
                            <Input
                                id="edh_skull_fracture"
                                value={data.clinical_details.supporting_detail.skull_fracture}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'skull_fracture', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Ada fraktur linier os temporal / Tidak ada"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_assoc_bleed">7. Adanya Perdarahan / Cedera Penyerta</Label>
                            <Input
                                id="edh_assoc_bleed"
                                value={data.clinical_details.supporting_detail.associated_bleeding}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'associated_bleeding', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Tidak ada perdarahan intraserebral"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_hb">8. Hemoglobin</Label>
                            <Input
                                id="edh_hb"
                                value={data.clinical_details.supporting_detail.hb}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'hb', e.target.value)}
                                disabled={!canManage}
                                placeholder="13.1 g/dL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_ht">9. Hematokrit</Label>
                            <Input
                                id="edh_ht"
                                value={data.clinical_details.supporting_detail.ht}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'ht', e.target.value)}
                                disabled={!canManage}
                                placeholder="39 %"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_leukosit">10. Leukosit</Label>
                            <Input
                                id="edh_leukosit"
                                value={data.clinical_details.supporting_detail.leukosit}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'leukosit', e.target.value)}
                                disabled={!canManage}
                                placeholder="11.500 /uL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_trombosit">11. Trombosit</Label>
                            <Input
                                id="edh_trombosit"
                                value={data.clinical_details.supporting_detail.trombosit}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'trombosit', e.target.value)}
                                disabled={!canManage}
                                placeholder="240.000 /uL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_blood_type">12. Golongan Darah</Label>
                            <Input
                                id="edh_blood_type"
                                value={data.clinical_details.supporting_detail.blood_type}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'blood_type', e.target.value)}
                                disabled={!canManage}
                                placeholder="A / B / AB / O"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_electrolytes">13. Elektrolit</Label>
                            <Input
                                id="edh_electrolytes"
                                value={data.clinical_details.supporting_detail.electrolytes}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'electrolytes', e.target.value)}
                                disabled={!canManage}
                                placeholder="Na 141, K 3.9, Cl 103 mEq/L"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_blood_glucose">14. Gula Darah</Label>
                            <Input
                                id="edh_blood_glucose"
                                value={data.clinical_details.supporting_detail.blood_glucose}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'blood_glucose', e.target.value)}
                                disabled={!canManage}
                                placeholder="132 mg/dL"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_renal">15. Fungsi Ginjal (jika diperlukan)</Label>
                            <Input
                                id="edh_renal"
                                value={data.clinical_details.supporting_detail.renal_function}
                                onChange={(e) => onUpdateClinical('supporting_detail', 'renal_function', e.target.value)}
                                disabled={!canManage}
                                placeholder="Ur 28 / Cr 0.9 mg/dL"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* F. Persiapan Operasi (15 Item SKPL) */}
            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-2 text-base font-semibold">
                        <ShieldCheck className="text-primary h-5 w-5" />
                        F. Persiapan Operasi
                    </CardTitle>
                    <CardDescription>
                        Kesiapan kraniotomi evakuasi hematoma, persetujuan darurat, penandaan area, alat, dan SSC Sign In.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-5">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_diag_preop">1. Diagnosis Praoperasi</Label>
                            <Select
                                value={data.medical_diagnosis_id}
                                onValueChange={(val) => onSetField('medical_diagnosis_id', val)}
                                disabled={!canManage}
                            >
                                <SelectTrigger id="edh_diag_preop">
                                    <SelectValue placeholder="Pilih Diagnosis Medis" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.medicalDiagnoses.map((d) => (
                                        <SelectItem key={d.id} value={String(d.id)}>
                                            {d.name}
                                        </SelectItem>
                                    ))}
                                    <SelectItem value="other">Lainnya (Ketik Manual)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {data.medical_diagnosis_id === 'other' && (
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_diag_other">Diagnosis Praoperasi (Lainnya)</Label>
                                <Input
                                    id="edh_diag_other"
                                    value={data.medical_diagnosis_other}
                                    onChange={(e) => onSetField('medical_diagnosis_other', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="Ketik diagnosis praoperasi..."
                                />
                            </div>
                        )}

                        <div className="space-y-1.5">
                            <Label htmlFor="edh_surgery_site">2. Lokasi &amp; Sisi Kepala yang Akan Dioperasi</Label>
                            <Input
                                id="edh_surgery_site"
                                value={data.surgery_site}
                                onChange={(e) => onSetField('surgery_site', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Regio Temporal Kanan"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edh_decision_time">3. Jam Keputusan Operasi</Label>
                            <Input
                                id="edh_decision_time"
                                type="datetime-local"
                                value={data.clinical_details.prep_extra.decision_time}
                                onChange={(e) => onUpdateClinical('prep_extra', 'decision_time', e.target.value)}
                                disabled={!canManage}
                                className="min-h-11"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div className="bg-muted/30 space-y-2.5 rounded-xl border p-3.5">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="fasting_status"
                                    checked={data.fasting_status}
                                    onCheckedChange={(checked) => onSetField('fasting_status', Boolean(checked))}
                                    disabled={!canManage}
                                />
                                <Label htmlFor="fasting_status" className="cursor-pointer text-xs font-semibold">
                                    4. Status Puasa (Sudah Puasa)
                                </Label>
                            </div>
                            {data.fasting_status && (
                                <div className="space-y-1">
                                    <Label htmlFor="fasting_since" className="text-xs">
                                        Waktu Terakhir Makan / Minum <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="fasting_since"
                                        type="datetime-local"
                                        value={data.fasting_since}
                                        onChange={(e) => onSetField('fasting_since', e.target.value)}
                                        disabled={!canManage}
                                    />
                                    <InputError message={errors.fasting_since} />
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="weight_kg" className="text-xs">
                                    5a. Berat Badan (kg)
                                </Label>
                                <Input
                                    id="weight_kg"
                                    type="number"
                                    step="0.1"
                                    value={data.vital_signs.weight_kg}
                                    onChange={(e) => onUpdateVital('weight_kg', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="68"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="height_cm" className="text-xs">
                                    5b. Tinggi Badan (cm)
                                </Label>
                                <Input
                                    id="height_cm"
                                    type="number"
                                    step="0.1"
                                    value={data.vital_signs.height_cm}
                                    onChange={(e) => onUpdateVital('height_cm', e.target.value)}
                                    disabled={!canManage}
                                    placeholder="170"
                                />
                            </div>
                        </div>

                        <div className="bg-muted/20 rounded-xl border p-3.5 text-xs">
                            <div className="text-muted-foreground font-medium">6. Riwayat Alergi dan Reaksi Alergi (Bagian B.11)</div>
                            <div className="text-foreground mt-1 font-semibold">
                                {allergyLabel}
                                {data.allergy_status === 'ada' && data.allergy_detail ? `: ${data.allergy_detail}` : ''}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div className="bg-muted/30 space-y-2 rounded-xl border p-3.5">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="iv_access"
                                    checked={data.preparation.iv_access}
                                    onCheckedChange={(checked) => onTogglePrep('iv_access', Boolean(checked))}
                                    disabled={!canManage}
                                />
                                <Label htmlFor="iv_access" className="cursor-pointer text-xs font-semibold">
                                    7. Pemasangan Infus Terpasang
                                </Label>
                            </div>
                            <Input
                                id="edh_iv_fluid_detail"
                                value={data.clinical_details.prep_extra.iv_fluid_detail}
                                onChange={(e) => onUpdateClinical('prep_extra', 'iv_fluid_detail', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: IV 16G & 18G, NaCl 0,9% 20 tpm"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edh_blood_prep">8. Persiapan Darah (jika diperlukan)</Label>
                            <Input
                                id="edh_blood_prep"
                                value={data.clinical_details.prep_extra.blood_prep}
                                onChange={(e) => onUpdateClinical('prep_extra', 'blood_prep', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: 2 kolf PRC crossmatch standby"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edh_antibiotic">9. Pemberian Antibiotik Sesuai Instruksi</Label>
                            <Input
                                id="edh_antibiotic"
                                value={data.clinical_details.prep_extra.antibiotic_instruction}
                                onChange={(e) => onUpdateClinical('prep_extra', 'antibiotic_instruction', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Ceftriaxone 2 g IV pre-insisi"
                            />
                        </div>

                        <div className="bg-muted/30 flex items-center space-x-2.5 rounded-xl border p-3.5">
                            <Checkbox
                                id="informed_consent_surgery"
                                checked={data.preparation.informed_consent_surgery}
                                onCheckedChange={(checked) => onTogglePrep('informed_consent_surgery', Boolean(checked))}
                                disabled={!canManage}
                            />
                            <Label htmlFor="informed_consent_surgery" className="cursor-pointer text-xs font-semibold">
                                10. Persetujuan Tindakan Operasi (Informed Consent) Lengkap
                            </Label>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edh_consent_signer">11a. Identitas Pemberi Persetujuan</Label>
                            <Input
                                id="edh_consent_signer"
                                value={data.clinical_details.prep_extra.consent_signer}
                                onChange={(e) => onUpdateClinical('prep_extra', 'consent_signer', e.target.value)}
                                disabled={!canManage}
                                placeholder="Nama lengkap penanda tangan"
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="edh_consent_relation">11b. Hubungan dengan Pasien</Label>
                            <Input
                                id="edh_consent_relation"
                                value={data.clinical_details.prep_extra.consent_relation}
                                onChange={(e) => onUpdateClinical('prep_extra', 'consent_relation', e.target.value)}
                                disabled={!canManage}
                                placeholder="Istri / Suami / Orang Tua"
                            />
                        </div>

                        <div className="space-y-1.5 md:col-span-2">
                            <Label htmlFor="edh_emergency_consent">
                                12. Dokumentasi Dasar Tindakan Darurat (bila persetujuan biasa tidak memungkinkan)
                            </Label>
                            <Input
                                id="edh_emergency_consent"
                                value={data.clinical_details.prep_extra.emergency_consent_doc}
                                onChange={(e) => onUpdateClinical('prep_extra', 'emergency_consent_doc', e.target.value)}
                                disabled={!canManage}
                                placeholder="Nomor berkas / dasar tindakan darurat penyelamatan nyawa"
                            />
                        </div>

                        <div className="bg-muted/30 flex items-center space-x-2.5 rounded-xl border p-3.5">
                            <Checkbox
                                id="site_marked"
                                checked={data.preparation.site_marked === true}
                                onCheckedChange={(checked) => onTogglePrep('site_marked', Boolean(checked))}
                                disabled={!canManage}
                            />
                            <Label htmlFor="site_marked" className="cursor-pointer text-xs font-semibold">
                                13. Penandaan Lokasi Operasi (Site Marking)
                            </Label>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 border-t pt-4 lg:grid-cols-2">
                        <div className="space-y-2 rounded-xl border p-3.5">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <Label htmlFor="edh_instrument_prep" className="flex items-center gap-1.5 text-xs font-semibold">
                                    <Wrench className="text-primary h-4 w-4" />
                                    14. Persiapan Alat dan Instrumen Bedah Saraf
                                </Label>
                                <Badge variant={prepProgress.instrument_checks_ready ? 'default' : 'outline'}>
                                    {prepProgress.instrument_checks_ready
                                        ? `${prepProgress.instrument_checks_count} Alat Terverifikasi`
                                        : `${prepProgress.instrument_checks_count} Alat Dicek`}
                                </Badge>
                            </div>
                            <Input
                                id="edh_instrument_prep"
                                value={data.clinical_details.prep_extra.implant_instrument_prep}
                                onChange={(e) => onUpdateClinical('prep_extra', 'implant_instrument_prep', e.target.value)}
                                disabled={!canManage}
                                placeholder="Set kraniotomi, cranial drill, perforator, craniotome, BHP siap"
                            />
                            <Button asChild variant="outline" size="sm" className="min-h-11">
                                <Link href={route('cases.preparation', caseHeader.id)}>Buka Cek Alat &amp; Instrumen (Tab Persiapan)</Link>
                            </Button>
                        </div>

                        <div className="flex flex-col justify-between gap-3 rounded-xl border p-3.5">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="flex items-center gap-1.5 text-xs font-semibold">
                                    <ClipboardCheck className="text-primary h-4 w-4" />
                                    15. Pelaksanaan Surgical Safety Checklist (Sign In)
                                </div>
                                <Badge variant={prepProgress.ssc_sign_in_status === 'selesai' ? 'default' : 'outline'}>
                                    {prepProgress.ssc_sign_in_label}
                                </Badge>
                            </div>
                            <p className="text-muted-foreground text-xs">
                                Pastikan verifikasi identitas, lokasi operasi, mesin anestesi, pulse oximeter, dan risiko di tab SSC Sign In.
                            </p>
                            <div>
                                <Button asChild variant="outline" size="sm" className="min-h-11">
                                    <Link href={route('cases.ssc.sign-in', caseHeader.id)}>Buka SSC Sign In</Link>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div className="bg-muted/20 flex flex-col gap-3 rounded-xl border p-3.5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <Label htmlFor="readiness_status" className="text-sm font-semibold">
                                Status Kesiapan Operasi <span className="text-destructive">*</span>
                            </Label>
                            <p className="text-muted-foreground text-xs">
                                Dinyatakan <strong>Siap</strong> apabila infus, persetujuan tindakan, dan penandaan lokasi operasi telah tercentang.
                            </p>
                        </div>
                        <div className="w-full sm:w-56">
                            <Select value={data.readiness_status} onValueChange={(val) => onSetField('readiness_status', val)} disabled={!canManage}>
                                <SelectTrigger id="readiness_status">
                                    <SelectValue placeholder="Pilih Kesiapan" />
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

            {/* G. Pengkajian Pra-Anestesi (8 Item SKPL) */}
            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-2 text-base font-semibold">
                        <Syringe className="text-primary h-5 w-5" />
                        G. Pengkajian Pra-Anestesi
                    </CardTitle>
                    <CardDescription>Evaluasi riwayat medis/anestesi, jalan napas, kardiopulmonal, ASA, dan rencana anestesi.</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div className="bg-muted/20 rounded-xl border p-3 text-xs">
                            <div className="text-muted-foreground font-medium">1. Riwayat Penyakit Pasien (Bagian B.9)</div>
                            <div className="text-foreground mt-1 font-medium">{data.past_illness_history || 'Belum diisi pada Bagian B.9'}</div>
                        </div>
                        <div className="bg-muted/20 rounded-xl border p-3 text-xs">
                            <div className="text-muted-foreground font-medium">2. Riwayat Alergi (Bagian B.11)</div>
                            <div className="text-foreground mt-1 font-medium">
                                {allergyLabel}
                                {data.allergy_status === 'ada' && data.allergy_detail ? ` (${data.allergy_detail})` : ''}
                            </div>
                        </div>
                        <div className="bg-muted/20 rounded-xl border p-3 text-xs">
                            <div className="text-muted-foreground font-medium">6. Berat Badan (Bagian F.5a)</div>
                            <div className="text-foreground mt-1 font-medium tabular-nums">
                                {data.vital_signs.weight_kg ? `${data.vital_signs.weight_kg} kg` : 'Belum diisi pada Bagian F.5'}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="anesthesia_history">3. Riwayat Anestesi Sebelumnya</Label>
                            <Input
                                id="anesthesia_history"
                                value={data.anesthesia_history}
                                onChange={(e) => onSetField('anesthesia_history', e.target.value)}
                                disabled={!canManage}
                                placeholder="Belum pernah / riwayat komplikasi anestesi"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_airway_exam">4. Pemeriksaan Jalan Napas</Label>
                            <Input
                                id="edh_airway_exam"
                                value={data.clinical_details.anesthesia_pre.airway_exam}
                                onChange={(e) => onUpdateClinical('anesthesia_pre', 'airway_exam', e.target.value)}
                                disabled={!canManage}
                                placeholder="Mallampati II, gerak leher bebas"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_cardiopulmonary">5. Kondisi Jantung dan Pernapasan</Label>
                            <Input
                                id="edh_cardiopulmonary"
                                value={data.clinical_details.anesthesia_pre.cardiopulmonary}
                                onChange={(e) => onUpdateClinical('anesthesia_pre', 'cardiopulmonary', e.target.value)}
                                disabled={!canManage}
                                placeholder="Bunyi jantung reguler, vesikuler +/+, ronki -/-"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_asa_class">7. Klasifikasi ASA</Label>
                            <Input
                                id="edh_asa_class"
                                value={data.clinical_details.anesthesia_pre.asa_class}
                                onChange={(e) => onUpdateClinical('anesthesia_pre', 'asa_class', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: ASA III E"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_anesthesia_type_id">8a. Rencana Jenis Anestesi (Master)</Label>
                            <Select
                                value={data.anesthesia_type_id}
                                onValueChange={(val) => onSetField('anesthesia_type_id', val)}
                                disabled={!canManage}
                            >
                                <SelectTrigger id="edh_anesthesia_type_id">
                                    <SelectValue placeholder="Pilih Jenis Anestesi" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.anesthesiaTypes.map((a) => (
                                        <SelectItem key={a.id} value={String(a.id)}>
                                            {a.name} ({a.code})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_planned_anesthesia">8b. Catatan Rencana Jenis Anestesi</Label>
                            <Input
                                id="edh_planned_anesthesia"
                                value={data.clinical_details.anesthesia_pre.planned_anesthesia}
                                onChange={(e) => onUpdateClinical('anesthesia_pre', 'planned_anesthesia', e.target.value)}
                                disabled={!canManage}
                                placeholder="contoh: Anestesi Umum (Intubasi ETT)"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* H. Pelaksanaan Anestesi (12 Item SKPL - tanpa airway_aid) */}
            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="flex items-center gap-2 text-base font-semibold">
                        <Syringe className="text-primary h-5 w-5" />
                        H. Pelaksanaan Anestesi
                    </CardTitle>
                    <CardDescription>Catatan anestesi, intubasi ETT, pengaturan ventilator, obat, cairan, TTV, dan komplikasi.</CardDescription>
                </CardHeader>
                <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_an_method">1. Jenis Anestesi</Label>
                        <Input
                            id="edh_an_method"
                            value={data.clinical_details.anesthesia_exec.anesthesia_method}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'anesthesia_method', e.target.value)}
                            disabled={!canManage}
                            placeholder="Anestesi Umum (GA)"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_an_start">2. Waktu Mulai Anestesi</Label>
                        <Input
                            id="edh_an_start"
                            type="time"
                            value={data.clinical_details.anesthesia_exec.anesthesia_start_time}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'anesthesia_start_time', e.target.value)}
                            disabled={!canManage}
                            className="min-h-11"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_an_end">3. Waktu Selesai Anestesi</Label>
                        <Input
                            id="edh_an_end"
                            type="time"
                            value={data.clinical_details.anesthesia_exec.anesthesia_end_time}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'anesthesia_end_time', e.target.value)}
                            disabled={!canManage}
                            className="min-h-11"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_ett_type">4. Jenis &amp; Ukuran ETT (jika dilakukan intubasi)</Label>
                        <Input
                            id="edh_ett_type"
                            value={data.clinical_details.anesthesia_exec.ett_type_size}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'ett_type_size', e.target.value)}
                            disabled={!canManage}
                            placeholder="Non-kinking ETT No. 7.5"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_ett_depth">5. Kedalaman Pemasangan ETT</Label>
                        <Input
                            id="edh_ett_depth"
                            value={data.clinical_details.anesthesia_exec.ett_depth_cm}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'ett_depth_cm', e.target.value)}
                            disabled={!canManage}
                            placeholder="21 cm di batas bibir"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_ventilator">6. Pengaturan Ventilator</Label>
                        <Input
                            id="edh_ventilator"
                            value={data.clinical_details.anesthesia_exec.ventilator_setting}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'ventilator_setting', e.target.value)}
                            disabled={!canManage}
                            placeholder="VCV TV 480 ml, RR 14, PEEP 5, FiO2 50%"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_med_name">7. Nama Obat Anestesi</Label>
                        <Input
                            id="edh_med_name"
                            value={data.clinical_details.anesthesia_exec.medications_summary}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'medications_summary', e.target.value)}
                            disabled={!canManage}
                            placeholder="Propofol, Fentanyl, Rocuronium, Sevoflurane"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_med_dose">8. Dosis Obat</Label>
                        <Input
                            id="edh_med_dose"
                            value={data.clinical_details.anesthesia_exec.medication_dose}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'medication_dose', e.target.value)}
                            disabled={!canManage}
                            placeholder="Propofol 120 mg, Fentanyl 100 mcg"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_med_time">9. Waktu Pemberian Obat</Label>
                        <Input
                            id="edh_med_time"
                            type="time"
                            value={data.clinical_details.anesthesia_exec.medication_time}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'medication_time', e.target.value)}
                            disabled={!canManage}
                            className="min-h-11"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_an_fluids">10. Cairan yang Diberikan</Label>
                        <Input
                            id="edh_an_fluids"
                            value={data.clinical_details.anesthesia_exec.fluids_given}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'fluids_given', e.target.value)}
                            disabled={!canManage}
                            placeholder="NaCl 0,9% 1000 ml"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_vitals_note">11. Pemantauan TD, Nadi, Pernapasan, Suhu &amp; SpO₂</Label>
                        <Input
                            id="edh_vitals_note"
                            value={data.clinical_details.anesthesia_exec.vitals_monitoring_note}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'vitals_monitoring_note', e.target.value)}
                            disabled={!canManage}
                            placeholder="Hemodinamik stabil selama pembiusan"
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="edh_an_comp">12. Komplikasi Anestesi (jika ada)</Label>
                        <Input
                            id="edh_an_comp"
                            value={data.clinical_details.anesthesia_exec.complications}
                            onChange={(e) => onUpdateClinical('anesthesia_exec', 'complications', e.target.value)}
                            disabled={!canManage}
                            placeholder="Tidak ada komplikasi anestesi"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
