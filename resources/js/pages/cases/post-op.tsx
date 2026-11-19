import { VitalSignChart, type VitalSignPoint } from '@/components/cases/vital-sign-chart';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import CaseLayout, { type CaseHeaderData } from '@/layouts/case-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Activity, ArrowRight, CheckCircle2, ClipboardCheck, ListChecks, Pencil, Plus, Save, ShieldCheck, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type OptionItem = { value: string; label: string };
type MasterDiagnosis = { id: number; code: string; name: string };

type ObservationRow = {
    id: number;
    recorded_at: string;
    recorded_at_full: string;
    gcs_total: number;
    gcs_detail: string;
    general_condition: string;
    response: string;
    airway_patency: string;
    wound_summary: string;
    pain_nrs: number;
    pain_detail: string;
    anesthesia_recovery: string;
    nausea_vomiting: boolean;
    shivering_hypothermia: boolean;
    complications: string[];
    notes: string | null;
    creator_name: string;
};

type PostVitalRow = VitalSignPoint & {
    recorded_at_full: string;
    respiratory_rate: number | null;
    breathing_pattern: string;
    oxygen_device: string;
    oxygen_flow_lpm: number | null;
};

type PostFluidRow = {
    id: number;
    recorded_at: string;
    kind_label: string;
    volume_ml: number;
    color: string | null;
    odor: string | null;
};

type CaseDiagnosisItem = {
    id: number;
    name: string;
    code: string | null;
    slki: Array<{ id: number; outcome_name: string; criterion_text: string; expectation: string; target_score: number }>;
    siki: Array<{
        id: number;
        name: string;
        items: Array<{
            id: number | null;
            siki_item_id: number;
            section_label: string;
            text: string;
            is_done: boolean;
            done_at: string | null;
        }>;
    }>;
};

interface PostopPageProps {
    caseHeader: CaseHeaderData;
    operationPlanActual: string;
    observations: ObservationRow[];
    vitalSigns: PostVitalRow[];
    postFluids: PostFluidRow[];
    summary: {
        ready_to_transfer: boolean;
        pre_transfer_condition_notes: string;
        assessed_at: string;
        assessed_by_name: string | null;
    };
    caseNursingDiagnoses: CaseDiagnosisItem[];
    verificationState: {
        activeVerifications: Array<{ id: number; user_name: string; sip: string | null; signed_at: string }>;
        revokedVerifications: Array<{ id: number; user_name: string; signed_at: string; revoked_at: string | null; revoke_reason: string | null }>;
    };
    options: {
        generalConditions: OptionItem[];
        patientResponses: OptionItem[];
        airwayPatencies: OptionItem[];
        breathingPatterns: OptionItem[];
        oxygenDevices: OptionItem[];
        dressingConditions: OptionItem[];
        sutureConditions: OptionItem[];
        painCharacters: OptionItem[];
        analgesicResponses: OptionItem[];
        consciousnessList: OptionItem[];
        fluidColors: OptionItem[];
        fluidOdors: OptionItem[];
        masterNursingDiagnoses: MasterDiagnosis[];
    };
    permissions: {
        canEdit: boolean;
        canVerify: boolean;
        canDoctorCorrect: boolean;
        canAdvance: boolean;
    };
}

function nowWibLocalInput(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
}

const COMPLICATION_LABELS: Array<{ key: string; label: string }> = [
    { key: 'perdarahan', label: 'Perdarahan' },
    { key: 'hematoma', label: 'Hematoma' },
    { key: 'infeksi_luka', label: 'Infeksi Luka' },
    { key: 'gangguan_napas', label: 'Gangguan Napas' },
    { key: 'hipo_hipertensi', label: 'Hipo / Hipertensi' },
    { key: 'mual_muntah', label: 'Mual & Muntah' },
    { key: 'alergi_obat', label: 'Alergi Obat' },
];

