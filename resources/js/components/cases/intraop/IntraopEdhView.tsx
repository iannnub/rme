import { type IntraopProcedureDetails } from '@/components/cases/intraop/IntraopProcedureDetailsCard';
import { VitalSignChart, type VitalSignPoint } from '@/components/cases/vital-sign-chart';
import { ConcurrencyAlert } from '@/components/concurrency-alert';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type CaseHeaderData } from '@/layouts/case-layout';
import { Link, router, useForm } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    Brain,
    ClipboardList,
    Clock,
    Droplets,
    ListChecks,
    Plus,
    Save,
    ShieldCheck,
    Stethoscope,
    Trash2,
} from 'lucide-react';
import { type FormEvent } from 'react';

type OptionItem = { value: string; label: string };
type FluidKindOption = OptionItem & { direction: string };

type IntraVitalRow = VitalSignPoint & {
    recorded_at_full: string;
    respiratory_rate: number | null;
    notes: string | null;
};

type FluidRow = {
    id: number;
    recorded_at: string;
    direction: string;
    direction_label: string;
    kind: string;
    kind_label: string;
    volume_ml: number;
    note: string | null;
};

type NursingActionRow = {
    id: number;
    performed_at: string;
    performed_at_full: string;
    performed_by_role: string;
    performed_by_role_label: string;
    action: string;
    evaluation: string;
    creator_name: string;
};

type CountRowData = {
    item: string;
    label: string;
    initial_count: number;
    additional_count: number;
    final_count: number | string;
    status: string;
};

interface IntraopEdhViewProps {
    caseHeader: CaseHeaderData;
    chronology: {
        updated_at: string | null;
        anesthesia_type_id: number | null;
        operation_plan_actual: string;
        postop_diagnosis: string;
        entered_or_at: string;
        anesthesia_start_at: string;
        incision_at: string;
        surgery_end_at: string;
        anesthesia_end_at: string;
        time_out_completed: boolean;
        time_out_label?: string;
        sign_out_label?: string;
    };
    positionArea: {
        updated_at: string | null;
        position: string;
        pressure_protection: string[];
        skin_antiseptic: string[];
        skin_condition_pre: string;
        skin_condition_post: string;
        draping_done: boolean;
    };
    vitalSigns: IntraVitalRow[];
    fluids: {
        items: FluidRow[];
        total_intake_ml: number;
        total_output_ml: number;
        balance_ml: number;
    };
    nursingActions: NursingActionRow[];
    instrumentCounts: {
        rows: Record<string, CountRowData>;
        count_verification: string;
        count_discrepancy_actions: string[];
    };
    specimenComplication: {
        updated_at: string | null;
        has_complication: boolean;
        complication_description: string;
        complication_handling: string;
    };
    procedureDetails: {
        updated_at: string | null;
        details: IntraopProcedureDetails;
    };
    options: {
        positions: OptionItem[];
        skinIntegrities: OptionItem[];
        fluidKinds: FluidKindOption[];
        countVerifications: OptionItem[];
    };
    permissions: {
        canEditIntraop: boolean;
        canEditAnesthesia: boolean;
        canEditComplication: boolean;
    };
    pressureLabels: Array<{ key: string; label: string }>;
    antisepticLabels: Array<{ key: string; label: string }>;
    discrepancyLabels: Array<{ key: string; label: string }>;
    nowWibLocalInput: () => string;
}

export function IntraopEdhView({
    caseHeader,
    chronology,
    positionArea,
    vitalSigns,
    fluids,
    nursingActions,
    instrumentCounts,
    specimenComplication,
    procedureDetails,
    options,
    permissions,
    pressureLabels,
    antisepticLabels,
    discrepancyLabels,
    nowWibLocalInput,
}: IntraopEdhViewProps) {
    // Bagian A: Identitas Operasi & 5 Waktu
    const chronoForm = useForm({
        anesthesia_type_id: chronology.anesthesia_type_id ? String(chronology.anesthesia_type_id) : '',
        operation_plan_actual: chronology.operation_plan_actual || 'Kraniotomi evakuasi hematoma',
        postop_diagnosis: chronology.postop_diagnosis,
        entered_or_at: chronology.entered_or_at,
        anesthesia_start_at: chronology.anesthesia_start_at,
        incision_at: chronology.incision_at,
        surgery_end_at: chronology.surgery_end_at,
        anesthesia_end_at: chronology.anesthesia_end_at,
    });

    // Bagian B.2-13, C.9-10, D.6-7: Detail Tindakan Kraniotomi, Transfusi, Elektrokauter & Alat Operasi
    const procForm = useForm({
        procedure_details: procedureDetails.details,
    });

    // Bagian B.14: Komplikasi Selama Operasi
    const compForm = useForm({
        has_specimen: false,
        specimen_type: '',
        origin_organ: '',
        purpose: '',
        preservative: '',
        has_complication: specimenComplication.has_complication,
        complication_description: specimenComplication.complication_description,
        complication_handling: specimenComplication.complication_handling,
    });

    // Bagian C.1-5: Pemantauan TTV Berkala (5 parameter SKPL)
    const vitalForm = useForm({
        recorded_at: nowWibLocalInput(),
        bp_systolic: '',
        bp_diastolic: '',
        pulse: '',
        temperature: '',
        respiratory_rate: '',
        spo2: '',
        breathing_pattern: 'eupnea',
        oxygen_device: 'tanpa_oksigen',
        oxygen_flow_lpm: '',
        notes: '',
    });

    // Bagian C.6-8, C.11: Cairan & Keseimbangan
    const fluidForm = useForm({
        recorded_at: nowWibLocalInput(),
        kind: 'kristaloid',
        volume_ml: '',
        note: '',
    });

    // Bagian D.1-5: Posisi, Kulit, Penekanan, Draping, Antisepsis
    const posForm = useForm({
        position: positionArea.position,
        pressure_protection: [...positionArea.pressure_protection],
        skin_antiseptic: [...positionArea.skin_antiseptic],
        skin_condition_pre: positionArea.skin_condition_pre,
        skin_condition_post: positionArea.skin_condition_post,
        draping_done: positionArea.draping_done,
    });

    // Bagian D.8-10: Penghitungan Kasa, Jarum, Instrumen (strictly 3 SKPL items)
    const countForm = useForm({
        counts: Object.fromEntries(
            Object.entries(instrumentCounts.rows).map(([key, row]) => [
                key,
                {
                    initial_count: String(row.initial_count),
                    additional_count: String(row.additional_count),
                    final_count: row.final_count === '' ? '' : String(row.final_count),
                },
            ]),
        ) as Record<string, { initial_count: string; additional_count: string; final_count: string }>,
        count_verification: instrumentCounts.count_verification,
        count_discrepancy_actions: [...instrumentCounts.count_discrepancy_actions],
    });

    // Bagian D.13: Dokumentasi Tindakan Keperawatan
    const actionForm = useForm({
        performed_at: nowWibLocalInput(),
        performed_by_role: 'sirkuler',
        action: '',
        evaluation: '',
    });

    const toggleArrayValue = (list: string[], value: string): string[] =>
        list.includes(value) ? list.filter((item) => item !== value) : [...list, value];

    const updateProcField = <K extends keyof IntraopProcedureDetails>(field: K, value: IntraopProcedureDetails[K]) => {
        procForm.setData('procedure_details', {
            ...procForm.data.procedure_details,
            [field]: value,
        });
    };

    const submitChronology = (e: FormEvent) => {
        e.preventDefault();
        chronoForm.transform((data) => ({
            ...data,
            expected_updated_at: chronology.updated_at ?? '',
        }));
        chronoForm.put(route('cases.intraop.chronology', caseHeader.id), { preserveScroll: true });
    };

    const submitProcDetails = (e: FormEvent) => {
        e.preventDefault();
        procForm.transform((data) => ({
            procedure_details: data.procedure_details,
            expected_updated_at: procedureDetails.updated_at ?? '',
        }));
        procForm.put(route('cases.intraop.procedure-details', caseHeader.id), { preserveScroll: true });
    };

    const submitComplication = (e: FormEvent) => {
        e.preventDefault();
        compForm.transform((data) => ({
            ...data,
            expected_updated_at: specimenComplication.updated_at ?? '',
        }));
        compForm.put(route('cases.intraop.specimen-complication', caseHeader.id), { preserveScroll: true });
    };

    const submitVital = (e: FormEvent) => {
        e.preventDefault();
        vitalForm.post(route('cases.intraop.vitals.store', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                vitalForm.reset('bp_systolic', 'bp_diastolic', 'pulse', 'temperature', 'respiratory_rate', 'spo2', 'notes');
                vitalForm.setData('recorded_at', nowWibLocalInput());
            },
        });
    };

    const submitFluid = (e: FormEvent) => {
        e.preventDefault();
        fluidForm.post(route('cases.intraop.fluids.store', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                fluidForm.reset('volume_ml', 'note');
                fluidForm.setData('recorded_at', nowWibLocalInput());
            },
        });
    };

    const submitPosition = (e: FormEvent) => {
        e.preventDefault();
        posForm.transform((data) => ({
            ...data,
            expected_updated_at: positionArea.updated_at ?? '',
        }));
        posForm.put(route('cases.intraop.position', caseHeader.id), { preserveScroll: true });
    };

    const submitCounts = (e: FormEvent) => {
        e.preventDefault();
        countForm.put(route('cases.intraop.counts', caseHeader.id), { preserveScroll: true });
    };

    const submitNursingAction = (e: FormEvent) => {
        e.preventDefault();
        actionForm.post(route('cases.intraop.actions.store', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                actionForm.reset('action', 'evaluation');
                actionForm.setData('performed_at', nowWibLocalInput());
            },
        });
    };

    const computedLiveCountStatus = (key: string): 'S' | 'TS' | '' => {
        const row = countForm.data.counts[key];
        if (!row || row.final_count.trim() === '') {
            return '';
        }
        const expected = (Number(row.initial_count) || 0) + (Number(row.additional_count) || 0);
        return expected === Number(row.final_count) ? 'S' : 'TS';
    };

    const anyCountMismatch = Object.keys(instrumentCounts.rows).some((k) => computedLiveCountStatus(k) === 'TS');

    const pd = procForm.data.procedure_details;
    const edhFluidKinds = options.fluidKinds.filter((f) => f.value !== 'irrigasi');
    const selectedPositionLabel = options.positions.find((p) => p.value === posForm.data.position)?.label ?? 'Belum dipilih pada Bagian D.1';

    return (
        <div className="space-y-6">
            <ConcurrencyAlert
                message={
                    (chronoForm.errors as Record<string, string | undefined>).concurrency ??
                    (posForm.errors as Record<string, string | undefined>).concurrency ??
                    (compForm.errors as Record<string, string | undefined>).concurrency ??
                    (procForm.errors as Record<string, string | undefined>).concurrency
                }
            />

            {/* BAGIAN A: Data Identitas Operasi & Waktu (13 item SKPL) */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Clock className="text-primary h-5 w-5" />
                        A. Data Identitas Operasi &amp; Waktu (Bedah Saraf EDH)
                    </CardTitle>
                    <CardDescription>
                        Identitas tindakan kraniotomi, tim kamar operasi, diagnosis pra/pascaoperasi, serta 5 waktu pelaksanaan operasi.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submitChronology} className="space-y-5">
                        <div className="bg-muted/30 grid grid-cols-1 gap-3 rounded-lg border p-3.5 text-sm md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <div className="text-muted-foreground text-xs">1. Tanggal Operasi</div>
                                <div className="font-medium">{caseHeader.scheduled_at ?? '-'}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">3. Diagnosis Praoperasi</div>
                                <div className="font-medium">{caseHeader.diagnosis || '-'}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">5. Nama Dokter Operator</div>
                                <div className="font-medium">{caseHeader.team.surgeon ?? '-'}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">6. Nama Dokter Anestesi</div>
                                <div className="font-medium">{caseHeader.team.anesthesiologist ?? '-'}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">7. Nama Perawat Instrumen</div>
                                <div className="font-medium">{caseHeader.team.scrub_nurse ?? '-'}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">8. Nama Perawat Sirkuler</div>
                                <div className="font-medium">{caseHeader.team.circulating_nurse ?? '-'}</div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_op_actual">2. Nama Tindakan</Label>
                                <Input
                                    id="edh_op_actual"
                                    value={chronoForm.data.operation_plan_actual}
                                    onChange={(e) => chronoForm.setData('operation_plan_actual', e.target.value)}
                                    disabled={!permissions.canEditIntraop}
                                    placeholder="Kraniotomi evakuasi hematoma"
                                    className="min-h-11"
                                />
                                <InputError message={chronoForm.errors.operation_plan_actual} />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_postop_diag">4. Diagnosis Pascaoperasi</Label>
                                <Input
                                    id="edh_postop_diag"
                                    value={chronoForm.data.postop_diagnosis}
                                    onChange={(e) => chronoForm.setData('postop_diagnosis', e.target.value)}
                                    disabled={!permissions.canEditIntraop}
                                    placeholder="Post Kraniotomi Evakuasi Epidural Hematoma (EDH)"
                                    className="min-h-11"
                                />
                                <InputError message={chronoForm.errors.postop_diagnosis} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            {[
                                { id: 'edh_entered_or_at', key: 'entered_or_at' as const, label: '9. Jam Masuk Kamar Operasi' },
                                { id: 'edh_anesthesia_start_at', key: 'anesthesia_start_at' as const, label: '10. Jam Mulai Anestesi' },
                                { id: 'edh_incision_at', key: 'incision_at' as const, label: '11. Jam Mulai Operasi / Insisi' },
                                { id: 'edh_surgery_end_at', key: 'surgery_end_at' as const, label: '12. Jam Selesai Operasi' },
                                { id: 'edh_anesthesia_end_at', key: 'anesthesia_end_at' as const, label: '13. Jam Selesai Anestesi' },
                            ].map((item) => (
                                <div key={item.id} className="space-y-1.5">
                                    <Label htmlFor={item.id}>{item.label}</Label>
                                    <Input
                                        id={item.id}
                                        type="datetime-local"
                                        value={chronoForm.data[item.key]}
                                        onChange={(e) => chronoForm.setData(item.key, e.target.value)}
                                        disabled={!permissions.canEditIntraop}
                                        className="min-h-11"
                                    />
                                    <InputError message={chronoForm.errors[item.key]} />
                                </div>
                            ))}
                        </div>

                        {permissions.canEditIntraop && (
                            <div className="flex justify-end">
                                <Button type="submit" className="min-h-11" disabled={chronoForm.processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {chronoForm.processing ? 'Menyimpan...' : 'Simpan Bagian A (Identitas & Waktu)'}
                                </Button>
                            </div>
                        )}
                    </form>
                </CardContent>
            </Card>

            {/* BAGIAN B: Data Tindakan Operasi Kraniotomi (14 item SKPL) */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Brain className="text-primary h-5 w-5" />
                        B. Data Tindakan Operasi Kraniotomi (EDH)
                    </CardTitle>
                    <CardDescription>
                        Seluruh langkah pembedahan kraniotomi evakuasi hematoma beserta drain, penutupan luka, dan komplikasi operasi.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <form onSubmit={submitProcDetails} className="space-y-4">
                        <div className="bg-muted/30 rounded-lg border p-3 text-sm">
                            <span className="text-muted-foreground text-xs font-medium">1. Posisi Pasien Selama Operasi: </span>
                            <span className="font-semibold">{selectedPositionLabel}</span>
                            <span className="text-muted-foreground ml-2 text-xs">(diatur bersama pada Bagian D.1 di bawah)</span>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b2">2. Lokasi &amp; Sisi Sayatan (Insisi)</Label>
                                <Input
                                    id="edh_b2"
                                    value={pd.incision_location_side}
                                    onChange={(e) => updateProcField('incision_location_side', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Trauma flap frontotemporoparietal kanan"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b3">3. Lokasi Kraniotomi</Label>
                                <Input
                                    id="edh_b3"
                                    value={pd.craniotomy_location}
                                    onChange={(e) => updateProcField('craniotomy_location', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Regio temporoparietal dekstra"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="edh_b4">4. Kondisi Jaringan &amp; Tulang Tengkorak</Label>
                                <Input
                                    id="edh_b4"
                                    value={pd.bone_tissue_condition}
                                    onChange={(e) => updateProcField('bone_tissue_condition', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Fraktur linear os temporal dekstra, perdarahan aktif cabang a. meningea media"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b5">5. Volume &amp; Kondisi Hematoma</Label>
                                <Input
                                    id="edh_b5"
                                    value={pd.hematoma_volume_condition}
                                    onChange={(e) => updateProcField('hematoma_volume_condition', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Bekuan darah epidural +/- 45 mL terorganisir"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b6">6. Proses Pembukaan Tulang Tengkorak</Label>
                                <Input
                                    id="edh_b6"
                                    value={pd.craniotomy_opening_process}
                                    onChange={(e) => updateProcField('craniotomy_opening_process', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Burr hole 4 titik, dihubungkan craniotome"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b7">7. Proses Pengangkatan / Evakuasi Hematoma</Label>
                                <Input
                                    id="edh_b7"
                                    value={pd.hematoma_evacuation_process}
                                    onChange={(e) => updateProcField('hematoma_evacuation_process', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Evakuasi bekuan bertahap dgn suction & irigasi NaCl 0.9%"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b8">8. Tindakan Penghentian Perdarahan (Hemostasis)</Label>
                                <Input
                                    id="edh_b8"
                                    value={pd.hemostasis_action}
                                    onChange={(e) => updateProcField('hemostasis_action', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Koagulasi bipolar, bone wax, Spongostan"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b9">9. Kondisi Jaringan Otak Setelah Evakuasi</Label>
                                <Input
                                    id="edh_b9"
                                    value={pd.brain_tissue_condition}
                                    onChange={(e) => updateProcField('brain_tissue_condition', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Dura utuh, pulsasi baik, tidak edema masif"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b10">10. Pengelolaan Potongan Tulang Tengkorak (Bone Flap)</Label>
                                <Input
                                    id="edh_b10"
                                    value={pd.bone_flap_management}
                                    onChange={(e) => updateProcField('bone_flap_management', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Dikembalikan & difiksasi"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b11">11. Pemasangan Drain</Label>
                                <Input
                                    id="edh_b11"
                                    value={pd.drain_placement}
                                    onChange={(e) => updateProcField('drain_placement', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Redon drain vakum 1 buah"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_b12">12. Penutupan Luka</Label>
                                <Input
                                    id="edh_b12"
                                    value={pd.wound_closure}
                                    onChange={(e) => updateProcField('wound_closure', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Jahit lapis demi lapis"
                                    className="min-h-11"
                                />
                            </div>
                            <div className="space-y-1.5 md:col-span-2">
                                <Label htmlFor="edh_b13">13. Kondisi Luka Setelah Operasi</Label>
                                <Input
                                    id="edh_b13"
                                    value={pd.postop_wound_condition}
                                    onChange={(e) => updateProcField('postop_wound_condition', e.target.value)}
                                    disabled={!permissions.canEditComplication}
                                    placeholder="Tertutup rapat, kasa kering, tidak rembes"
                                    className="min-h-11"
                                />
                            </div>
                        </div>

                        {permissions.canEditComplication && (
                            <div className="flex justify-end">
                                <Button type="submit" className="min-h-11" disabled={procForm.processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {procForm.processing ? 'Menyimpan...' : 'Simpan Tindakan Kraniotomi (B.2 - B.13)'}
                                </Button>
                            </div>
                        )}
                    </form>

                    {/* 14. Komplikasi Selama Operasi */}
                    <form onSubmit={submitComplication} className="space-y-4 border-t pt-4">
                        <div className="space-y-3">
                            <div className="text-sm font-semibold">14. Komplikasi Selama Operasi</div>
                            <label className="flex cursor-pointer items-center gap-3 text-sm font-medium">
                                <Checkbox
                                    checked={compForm.data.has_complication}
                                    disabled={!permissions.canEditComplication}
                                    onCheckedChange={(checked) => compForm.setData('has_complication', checked === true)}
                                />
                                <span>Terjadi komplikasi / kejadian tidak diharapkan intra-operasi</span>
                            </label>

                            {compForm.data.has_complication && (
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="edh_comp_desc">Deskripsi Komplikasi *</Label>
                                        <textarea
                                            id="edh_comp_desc"
                                            rows={2}
                                            value={compForm.data.complication_description}
                                            onChange={(e) => compForm.setData('complication_description', e.target.value)}
                                            disabled={!permissions.canEditComplication}
                                            className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                        />
                                        <InputError message={compForm.errors.complication_description} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="edh_comp_handle">Tindakan Penanganan *</Label>
                                        <textarea
                                            id="edh_comp_handle"
                                            rows={2}
                                            value={compForm.data.complication_handling}
                                            onChange={(e) => compForm.setData('complication_handling', e.target.value)}
                                            disabled={!permissions.canEditComplication}
                                            className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                        />
                                        <InputError message={compForm.errors.complication_handling} />
                                    </div>
                                </div>
                            )}
                        </div>

                        {permissions.canEditComplication && (
                            <div className="flex justify-end">
                                <Button type="submit" variant="outline" className="min-h-11" disabled={compForm.processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {compForm.processing ? 'Menyimpan...' : 'Simpan Komplikasi Operasi (B.14)'}
                                </Button>
                            </div>
                        )}
                    </form>
                </CardContent>
            </Card>

            {/* BAGIAN C: Pemantauan Fisiologis & Cairan Selama Operasi (11 item SKPL) */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Activity className="text-primary h-5 w-5" />
                        C. Pemantauan Fisiologis &amp; Cairan Selama Operasi
                    </CardTitle>
                    <CardDescription>
                        Pemantauan 5 parameter tanda vital berkala, cairan infus, perdarahan, urine, transfusi darah, serta keseimbangan cairan
                        otomatis.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="space-y-4">
                        <div className="text-sm font-semibold">1-5. Pemantauan Tanda-Tanda Vital Berkala</div>
                        <VitalSignChart data={vitalSigns} />

                        {permissions.canEditAnesthesia && (
                            <form onSubmit={submitVital} className="bg-muted/30 grid grid-cols-2 gap-3 rounded-lg border p-3.5 md:grid-cols-6">
                                <div className="col-span-2 space-y-1 md:col-span-2">
                                    <Label htmlFor="edh_v_time">Waktu Catat</Label>
                                    <Input
                                        id="edh_v_time"
                                        type="datetime-local"
                                        value={vitalForm.data.recorded_at}
                                        onChange={(e) => vitalForm.setData('recorded_at', e.target.value)}
                                        className="min-h-11"
                                    />
                                    <InputError message={vitalForm.errors.recorded_at} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_v_sys">1a. TD Sistolik (mmHg)</Label>
                                    <Input
                                        id="edh_v_sys"
                                        type="number"
                                        value={vitalForm.data.bp_systolic}
                                        onChange={(e) => vitalForm.setData('bp_systolic', e.target.value)}
                                        placeholder="120"
                                        className="min-h-11"
                                    />
                                    <InputError message={vitalForm.errors.bp_systolic} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_v_dia">1b. TD Diastolik (mmHg)</Label>
                                    <Input
                                        id="edh_v_dia"
                                        type="number"
                                        value={vitalForm.data.bp_diastolic}
                                        onChange={(e) => vitalForm.setData('bp_diastolic', e.target.value)}
                                        placeholder="80"
                                        className="min-h-11"
                                    />
                                    <InputError message={vitalForm.errors.bp_diastolic} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_v_pulse">2. Nadi (x/mnt)</Label>
                                    <Input
                                        id="edh_v_pulse"
                                        type="number"
                                        value={vitalForm.data.pulse}
                                        onChange={(e) => vitalForm.setData('pulse', e.target.value)}
                                        placeholder="80"
                                        className="min-h-11"
                                    />
                                    <InputError message={vitalForm.errors.pulse} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_v_rr">3. Napas (x/mnt)</Label>
                                    <Input
                                        id="edh_v_rr"
                                        type="number"
                                        value={vitalForm.data.respiratory_rate}
                                        onChange={(e) => vitalForm.setData('respiratory_rate', e.target.value)}
                                        placeholder="16"
                                        className="min-h-11"
                                    />
                                    <InputError message={vitalForm.errors.respiratory_rate} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_v_temp">4. Suhu (C)</Label>
                                    <Input
                                        id="edh_v_temp"
                                        type="number"
                                        step="0.1"
                                        value={vitalForm.data.temperature}
                                        onChange={(e) => vitalForm.setData('temperature', e.target.value)}
                                        placeholder="36.5"
                                        className="min-h-11"
                                    />
                                    <InputError message={vitalForm.errors.temperature} />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_v_spo2">5. SpO2 (%)</Label>
                                    <Input
                                        id="edh_v_spo2"
                                        type="number"
                                        value={vitalForm.data.spo2}
                                        onChange={(e) => vitalForm.setData('spo2', e.target.value)}
                                        placeholder="99"
                                        className="min-h-11"
                                    />
                                    <InputError message={vitalForm.errors.spo2} />
                                </div>
                                <div className="col-span-2 flex items-end md:col-span-4">
                                    <Button type="submit" className="min-h-11 w-full md:w-auto" disabled={vitalForm.processing}>
                                        <Plus className="mr-1.5 h-4 w-4" />
                                        {vitalForm.processing ? 'Menyimpan...' : 'Catat TTV Intra-Op'}
                                    </Button>
                                </div>
                            </form>
                        )}

                        {vitalSigns.length > 0 && (
                            <div className="overflow-x-auto rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Jam</TableHead>
                                            <TableHead>Tekanan Darah</TableHead>
                                            <TableHead>Nadi</TableHead>
                                            <TableHead>RR</TableHead>
                                            <TableHead>Suhu</TableHead>
                                            <TableHead>SpO2</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {vitalSigns.map((v) => (
                                            <TableRow key={v.id}>
                                                <TableCell className="whitespace-nowrap">{v.recorded_at}</TableCell>
                                                <TableCell className="font-medium whitespace-nowrap">
                                                    {v.bp_systolic}/{v.bp_diastolic} mmHg
                                                </TableCell>
                                                <TableCell>{v.pulse} x/mnt</TableCell>
                                                <TableCell>{v.respiratory_rate ?? '-'} x/mnt</TableCell>
                                                <TableCell>{v.temperature} C</TableCell>
                                                <TableCell>{v.spo2}%</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>

                    {/* 6-11. Cairan Infus, Perdarahan, Urine, Transfusi, Keseimbangan */}
                    <div className="space-y-4 border-t pt-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div className="flex items-center gap-2 text-sm font-semibold">
                                <Droplets className="text-primary h-4 w-4" />
                                6-11. Cairan Infus, Perdarahan, Urine &amp; Transfusi Darah
                            </div>
                            <div className="flex flex-wrap gap-2 text-xs">
                                <Badge variant="secondary">Masuk: {fluids.total_intake_ml} mL</Badge>
                                <Badge variant="secondary">Keluar (Perdarahan/Urine): {fluids.total_output_ml} mL</Badge>
                                <Badge className={fluids.balance_ml >= 0 ? 'bg-emerald-600 text-white' : 'bg-amber-600 text-white'}>
                                    11. Keseimbangan Cairan: {fluids.balance_ml >= 0 ? '+' : ''}
                                    {fluids.balance_ml} mL
                                </Badge>
                            </div>
                        </div>

                        {permissions.canEditAnesthesia && (
                            <form onSubmit={submitFluid} className="bg-muted/30 grid grid-cols-1 gap-3 rounded-lg border p-3.5 md:grid-cols-4">
                                <div className="space-y-1">
                                    <Label htmlFor="edh_f_time">Waktu</Label>
                                    <Input
                                        id="edh_f_time"
                                        type="datetime-local"
                                        value={fluidForm.data.recorded_at}
                                        onChange={(e) => fluidForm.setData('recorded_at', e.target.value)}
                                        className="min-h-11"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_f_kind">6/7/8. Jenis Cairan</Label>
                                    <Select value={fluidForm.data.kind} onValueChange={(v) => fluidForm.setData('kind', v)}>
                                        <SelectTrigger id="edh_f_kind" className="min-h-11">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {edhFluidKinds.map((fk) => (
                                                <SelectItem key={fk.value} value={fk.value}>
                                                    {fk.label} ({fk.direction === 'masuk' ? 'Masuk' : 'Keluar'})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <Label htmlFor="edh_f_vol">Volume (mL)</Label>
                                    <Input
                                        id="edh_f_vol"
                                        type="number"
                                        value={fluidForm.data.volume_ml}
                                        onChange={(e) => fluidForm.setData('volume_ml', e.target.value)}
                                        placeholder="500"
                                        className="min-h-11"
                                    />
                                    <InputError message={fluidForm.errors.volume_ml} />
                                </div>
                                <div className="flex items-end">
                                    <Button type="submit" className="min-h-11 w-full" disabled={fluidForm.processing}>
                                        <Plus className="mr-1.5 h-4 w-4" />
                                        {fluidForm.processing ? 'Menyimpan...' : 'Catat Cairan'}
                                    </Button>
                                </div>
                            </form>
                        )}

                        {fluids.items.length > 0 && (
                            <div className="overflow-x-auto rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Jam</TableHead>
                                            <TableHead>Arah</TableHead>
                                            <TableHead>Jenis</TableHead>
                                            <TableHead className="text-right">Volume (mL)</TableHead>
                                            <TableHead />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {fluids.items.map((f) => (
                                            <TableRow key={f.id}>
                                                <TableCell>{f.recorded_at}</TableCell>
                                                <TableCell>
                                                    <Badge variant={f.direction === 'masuk' ? 'default' : 'secondary'}>{f.direction_label}</Badge>
                                                </TableCell>
                                                <TableCell>{f.kind_label}</TableCell>
                                                <TableCell className="text-right font-semibold">{f.volume_ml} mL</TableCell>
                                                <TableCell className="text-right">
                                                    {permissions.canEditAnesthesia && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                router.delete(route('cases.intraop.fluids.destroy', [caseHeader.id, f.id]), {
                                                                    preserveScroll: true,
                                                                })
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4 text-rose-600" />
                                                        </Button>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}

                        <form onSubmit={submitProcDetails} className="bg-muted/30 space-y-3 rounded-lg border p-3.5">
                            <label className="flex cursor-pointer items-center gap-2.5 text-sm font-medium">
                                <Checkbox
                                    checked={pd.blood_transfusion_given}
                                    disabled={!permissions.canEditComplication}
                                    onCheckedChange={(c) => updateProcField('blood_transfusion_given', c === true)}
                                />
                                <span>9. Diberikan Transfusi Darah Selama Operasi</span>
                            </label>
                            {pd.blood_transfusion_given && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="edh_c10">10. Jenis &amp; Jumlah Produk Darah</Label>
                                    <Input
                                        id="edh_c10"
                                        value={pd.blood_product_type_amount}
                                        onChange={(e) => updateProcField('blood_product_type_amount', e.target.value)}
                                        disabled={!permissions.canEditComplication}
                                        placeholder="contoh: PRC 2 kolf (500 mL)"
                                        className="min-h-11"
                                    />
                                    <InputError
                                        message={
                                            (procForm.errors as Record<string, string | undefined>)['procedure_details.blood_product_type_amount']
                                        }
                                    />
                                </div>
                            )}
                            {permissions.canEditComplication && (
                                <div className="flex justify-end">
                                    <Button type="submit" size="sm" variant="outline" className="min-h-10" disabled={procForm.processing}>
                                        <Save className="mr-1.5 h-3.5 w-3.5" />
                                        Simpan Status Transfusi (C.9 - C.10)
                                    </Button>
                                </div>
                            )}
                        </form>
                    </div>
                </CardContent>
            </Card>

            {/* BAGIAN D: Asuhan Keperawatan Kamar Operasi (13 item SKPL) */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <ClipboardList className="text-primary h-5 w-5" />
                        D. Asuhan Keperawatan Kamar Operasi
                    </CardTitle>
                    <CardDescription>
                        Posisi &amp; proteksi kulit, elektrokauter, alat operasi, penghitungan Kasa/Jarum/Instrumen (3 item), SSC, serta tindakan
                        keperawatan.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                    {/* D.1-D.5 */}
                    <form onSubmit={submitPosition} className="space-y-5">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_d1">1. Pengaturan Posisi Pasien (B.1 &amp; D.1)</Label>
                                <Select
                                    value={posForm.data.position}
                                    onValueChange={(v) => posForm.setData('position', v)}
                                    disabled={!permissions.canEditIntraop}
                                >
                                    <SelectTrigger id="edh_d1" className="min-h-11">
                                        <SelectValue placeholder="Pilih posisi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.positions.map((p) => (
                                            <SelectItem key={p.value} value={p.value}>
                                                {p.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_d2_pre">2a. Kondisi Kulit Sebelum Operasi</Label>
                                <Select
                                    value={posForm.data.skin_condition_pre}
                                    onValueChange={(v) => posForm.setData('skin_condition_pre', v)}
                                    disabled={!permissions.canEditIntraop}
                                >
                                    <SelectTrigger id="edh_d2_pre" className="min-h-11">
                                        <SelectValue placeholder="Pilih kondisi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.skinIntegrities.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>
                                                {s.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="edh_d2_post">2b. Kondisi Kulit Sesudah Operasi</Label>
                                <Select
                                    value={posForm.data.skin_condition_post}
                                    onValueChange={(v) => posForm.setData('skin_condition_post', v)}
                                    disabled={!permissions.canEditIntraop}
                                >
                                    <SelectTrigger id="edh_d2_post" className="min-h-11">
                                        <SelectValue placeholder="Pilih kondisi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.skinIntegrities.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>
                                                {s.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-2 rounded-lg border p-3.5">
                                <div className="text-sm font-medium">3. Perlindungan Area Penekanan</div>
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {pressureLabels.map((item) => (
                                        <label key={item.key} className="flex cursor-pointer items-center gap-2 text-sm">
                                            <Checkbox
                                                checked={posForm.data.pressure_protection.includes(item.key)}
                                                disabled={!permissions.canEditIntraop}
                                                onCheckedChange={() =>
                                                    posForm.setData(
                                                        'pressure_protection',
                                                        toggleArrayValue(posForm.data.pressure_protection, item.key),
                                                    )
                                                }
                                            />
                                            <span>{item.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-2 rounded-lg border p-3.5">
                                <div className="text-sm font-medium">5. Antisepsis Kulit</div>
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {antisepticLabels.map((item) => (
                                        <label key={item.key} className="flex cursor-pointer items-center gap-2 text-sm">
                                            <Checkbox
                                                checked={posForm.data.skin_antiseptic.includes(item.key)}
                                                disabled={!permissions.canEditIntraop}
                                                onCheckedChange={() =>
                                                    posForm.setData('skin_antiseptic', toggleArrayValue(posForm.data.skin_antiseptic, item.key))
                                                }
                                            />
                                            <span>{item.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3.5">
                            <label className="flex cursor-pointer items-center gap-2 text-sm font-medium">
                                <Checkbox
                                    checked={posForm.data.draping_done}
                                    disabled={!permissions.canEditIntraop}
                                    onCheckedChange={(checked) => posForm.setData('draping_done', checked === true)}
                                />
                                <span>4. Persiapan Area Operasi (Draping Steril Terpasang Baik)</span>
                            </label>
                            {permissions.canEditIntraop && (
                                <Button type="submit" className="min-h-11" disabled={posForm.processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {posForm.processing ? 'Menyimpan...' : 'Simpan Posisi & Area (D.1 - D.5)'}
                                </Button>
                            )}
                        </div>
                    </form>

                    {/* D.6-D.7 */}
                    <form onSubmit={submitProcDetails} className="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_d6">6. Penggunaan Elektrokauter (ESU)</Label>
                            <Input
                                id="edh_d6"
                                value={pd.cautery_usage}
                                onChange={(e) => updateProcField('cautery_usage', e.target.value)}
                                disabled={!permissions.canEditComplication}
                                placeholder="Bipolar 25 W / Monopolar 35 W, kontak baik"
                                className="min-h-11"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="edh_d7">7. Penggunaan Alat Operasi</Label>
                            <Input
                                id="edh_d7"
                                value={pd.equipment_usage_notes}
                                onChange={(e) => updateProcField('equipment_usage_notes', e.target.value)}
                                disabled={!permissions.canEditComplication}
                                placeholder="Craniotome, bor, suction, mikroskop/headlamp berfungsi baik"
                                className="min-h-11"
                            />
                        </div>
                        {permissions.canEditComplication && (
                            <div className="flex justify-end md:col-span-2">
                                <Button type="submit" variant="outline" className="min-h-11" disabled={procForm.processing}>
                                    <Save className="mr-2 h-4 w-4" />
                                    {procForm.processing ? 'Menyimpan...' : 'Simpan Elektrokauter & Alat (D.6 - D.7)'}
                                </Button>
                            </div>
                        )}
                    </form>

                    {/* D.8-D.10 Penghitungan 3 Item: Kasa, Jarum, Instrumen */}
                    <form onSubmit={submitCounts} className="space-y-4 border-t pt-4">
                        <div className="flex items-center gap-2 text-sm font-semibold">
                            <ListChecks className="text-primary h-4 w-4" />
                            8-10. Penghitungan Kasa, Jarum &amp; Instrumen Sebelum &amp; Sesudah Operasi
                        </div>
                        <div className="overflow-x-auto rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Item Hitung</TableHead>
                                        <TableHead className="w-28 text-center">Awal</TableHead>
                                        <TableHead className="w-28 text-center">Tambahan</TableHead>
                                        <TableHead className="w-32 text-center">Akhir</TableHead>
                                        <TableHead className="w-28 text-center">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {Object.entries(instrumentCounts.rows).map(([key, row]) => {
                                        const liveStatus = computedLiveCountStatus(key);
                                        return (
                                            <TableRow key={key}>
                                                <TableCell className="font-medium">{row.label}</TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min={0}
                                                        value={countForm.data.counts[key]?.initial_count ?? '0'}
                                                        onChange={(e) =>
                                                            countForm.setData('counts', {
                                                                ...countForm.data.counts,
                                                                [key]: { ...countForm.data.counts[key], initial_count: e.target.value },
                                                            })
                                                        }
                                                        disabled={!permissions.canEditIntraop}
                                                        className="min-h-11 text-center"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min={0}
                                                        value={countForm.data.counts[key]?.additional_count ?? '0'}
                                                        onChange={(e) =>
                                                            countForm.setData('counts', {
                                                                ...countForm.data.counts,
                                                                [key]: { ...countForm.data.counts[key], additional_count: e.target.value },
                                                            })
                                                        }
                                                        disabled={!permissions.canEditIntraop}
                                                        className="min-h-11 text-center"
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min={0}
                                                        placeholder="-"
                                                        value={countForm.data.counts[key]?.final_count ?? ''}
                                                        onChange={(e) =>
                                                            countForm.setData('counts', {
                                                                ...countForm.data.counts,
                                                                [key]: { ...countForm.data.counts[key], final_count: e.target.value },
                                                            })
                                                        }
                                                        disabled={!permissions.canEditIntraop}
                                                        className="min-h-11 text-center"
                                                    />
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {liveStatus === 'S' && <Badge className="bg-emerald-600 text-white">Sesuai (S)</Badge>}
                                                    {liveStatus === 'TS' && <Badge variant="destructive">Tidak Sesuai</Badge>}
                                                    {liveStatus === '' && <Badge variant="secondary">Belum Final</Badge>}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="space-y-3 rounded-lg border p-3.5">
                            <Label htmlFor="edh_count_verif">Hasil Verifikasi Penghitungan</Label>
                            <Select
                                value={countForm.data.count_verification}
                                onValueChange={(v) => countForm.setData('count_verification', v)}
                                disabled={!permissions.canEditIntraop}
                            >
                                <SelectTrigger id="edh_count_verif" className="min-h-11">
                                    <SelectValue placeholder="Pilih verifikasi akhir" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.countVerifications.map((c) => (
                                        <SelectItem key={c.value} value={c.value}>
                                            {c.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={countForm.errors.count_verification} />

                            {anyCountMismatch && (
                                <Alert variant="destructive">
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertTitle>Terdeteksi ketidaksesuaian jumlah akhir (TS)</AlertTitle>
                                    <AlertDescription>
                                        <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                            {discrepancyLabels.map((d) => (
                                                <label key={d.key} className="flex cursor-pointer items-center gap-2 text-sm">
                                                    <Checkbox
                                                        checked={countForm.data.count_discrepancy_actions.includes(d.key)}
                                                        disabled={!permissions.canEditIntraop}
                                                        onCheckedChange={() =>
                                                            countForm.setData(
                                                                'count_discrepancy_actions',
                                                                toggleArrayValue(countForm.data.count_discrepancy_actions, d.key),
                                                            )
                                                        }
                                                    />
                                                    <span>{d.label}</span>
                                                </label>
                                            ))}
                                        </div>
                                        <InputError message={countForm.errors.count_discrepancy_actions} />
                                    </AlertDescription>
                                </Alert>
                            )}

                            {permissions.canEditIntraop && (
                                <div className="flex justify-end">
                                    <Button type="submit" className="min-h-11" disabled={countForm.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {countForm.processing ? 'Menyimpan...' : 'Simpan Penghitungan Alat (D.8 - D.10)'}
                                    </Button>
                                </div>
                            )}
                        </div>
                    </form>

                    {/* D.11-D.12 Catatan Keselamatan Operasi: Time Out & Sign Out */}
                    <div className="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
                        <div className="flex items-center justify-between gap-3 rounded-lg border p-3.5">
                            <div>
                                <div className="text-muted-foreground text-xs font-medium">11. Catatan Keselamatan: Time Out</div>
                                <div className="mt-1 flex items-center gap-1.5 text-sm font-semibold">
                                    <ShieldCheck className="text-primary h-4 w-4" />
                                    <span>{chronology.time_out_label ?? (chronology.time_out_completed ? 'Sudah Disahkan' : 'Belum Selesai')}</span>
                                </div>
                            </div>
                            <Button asChild size="sm" variant="outline" className="min-h-10">
                                <Link href={route('cases.ssc.time-out', caseHeader.id)}>Buka Time Out</Link>
                            </Button>
                        </div>

                        <div className="flex items-center justify-between gap-3 rounded-lg border p-3.5">
                            <div>
                                <div className="text-muted-foreground text-xs font-medium">12. Catatan Keselamatan: Sign Out</div>
                                <div className="mt-1 flex items-center gap-1.5 text-sm font-semibold">
                                    <ShieldCheck className="text-primary h-4 w-4" />
                                    <span>{chronology.sign_out_label ?? 'Belum Diisi'}</span>
                                </div>
                            </div>
                            <Button asChild size="sm" variant="outline" className="min-h-10">
                                <Link href={route('cases.ssc.sign-out', caseHeader.id)}>Buka Sign Out</Link>
                            </Button>
                        </div>
                    </div>

                    {/* D.13 Dokumentasi Tindakan Keperawatan */}
                    <div className="space-y-4 border-t pt-4">
                        <div className="flex items-center gap-2 text-sm font-semibold">
                            <Stethoscope className="text-primary h-4 w-4" />
                            13. Dokumentasi Tindakan Keperawatan
                        </div>
                        {permissions.canEditIntraop && (
                            <form
                                onSubmit={submitNursingAction}
                                className="bg-muted/30 grid grid-cols-1 gap-3 rounded-lg border p-3.5 md:grid-cols-12"
                            >
                                <div className="space-y-1 md:col-span-3">
                                    <Label htmlFor="edh_act_time">Waktu</Label>
                                    <Input
                                        id="edh_act_time"
                                        type="datetime-local"
                                        value={actionForm.data.performed_at}
                                        onChange={(e) => actionForm.setData('performed_at', e.target.value)}
                                        className="min-h-11"
                                    />
                                </div>
                                <div className="space-y-1 md:col-span-2">
                                    <Label htmlFor="edh_act_role">Peran</Label>
                                    <Select
                                        value={actionForm.data.performed_by_role}
                                        onValueChange={(v) => actionForm.setData('performed_by_role', v)}
                                    >
                                        <SelectTrigger id="edh_act_role" className="min-h-11">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="sirkuler">Perawat Sirkuler</SelectItem>
                                            <SelectItem value="instrumen">Perawat Instrumen</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1 md:col-span-3">
                                    <Label htmlFor="edh_act_text">Tindakan Keperawatan</Label>
                                    <Input
                                        id="edh_act_text"
                                        value={actionForm.data.action}
                                        onChange={(e) => actionForm.setData('action', e.target.value)}
                                        placeholder="Menyiapkan cairan irigasi & memantau hemostasis"
                                        className="min-h-11"
                                    />
                                    <InputError message={actionForm.errors.action} />
                                </div>
                                <div className="space-y-1 md:col-span-3">
                                    <Label htmlFor="edh_act_eval">Evaluasi / Respons</Label>
                                    <Input
                                        id="edh_act_eval"
                                        value={actionForm.data.evaluation}
                                        onChange={(e) => actionForm.setData('evaluation', e.target.value)}
                                        placeholder="Tindakan berjalan lancar, hemodinamik terpantau"
                                        className="min-h-11"
                                    />
                                    <InputError message={actionForm.errors.evaluation} />
                                </div>
                                <div className="flex items-end md:col-span-1">
                                    <Button type="submit" className="min-h-11 w-full" disabled={actionForm.processing}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                            </form>
                        )}

                        {nursingActions.length > 0 && (
                            <div className="overflow-x-auto rounded-lg border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Waktu</TableHead>
                                            <TableHead>Peran</TableHead>
                                            <TableHead>Tindakan</TableHead>
                                            <TableHead>Evaluasi</TableHead>
                                            <TableHead />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {nursingActions.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell className="whitespace-nowrap">{row.performed_at}</TableCell>
                                                <TableCell>{row.performed_by_role_label}</TableCell>
                                                <TableCell>{row.action}</TableCell>
                                                <TableCell>{row.evaluation}</TableCell>
                                                <TableCell className="text-right">
                                                    {permissions.canEditIntraop && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                router.delete(route('cases.intraop.actions.destroy', [caseHeader.id, row.id]), {
                                                                    preserveScroll: true,
                                                                })
                                                            }
                                                        >
                                                            <Trash2 className="h-4 w-4 text-rose-600" />
                                                        </Button>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