export default function PostopPage({
    caseHeader,
    operationPlanActual,
    observations,
    vitalSigns,
    postFluids,
    summary,
    caseNursingDiagnoses,
    verificationState,
    options,
    permissions,
}: PostopPageProps) {
    const [showNewForm, setShowNewForm] = useState(observations.length === 0);
    const [verifyOpen, setVerifyOpen] = useState(false);
    const [correctOpen, setCorrectOpen] = useState(false);
    const [advancing, setAdvancing] = useState(false);

    const obsForm = useForm({
        recorded_at: nowWibLocalInput(),
        gcs_eye: '4',
        gcs_verbal: '5',
        gcs_motor: '6',
        general_condition: 'baik',
        response: 'stabil',
        airway_patency: 'paten',
        bp_systolic: '',
        bp_diastolic: '',
        pulse: '',
        temperature: '',
        respiratory_rate: '',
        spo2: '',
        breathing_pattern: 'eupnea',
        oxygen_device: 'tanpa_oksigen',
        oxygen_flow_lpm: '',
        wound_location: caseHeader.surgery_site !== '-' ? caseHeader.surgery_site : '',
        dressing: 'bersih',
        bleeding_seepage: false as boolean,
        swelling_hematoma: false as boolean,
        suture_condition: 'rapi_utuh',
        pain_nrs: '0',
        pain_location: '',
        pain_character: '',
        pain_duration: '',
        analgesic_response: '',
        fluid_intake_ml: '',
        urine_ml: '',
        drain_ml: '',
        drain_color: '',
        drain_odor: '',
        bleeding_ml: '',
        anesthesia_recovery: 'compos_mentis',
        nausea_vomiting: false as boolean,
        shivering_hypothermia: false as boolean,
        complications: [] as string[],
        notes: '',
    });

    const summaryForm = useForm({
        ready_to_transfer: summary.ready_to_transfer,
        pre_transfer_condition_notes: summary.pre_transfer_condition_notes,
        assessed_at: summary.assessed_at,
    });

    const diagForm = useForm({
        nursing_diagnosis_id: '',
        custom_text: '',
        notes: '',
    });

    const verifyForm = useForm({ password: '' });

    const correctForm = useForm({
        pre_transfer_condition_notes: summary.pre_transfer_condition_notes,
        operation_plan_actual: operationPlanActual,
        reason: '',
    });

    const gcsTotalLive = (Number(obsForm.data.gcs_eye) || 0) + (Number(obsForm.data.gcs_verbal) || 0) + (Number(obsForm.data.gcs_motor) || 0);

    const toggleComplication = (key: string) => {
        const list = obsForm.data.complications;
        obsForm.setData('complications', list.includes(key) ? list.filter((i) => i !== key) : [...list, key]);
    };

    const submitObservation = (e: FormEvent) => {
        e.preventDefault();
        obsForm.post(route('cases.postop.observations.store', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                obsForm.reset(
                    'bp_systolic',
                    'bp_diastolic',
                    'pulse',
                    'temperature',
                    'respiratory_rate',
                    'spo2',
                    'oxygen_flow_lpm',
                    'fluid_intake_ml',
                    'urine_ml',
                    'drain_ml',
                    'bleeding_ml',
                    'notes',
                );
                obsForm.setData('recorded_at', nowWibLocalInput());
                setShowNewForm(false);
            },
        });
    };

    const submitSummary = (e: FormEvent) => {
        e.preventDefault();
        summaryForm.put(route('cases.postop.summary', caseHeader.id), { preserveScroll: true });
    };

    const submitDiagnosis = (e: FormEvent) => {
        e.preventDefault();
        diagForm.post(route('cases.postop.add-diagnosis', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => diagForm.reset(),
        });
    };

    const submitVerify = (e: FormEvent) => {
        e.preventDefault();
        verifyForm.post(route('cases.postop.verify', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                verifyForm.reset();
                setVerifyOpen(false);
            },
        });
    };

    const submitCorrection = (e: FormEvent) => {
        e.preventDefault();
        correctForm.post(route('cases.postop.doctor-correction', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                correctForm.setData('reason', '');
                setCorrectOpen(false);
            },
        });
    };

    const handleAdvanceToHandover = () => {
        if (!caseHeader.next_transition) {
            return;
        }
        setAdvancing(true);
        router.post(
            route('cases.advance-status', caseHeader.id),
            { target_status: caseHeader.next_transition.target },
            {
                preserveScroll: true,
                onFinish: () => setAdvancing(false),
            },
        );
    };

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="pasca-op">
            <Head title={`Pasca-Operasi - ${caseHeader.case_number}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                {/* Banner terkunci setelah selesai */}
                {caseHeader.status === 'selesai' && (
                    <Alert>
                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        <AlertTitle>Kasus Berstatus Selesai - Edit Perawat Terkunci</AlertTitle>
                        <AlertDescription className="flex flex-wrap items-center justify-between gap-2">
                            <span>
                                Data keperawatan sudah dikunci. Hanya dokter operator atau dokter anestesi kasus ini yang dapat melakukan koreksi
                                medis dengan alasan wajib tercatat di audit log.
                            </span>
                            {permissions.canDoctorCorrect && (
                                <Button type="button" size="sm" variant="outline" onClick={() => setCorrectOpen(true)}>
                                    <Pencil className="mr-1.5 h-3.5 w-3.5" />
                                    Koreksi Dokter (Wajib Alasan)
                                </Button>
                            )}
                        </AlertDescription>
                    </Alert>
                )}

                {/* Grafik Tren TTV Pasca-Op */}
                <VitalSignChart
                    data={vitalSigns}
                    title="Grafik Tren TTV Pasca-Operasi"
                    description="Pemantauan tekanan darah, nadi, SpO₂, dan suhu selama pemulihan."
                />

                {/* Daftar Observasi Pasca-Op */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Activity className="text-primary h-5 w-5" />
                                    A. Daftar Observasi Pasca-Operasi Berulang (KF-13..16 & KF-36)
                                </CardTitle>
                                <CardDescription>
                                    Catat pemantauan kesadaran (GCS), TTV, pernapasan, nyeri NRS, luka, cairan, dan komplikasi.
                                </CardDescription>
                            </div>
                            {permissions.canEdit && (
                                <Button type="button" className="min-h-11" onClick={() => setShowNewForm((prev) => !prev)}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    {showNewForm ? 'Tutup Form Observasi' : 'Observasi Baru'}
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Form Observasi Baru */}
                        {permissions.canEdit && showNewForm && (
                            <form onSubmit={submitObservation} className="bg-muted/20 space-y-5 rounded-lg border p-4">
                                <div className="text-foreground text-sm font-semibold">Tambah Observasi Pasca-Operasi Baru</div>

                                {/* 1. Kondisi Umum & GCS */}
                                <div className="grid grid-cols-2 gap-3 md:grid-cols-6">
                                    <div className="col-span-2">
                                        <Label className="text-xs">Waktu Observasi *</Label>
                                        <Input
                                            type="datetime-local"
                                            value={obsForm.data.recorded_at}
                                            onChange={(e) => obsForm.setData('recorded_at', e.target.value)}
                                            className="min-h-10 text-xs"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">GCS Eye (1–4) *</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={4}
                                            value={obsForm.data.gcs_eye}
                                            onChange={(e) => obsForm.setData('gcs_eye', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">GCS Verbal (1–5) *</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={5}
                                            value={obsForm.data.gcs_verbal}
                                            onChange={(e) => obsForm.setData('gcs_verbal', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">GCS Motor (1–6) *</Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={6}
                                            value={obsForm.data.gcs_motor}
                                            onChange={(e) => obsForm.setData('gcs_motor', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div className="flex flex-col justify-end">
                                        <Badge variant="secondary" className="min-h-10 justify-center text-sm font-bold">
                                            Total GCS: {gcsTotalLive}
                                        </Badge>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <div>
                                        <Label className="text-xs">Kondisi Umum *</Label>
                                        <Select value={obsForm.data.general_condition} onValueChange={(v) => obsForm.setData('general_condition', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.generalConditions.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="text-xs">Respons Pasien *</Label>
                                        <Select value={obsForm.data.response} onValueChange={(v) => obsForm.setData('response', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.patientResponses.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="text-xs">Kepatenan Jalan Napas *</Label>
                                        <Select value={obsForm.data.airway_patency} onValueChange={(v) => obsForm.setData('airway_patency', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.airwayPatencies.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {/* 2. TTV & Oksigen */}
                                <div className="grid grid-cols-2 gap-3 border-t pt-3 md:grid-cols-6">
                                    <div>
                                        <Label className="text-xs">TD Sistolik</Label>
                                        <Input
                                            type="number"
                                            value={obsForm.data.bp_systolic}
                                            onChange={(e) => obsForm.setData('bp_systolic', e.target.value)}
                                            placeholder="120"
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">TD Diastolik</Label>
                                        <Input
                                            type="number"
                                            value={obsForm.data.bp_diastolic}
                                            onChange={(e) => obsForm.setData('bp_diastolic', e.target.value)}
                                            placeholder="80"
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Nadi</Label>
                                        <Input
                                            type="number"
                                            value={obsForm.data.pulse}
                                            onChange={(e) => obsForm.setData('pulse', e.target.value)}
                                            placeholder="80"
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Suhu (°C)</Label>
                                        <Input
                                            type="number"
                                            step="0.1"
                                            value={obsForm.data.temperature}
                                            onChange={(e) => obsForm.setData('temperature', e.target.value)}
                                            placeholder="36.6"
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">RR (x/mnt)</Label>
                                        <Input
                                            type="number"
                                            value={obsForm.data.respiratory_rate}
                                            onChange={(e) => obsForm.setData('respiratory_rate', e.target.value)}
                                            placeholder="18"
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">SpO₂ (%)</Label>
                                        <Input
                                            type="number"
                                            value={obsForm.data.spo2}
                                            onChange={(e) => obsForm.setData('spo2', e.target.value)}
                                            placeholder="99"
                                            className="min-h-10"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <div>
                                        <Label className="text-xs">Pola Pernapasan *</Label>
                                        <Select value={obsForm.data.breathing_pattern} onValueChange={(v) => obsForm.setData('breathing_pattern', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.breathingPatterns.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="text-xs">Penggunaan Oksigen *</Label>
                                        <Select value={obsForm.data.oxygen_device} onValueChange={(v) => obsForm.setData('oxygen_device', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.oxygenDevices.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="text-xs">
                                            Aliran O₂ (1–15 LPM) {obsForm.data.oxygen_device !== 'tanpa_oksigen' ? '*' : ''}
                                        </Label>
                                        <Input
                                            type="number"
                                            min={1}
                                            max={15}
                                            disabled={obsForm.data.oxygen_device === 'tanpa_oksigen'}
                                            value={obsForm.data.oxygen_flow_lpm}
                                            onChange={(e) => obsForm.setData('oxygen_flow_lpm', e.target.value)}
                                            className="min-h-10"
                                        />
                                        <InputError message={obsForm.errors.oxygen_flow_lpm} />
                                    </div>
                                </div>

                                {/* 3. Nyeri NRS & 4. Luka */}
                                <div className="grid grid-cols-1 gap-3 border-t pt-3 md:grid-cols-5">
                                    <div>
                                        <Label className="text-xs">Skala Nyeri (NRS 0–10) *</Label>
                                        <Input
                                            type="number"
                                            min={0}
                                            max={10}
                                            value={obsForm.data.pain_nrs}
                                            onChange={(e) => obsForm.setData('pain_nrs', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Lokasi Nyeri</Label>
                                        <Input
                                            value={obsForm.data.pain_location}
                                            onChange={(e) => obsForm.setData('pain_location', e.target.value)}
                                            placeholder="Area insisi"
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Karakteristik Nyeri</Label>
                                        <Select value={obsForm.data.pain_character} onValueChange={(v) => obsForm.setData('pain_character', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue placeholder="Pilih" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.painCharacters.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="text-xs">Durasi Nyeri</Label>
                                        <Input
                                            value={obsForm.data.pain_duration}
                                            onChange={(e) => obsForm.setData('pain_duration', e.target.value)}
                                            placeholder="Intermiten / menetap"
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Respons Antinyeri</Label>
                                        <Select
                                            value={obsForm.data.analgesic_response}
                                            onValueChange={(v) => obsForm.setData('analgesic_response', v)}
                                        >
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue placeholder="Pilih" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.analgesicResponses.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-3 border-t pt-3 md:grid-cols-5">
                                    <div className="md:col-span-2">
                                        <Label className="text-xs">Lokasi Luka Operasi</Label>
                                        <Input
                                            value={obsForm.data.wound_location}
                                            onChange={(e) => obsForm.setData('wound_location', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Kondisi Balutan</Label>
                                        <Select value={obsForm.data.dressing} onValueChange={(v) => obsForm.setData('dressing', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.dressingConditions.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="text-xs">Kondisi Jahitan</Label>
                                        <Select value={obsForm.data.suture_condition} onValueChange={(v) => obsForm.setData('suture_condition', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.sutureConditions.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex flex-col justify-center gap-1.5 pt-3 text-xs">
                                        <label className="flex cursor-pointer items-center gap-2">
                                            <Checkbox
                                                checked={obsForm.data.bleeding_seepage}
                                                onCheckedChange={(c) => obsForm.setData('bleeding_seepage', c === true)}
                                            />
                                            <span>Perdarahan / Rembesan</span>
                                        </label>
                                        <label className="flex cursor-pointer items-center gap-2">
                                            <Checkbox
                                                checked={obsForm.data.swelling_hematoma}
                                                onCheckedChange={(c) => obsForm.setData('swelling_hematoma', c === true)}
                                            />
                                            <span>Bengkak / Hematoma</span>
                                        </label>
                                    </div>
                                </div>

                                {/* 5. Cairan & Drain */}
                                <div className="grid grid-cols-2 gap-3 border-t pt-3 md:grid-cols-6">
                                    <div>
                                        <Label className="text-xs">Cairan Masuk (ml)</Label>
                                        <Input
                                            type="number"
                                            min={0}
                                            value={obsForm.data.fluid_intake_ml}
                                            onChange={(e) => obsForm.setData('fluid_intake_ml', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Urine (ml)</Label>
                                        <Input
                                            type="number"
                                            min={0}
                                            value={obsForm.data.urine_ml}
                                            onChange={(e) => obsForm.setData('urine_ml', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Perdarahan (ml)</Label>
                                        <Input
                                            type="number"
                                            min={0}
                                            value={obsForm.data.bleeding_ml}
                                            onChange={(e) => obsForm.setData('bleeding_ml', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Cairan Drain (ml)</Label>
                                        <Input
                                            type="number"
                                            min={0}
                                            value={obsForm.data.drain_ml}
                                            onChange={(e) => obsForm.setData('drain_ml', e.target.value)}
                                            className="min-h-10"
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs">Warna Drain</Label>
                                        <Select value={obsForm.data.drain_color} onValueChange={(v) => obsForm.setData('drain_color', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue placeholder="Pilih" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.fluidColors.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label className="text-xs">Bau Drain</Label>
                                        <Select value={obsForm.data.drain_odor} onValueChange={(v) => obsForm.setData('drain_odor', v)}>
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue placeholder="Pilih" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.fluidOdors.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {/* 6. Efek Anestesi & Komplikasi */}
                                <div className="grid grid-cols-1 gap-3 border-t pt-3 md:grid-cols-3">
                                    <div>
                                        <Label className="text-xs">Pemulihan Kesadaran Anestesi</Label>
                                        <Select
                                            value={obsForm.data.anesthesia_recovery}
                                            onValueChange={(v) => obsForm.setData('anesthesia_recovery', v)}
                                        >
                                            <SelectTrigger className="min-h-10">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.consciousnessList.map((o) => (
                                                    <SelectItem key={o.value} value={o.value}>
                                                        {o.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-4 pt-4 text-xs">
                                        <label className="flex cursor-pointer items-center gap-2">
                                            <Checkbox
                                                checked={obsForm.data.nausea_vomiting}
                                                onCheckedChange={(c) => obsForm.setData('nausea_vomiting', c === true)}
                                            />
                                            <span>Mual / Muntah</span>
                                        </label>
                                        <label className="flex cursor-pointer items-center gap-2">
                                            <Checkbox
                                                checked={obsForm.data.shivering_hypothermia}
                                                onCheckedChange={(c) => obsForm.setData('shivering_hypothermia', c === true)}
                                            />
                                            <span>Menggigil / Hipotermia</span>
                                        </label>
                                    </div>
                                    <div>
                                        <Label className="text-xs">Catatan Observasi</Label>
                                        <Input
                                            value={obsForm.data.notes}
                                            onChange={(e) => obsForm.setData('notes', e.target.value)}
                                            placeholder="Catatan tambahan"
                                            className="min-h-10"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Komplikasi Pasca-Operasi (centang bila ada)</Label>
                                    <div className="flex flex-wrap gap-3">
                                        {COMPLICATION_LABELS.map((c) => (
                                            <label key={c.key} className="flex cursor-pointer items-center gap-2 text-xs">
                                                <Checkbox
                                                    checked={obsForm.data.complications.includes(c.key)}
                                                    onCheckedChange={() => toggleComplication(c.key)}
                                                />
                                                <span>{c.label}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="flex justify-end">
                                    <Button type="submit" className="min-h-11" disabled={obsForm.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {obsForm.processing ? 'Menyimpan...' : 'Simpan Observasi Pasca-Op'}
                                    </Button>
                                </div>
                            </form>
                        )}

                        {/* Tabel Observasi */}
                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>GCS & Kondisi</TableHead>
                                        <TableHead>Jalan Napas</TableHead>
                                        <TableHead>Nyeri (NRS)</TableHead>
                                        <TableHead>Luka & Balutan</TableHead>
                                        <TableHead>Anestesi & Komplikasi</TableHead>
                                        {permissions.canEdit && <TableHead className="w-[50px]" />}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {observations.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-muted-foreground py-6 text-center text-sm">
                                                Belum ada observasi pasca-operasi tercatat.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        observations.map((o) => (
                                            <TableRow key={o.id}>
                                                <TableCell className="font-mono text-xs">{o.recorded_at}</TableCell>
                                                <TableCell className="text-xs">
                                                    <div className="font-semibold">
                                                        GCS {o.gcs_total} ({o.gcs_detail})
                                                    </div>
                                                    <div className="text-muted-foreground">
                                                        {o.general_condition} • {o.response}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-xs">{o.airway_patency}</TableCell>
                                                <TableCell className="text-xs">
                                                    <Badge variant={o.pain_nrs >= 7 ? 'destructive' : 'outline'}>NRS {o.pain_nrs}/10</Badge>
                                                    <div className="text-muted-foreground mt-0.5">{o.pain_detail}</div>
                                                </TableCell>
                                                <TableCell className="text-xs">{o.wound_summary}</TableCell>
                                                <TableCell className="text-xs">
                                                    <div>{o.anesthesia_recovery}</div>
                                                    {(o.nausea_vomiting || o.shivering_hypothermia || o.complications.length > 0) && (
                                                        <div className="mt-0.5 text-amber-700">
                                                            {[
                                                                o.nausea_vomiting ? 'Mual/muntah' : null,
                                                                o.shivering_hypothermia ? 'Menggigil' : null,
                                                                ...o.complications,
                                                            ]
                                                                .filter(Boolean)
                                                                .join(', ')}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                {permissions.canEdit && (
                                                    <TableCell>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() =>
                                                                router.delete(route('cases.postop.observations.destroy', [caseHeader.id, o.id]), {
                                                                    preserveScroll: true,
                                                                })
                                                            }
                                                        >
                                                            <Trash2 className="text-destructive h-4 w-4" />
                                                        </Button>
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {postFluids.length > 0 && (
                            <div className="text-muted-foreground text-xs">
                                <strong>Cairan & Drain Pasca-Op:</strong>{' '}
                                {postFluids
                                    .map(
                                        (f) =>
                                            `${f.recorded_at} ${f.kind_label} ${f.volume_ml} ml${f.color ? ` (${f.color}${f.odor ? `, ${f.odor}` : ''})` : ''}`,
                                    )
                                    .join(' • ')}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* B. Diagnosis Keperawatan Pasca-Op + SLKI/SIKI */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ListChecks className="text-primary h-5 w-5" />
                            B. Diagnosis Keperawatan Pasca-Operasi + SLKI / SIKI (10 Master SDKI)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {permissions.canEdit && (
                            <form onSubmit={submitDiagnosis} className="grid grid-cols-1 gap-3 rounded-lg border p-3 md:grid-cols-3">
                                <Select value={diagForm.data.nursing_diagnosis_id} onValueChange={(v) => diagForm.setData('nursing_diagnosis_id', v)}>
                                    <SelectTrigger className="min-h-11">
                                        <SelectValue placeholder="Pilih diagnosis pasca-op (master)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.masterNursingDiagnoses.map((d) => (
                                            <SelectItem key={d.id} value={String(d.id)}>
                                                {d.code ? `${d.code} - ` : ''}
                                                {d.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Input
                                    value={diagForm.data.custom_text}
                                    onChange={(e) => diagForm.setData('custom_text', e.target.value)}
                                    placeholder="Atau teks diagnosis lainnya"
                                    className="min-h-11"
                                />
                                <Button type="submit" className="min-h-11" disabled={diagForm.processing}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Tambah Diagnosis
                                </Button>
                            </form>
                        )}

                        {caseNursingDiagnoses.length === 0 ? (
                            <p className="text-muted-foreground text-center text-sm">Belum ada diagnosis keperawatan pasca-op ditambahkan.</p>
                        ) : (
                            <div className="space-y-3">
                                {caseNursingDiagnoses.map((cd) => (
                                    <div key={cd.id} className="space-y-2 rounded-lg border p-4">
                                        <div className="flex items-center justify-between gap-2">
                                            <div className="font-semibold">
                                                {cd.code ? `${cd.code} - ` : ''}
                                                {cd.name}
                                            </div>
                                            {permissions.canEdit && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.delete(route('cases.postop.remove-diagnosis', [caseHeader.id, cd.id]), {
                                                            preserveScroll: true,
                                                        })
                                                    }
                                                >
                                                    <Trash2 className="text-destructive h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                        {cd.slki.length > 0 && (
                                            <div className="text-muted-foreground text-xs">
                                                <strong>Luaran SLKI:</strong>{' '}
                                                {cd.slki.map((c) => `${c.criterion_text} (${c.expectation}, target ${c.target_score})`).join(' • ')}
                                            </div>
                                        )}
                                        {cd.siki.map((int) => (
                                            <div key={int.id} className="space-y-1 pt-1">
                                                <div className="text-xs font-semibold">{int.name}</div>
                                                <div className="grid grid-cols-1 gap-1.5 md:grid-cols-2">
                                                    {int.items.map((item) => (
                                                        <label
                                                            key={item.siki_item_id}
                                                            className="hover:bg-muted/40 flex cursor-pointer items-start gap-2 rounded border px-2.5 py-1.5 text-xs"
                                                        >
                                                            <Checkbox
                                                                checked={item.is_done}
                                                                disabled={!permissions.canEdit || item.id === null}
                                                                onCheckedChange={() => {
                                                                    if (item.id !== null) {
                                                                        router.post(
                                                                            route('cases.postop.toggle-intervention', [caseHeader.id, item.id]),
                                                                            {},
                                                                            { preserveScroll: true },
                                                                        );
                                                                    }
                                                                }}
                                                            />
                                                            <span>
                                                                <Badge variant="outline" className="mr-1 text-[10px]">
                                                                    {item.section_label}
                                                                </Badge>
                                                                {item.text}
                                                                {item.done_at && (
                                                                    <span className="ml-1 font-medium text-emerald-700">({item.done_at})</span>
                                                                )}
                                                            </span>
                                                        </label>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* C. Kondisi Pasien Sebelum Dipindahkan (KF-17) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ClipboardCheck className="text-primary h-5 w-5" />
                            C. Kondisi Pasien Sebelum Dipindahkan (Wajib Sebelum Serah Terima - KF-17)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitSummary} className="space-y-4">
                            <label className="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm font-medium">
                                <Checkbox
                                    checked={summaryForm.data.ready_to_transfer}
                                    disabled={!permissions.canEdit}
                                    onCheckedChange={(c) => summaryForm.setData('ready_to_transfer', c === true)}
                                />
                                <span>Pasien dinyatakan layak dipindahkan dari Kamar Operasi / Ruang Pemulihan</span>
                            </label>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="space-y-1.5 md:col-span-2">
                                    <Label htmlFor="pre_transfer_condition_notes">Catatan Kondisi Sebelum Dipindahkan *</Label>
                                    <textarea
                                        id="pre_transfer_condition_notes"
                                        rows={3}
                                        value={summaryForm.data.pre_transfer_condition_notes}
                                        onChange={(e) => summaryForm.setData('pre_transfer_condition_notes', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        placeholder="Ringkasan kesadaran, jalan napas, TTV stabil, balutan bersih, dan kelayakan pindah..."
                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                    />
                                    <InputError message={summaryForm.errors.pre_transfer_condition_notes} />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="assessed_at">Waktu Penilaian Kelayakan</Label>
                                    <Input
                                        id="assessed_at"
                                        type="datetime-local"
                                        value={summaryForm.data.assessed_at}
                                        onChange={(e) => summaryForm.setData('assessed_at', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        className="min-h-11"
                                    />
                                    {summary.assessed_by_name && (
                                        <p className="text-muted-foreground text-xs">Dinilai oleh: {summary.assessed_by_name}</p>
                                    )}
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center justify-between gap-2 pt-2">
                                <div className="flex flex-wrap gap-2">
                                    <Button asChild variant="outline" className="min-h-11">
                                        <Link href={route('cases.handover', caseHeader.id)}>Buka Tab Serah Terima</Link>
                                    </Button>
                                    {permissions.canAdvance && caseHeader.status === 'pasca_op' && caseHeader.next_transition && (
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            className="min-h-11"
                                            disabled={!caseHeader.next_transition.can_advance || advancing}
                                            onClick={handleAdvanceToHandover}
                                        >
                                            Lanjut ke {caseHeader.next_transition.target_label}
                                            <ArrowRight className="ml-2 h-4 w-4" />
                                        </Button>
                                    )}
                                </div>

                                {permissions.canEdit && (
                                    <Button type="submit" className="min-h-11" disabled={summaryForm.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        Simpan Kondisi Sebelum Pindah
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* D. Verifikasi & Koreksi Dokter */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ShieldCheck className="text-primary h-5 w-5" />
                                    D. Verifikasi & Koreksi Dokter (Pasca-Operasi - KF-38)
                                </CardTitle>
                                <CardDescription>
                                    Dokter kasus dapat memverifikasi pasca-op atau melakukan koreksi medis (dengan alasan wajib, termasuk setelah
                                    kasus Selesai).
                                </CardDescription>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {permissions.canVerify && (
                                    <Button type="button" variant="outline" className="min-h-11" onClick={() => setVerifyOpen(true)}>
                                        <ShieldCheck className="mr-2 h-4 w-4" />
                                        Verifikasi Pasca-Op
                                    </Button>
                                )}
                                {permissions.canDoctorCorrect && (
                                    <Button type="button" variant="secondary" className="min-h-11" onClick={() => setCorrectOpen(true)}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Koreksi Dokter
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {verificationState.activeVerifications.length === 0 ? (
                            <p className="text-muted-foreground rounded-lg border border-dashed p-4 text-sm">
                                Belum ada verifikasi dokter aktif untuk bagian pasca-operasi.
                            </p>
                        ) : (
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                {verificationState.activeVerifications.map((v) => (
                                    <div
                                        key={v.id}
                                        className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-sm dark:border-emerald-800 dark:bg-emerald-950/30"
                                    >
                                        <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                                        <div>
                                            <div className="font-semibold">{v.user_name}</div>
                                            <div className="text-xs opacity-80">
                                                SIP: {v.sip ?? '-'} • {v.signed_at} WIB
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}

                        {verificationState.revokedVerifications.length > 0 && (
                            <div className="space-y-1 pt-2">
                                <div className="text-muted-foreground text-xs font-semibold">Riwayat Verifikasi yang Dibatalkan:</div>
                                {verificationState.revokedVerifications.map((rev) => (
                                    <div key={rev.id} className="rounded border border-amber-200 bg-amber-50/60 p-2 text-xs">
                                        {rev.user_name} ({rev.signed_at}) dibatalkan pada {rev.revoked_at}: <em>{rev.revoke_reason}</em>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Dialog Verifikasi Dokter */}
            <Dialog open={verifyOpen} onOpenChange={setVerifyOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Verifikasi Pasca-Operasi</DialogTitle>
                        <DialogDescription>Masukkan kata sandi akun dokter Anda untuk mengonfirmasi verifikasi pasca-operasi.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitVerify} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="postop_verify_password">Kata Sandi</Label>
                            <Input
                                id="postop_verify_password"
                                type="password"
                                value={verifyForm.data.password}
                                onChange={(e) => verifyForm.setData('password', e.target.value)}
                                className="min-h-11"
                            />
                            <InputError message={verifyForm.errors.password} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setVerifyOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={verifyForm.processing}>
                                Konfirmasi Verifikasi
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Dialog Koreksi Dokter (Wajib Alasan, Berlaku Pasca-Selesai) */}
            <Dialog open={correctOpen} onOpenChange={setCorrectOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Koreksi Medis oleh Dokter</DialogTitle>
                        <DialogDescription>
                            Koreksi dokter tercatat penuh pada Audit Log beserta alasan perubahan. Wajib mengisi alasan minimal 5 karakter.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitCorrection} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="correct_plan">Tindakan Operasi Aktual (Opsional)</Label>
                            <Input
                                id="correct_plan"
                                value={correctForm.data.operation_plan_actual}
                                onChange={(e) => correctForm.setData('operation_plan_actual', e.target.value)}
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="correct_notes">Catatan Kondisi Pasca-Operasi / Sebelum Pindah *</Label>
                            <textarea
                                id="correct_notes"
                                rows={3}
                                value={correctForm.data.pre_transfer_condition_notes}
                                onChange={(e) => correctForm.setData('pre_transfer_condition_notes', e.target.value)}
                                className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                            />
                            <InputError message={correctForm.errors.pre_transfer_condition_notes} />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="correct_reason">Alasan Koreksi Dokter * (Wajib, min. 5 karakter)</Label>
                            <Input
                                id="correct_reason"
                                value={correctForm.data.reason}
                                onChange={(e) => correctForm.setData('reason', e.target.value)}
                                placeholder="mis. Klarifikasi hasil observasi hemodinamik dan instruksi obat pulang"
                                className="min-h-11"
                            />
                            <InputError message={correctForm.errors.reason} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setCorrectOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={correctForm.processing}>
                                {correctForm.processing ? 'Menyimpan...' : 'Simpan Koreksi Dokter'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </CaseLayout>
    );
}
