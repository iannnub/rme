import { IntraopEdhView } from '@/components/cases/intraop/IntraopEdhView';
import { IntraopOrthoView } from '@/components/cases/intraop/IntraopOrthoView';
import {
    IntraopProcedureDetailsCard,
    type IntraopImplantRow,
    type IntraopProcedureDetails,
} from '@/components/cases/intraop/IntraopProcedureDetailsCard';
import { VitalSignChart, type VitalSignPoint } from '@/components/cases/vital-sign-chart';
import { ConcurrencyAlert } from '@/components/concurrency-alert';
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
import { cn } from '@/lib/utils';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    ClipboardList,
    Clock,
    Droplets,
    FileCheck2,
    FlaskConical,
    ListChecks,
    Lock,
    Pill,
    Plus,
    Save,
    ShieldCheck,
    Stethoscope,
    Trash2,
} from 'lucide-react';
import { useState, type FormEvent } from 'react';

type OptionItem = { value: string; label: string };
type FluidKindOption = OptionItem & { direction: string };
type AnesthesiaOption = { id: number; code: string; name: string };
type MasterDiagnosis = { id: number; code: string; name: string };

type IntraVitalRow = VitalSignPoint & {
    recorded_at_full: string;
    respiratory_rate: number | null;
    breathing_pattern: string;
    oxygen_device: string;
    oxygen_flow_lpm: number | null;
    notes: string | null;
};

type MedicationRow = {
    id: number;
    name: string;
    dose: string;
    route: string;
    given_at: string;
    note: string | null;
    creator_name: string;
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

type CaseDiagnosisItem = {
    id: number;
    name: string;
    code: string | null;
    custom_text: string | null;
    notes: string | null;
    slki: Array<{ id: number; outcome_name: string; criterion_text: string; expectation: string; target_score: number }>;
    siki: Array<{
        id: number;
        name: string;
        items: Array<{
            id: number | null;
            siki_item_id: number;
            section: string;
            section_label: string;
            text: string;
            is_done: boolean;
            done_at: string | null;
        }>;
    }>;
};

type SignoffCard = {
    role_label: string;
    title: string;
    assigned_name: string;
    id_label: string;
    signed: { user_name: string; identifier: string | null; signed_at: string } | null;
    can_sign: boolean;
};

interface IntraopPageProps {
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
        left_or_at: string;
        anesthesia_end_at: string;
        sign_in_completed: boolean;
        time_out_completed: boolean;
        time_out_label?: string;
        sign_out_label?: string;
    };
    positionArea: {
        updated_at: string | null;
        position: string;
        pressure_protection: string[];
        skin_antiseptic: string[];
        grounding_plate_site: string;
        skin_condition_pre: string;
        skin_condition_post: string;
        draping_done: boolean;
        warming: string[];
        room_temperature_c: string | number;
    };
    vitalSigns: IntraVitalRow[];
    medications: MedicationRow[];
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
        has_specimen: boolean;
        specimen_type: string;
        origin_organ: string;
        purpose: string;
        preservative: string;
        has_complication: boolean;
        complication_description: string;
        complication_handling: string;
    };
    procedureDetails: {
        updated_at: string | null;
        details: IntraopProcedureDetails;
        implants: IntraopImplantRow[];
    };
    caseNursingDiagnoses: CaseDiagnosisItem[];
    signoffCards: SignoffCard[];
    options: {
        anesthesiaTypes: AnesthesiaOption[];
        positions: OptionItem[];
        skinIntegrities: OptionItem[];
        breathingPatterns: OptionItem[];
        oxygenDevices: OptionItem[];
        consciousnessList: OptionItem[];
        fluidKinds: FluidKindOption[];
        countVerifications: OptionItem[];
        specimenPurposes: OptionItem[];
        specimenPreservatives: OptionItem[];
        masterNursingDiagnoses: MasterDiagnosis[];
    };
    permissions: {
        canEditIntraop: boolean;
        canEditAnesthesia: boolean;
        canEditComplication: boolean;
        canAdvance: boolean;
    };
}

function nowWibLocalInput(): string {
    const now = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
}

const PRESSURE_LABELS: Array<{ key: string; label: string }> = [
    { key: 'ganjal_kepala_leher', label: 'Ganjal Kepala / Leher' },
    { key: 'bantalan_tumit_siku', label: 'Bantalan Tumit / Siku' },
    { key: 'safety_belt', label: 'Safety Belt / Sabuk Pengaman' },
    { key: 'armboard', label: 'Armboard (Penyangga Lengan)' },
];

const ANTISEPTIC_LABELS: Array<{ key: string; label: string }> = [
    { key: 'povidone_iodine', label: 'Povidone Iodine' },
    { key: 'alkohol_70', label: 'Alkohol 70%' },
    { key: 'chlorhexidine', label: 'Chlorhexidine' },
];

const WARMING_LABELS: Array<{ key: string; label: string }> = [
    { key: 'blanket_warmer', label: 'Blanket Warmer' },
    { key: 'fluid_warmer', label: 'Fluid Warmer' },
];

const DISCREPANCY_LABELS: Array<{ key: string; label: string }> = [
    { key: 'pencarian_ulang', label: 'Pencarian Ulang Area Operasi' },
    { key: 'lapor_operator', label: 'Lapor Dokter Operator' },
    { key: 'xray_lapangan', label: 'Foto X-Ray Lapangan Operasi' },
];

export default function IntraopPage({
    caseHeader,
    chronology,
    positionArea,
    vitalSigns,
    medications,
    fluids,
    nursingActions,
    instrumentCounts,
    specimenComplication,
    procedureDetails,
    caseNursingDiagnoses,
    signoffCards,
    options,
    permissions,
}: IntraopPageProps) {
    // 1 & 2. Kronologi & Info Tindakan
    const chronoForm = useForm({
        anesthesia_type_id: chronology.anesthesia_type_id ? String(chronology.anesthesia_type_id) : '',
        operation_plan_actual: chronology.operation_plan_actual,
        postop_diagnosis: chronology.postop_diagnosis,
        entered_or_at: chronology.entered_or_at,
        anesthesia_start_at: chronology.anesthesia_start_at,
        incision_at: chronology.incision_at,
        surgery_end_at: chronology.surgery_end_at,
        left_or_at: chronology.left_or_at,
        anesthesia_end_at: chronology.anesthesia_end_at,
    });

    // 3. Posisi & Area
    const posForm = useForm({
        position: positionArea.position,
        pressure_protection: [...positionArea.pressure_protection],
        skin_antiseptic: [...positionArea.skin_antiseptic],
        grounding_plate_site: positionArea.grounding_plate_site,
        skin_condition_pre: positionArea.skin_condition_pre,
        skin_condition_post: positionArea.skin_condition_post,
        draping_done: positionArea.draping_done,
        warming: [...positionArea.warming],
        room_temperature_c: positionArea.room_temperature_c === '' ? '' : String(positionArea.room_temperature_c),
    });

    // 4. Diagnosis Intra-Op
    const diagForm = useForm({
        nursing_diagnosis_id: '',
        custom_text: '',
        notes: '',
    });

    // 5. Tindakan Keperawatan
    const actionForm = useForm({
        performed_at: nowWibLocalInput(),
        performed_by_role: 'sirkuler',
        action: '',
        evaluation: '',
    });

    // 6a. TTV
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

    // 6b. Obat
    const medForm = useForm({
        given_at: nowWibLocalInput(),
        name: '',
        dose: '',
        route: 'IV',
        note: '',
    });

    // 6c. Cairan
    const fluidForm = useForm({
        recorded_at: nowWibLocalInput(),
        kind: 'kristaloid',
        volume_ml: '',
        note: '',
    });

    // 7. Hitung Instrumen
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

    // 8. Spesimen & Komplikasi
    const specForm = useForm({
        has_specimen: specimenComplication.has_specimen,
        specimen_type: specimenComplication.specimen_type,
        origin_organ: specimenComplication.origin_organ,
        purpose: specimenComplication.purpose,
        preservative: specimenComplication.preservative,
        has_complication: specimenComplication.has_complication,
        complication_description: specimenComplication.complication_description,
        complication_handling: specimenComplication.complication_handling,
    });

    // 9. Pengesahan 4 Pihak
    const signForm = useForm({
        role_label: '',
        password: '',
    });
    const [signingCard, setSigningCard] = useState<SignoffCard | null>(null);
    const [advancing, setAdvancing] = useState(false);

    const toggleArrayValue = (list: string[], value: string): string[] =>
        list.includes(value) ? list.filter((item) => item !== value) : [...list, value];

    const submitChronology = (e: FormEvent) => {
        e.preventDefault();
        chronoForm.transform((data) => ({
            ...data,
            expected_updated_at: chronology.updated_at ?? '',
        }));
        chronoForm.put(route('cases.intraop.chronology', caseHeader.id), { preserveScroll: true });
    };

    const submitPosition = (e: FormEvent) => {
        e.preventDefault();
        posForm.transform((data) => ({
            ...data,
            expected_updated_at: positionArea.updated_at ?? '',
        }));
        posForm.put(route('cases.intraop.position', caseHeader.id), { preserveScroll: true });
    };

    const submitDiagnosis = (e: FormEvent) => {
        e.preventDefault();
        diagForm.post(route('cases.intraop.add-diagnosis', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => diagForm.reset(),
        });
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

    const applyTemplates = () => {
        router.post(route('cases.intraop.actions.templates', caseHeader.id), {}, { preserveScroll: true });
    };

    const submitVital = (e: FormEvent) => {
        e.preventDefault();
        vitalForm.post(route('cases.intraop.vitals.store', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                vitalForm.reset('bp_systolic', 'bp_diastolic', 'pulse', 'temperature', 'respiratory_rate', 'spo2', 'oxygen_flow_lpm', 'notes');
                vitalForm.setData('recorded_at', nowWibLocalInput());
            },
        });
    };

    const submitMedication = (e: FormEvent) => {
        e.preventDefault();
        medForm.post(route('cases.intraop.medications.store', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                medForm.reset('name', 'dose', 'note');
                medForm.setData('given_at', nowWibLocalInput());
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

    const submitCounts = (e: FormEvent) => {
        e.preventDefault();
        countForm.put(route('cases.intraop.counts.update', caseHeader.id), { preserveScroll: true });
    };

    const submitSpecimenComp = (e: FormEvent) => {
        e.preventDefault();
        specForm.transform((data) => ({
            ...data,
            expected_updated_at: specimenComplication.updated_at ?? '',
        }));
        specForm.put(route('cases.intraop.specimen-complication', caseHeader.id), { preserveScroll: true });
    };

    const openSignDialog = (card: SignoffCard) => {
        setSigningCard(card);
        signForm.setData({ role_label: card.role_label, password: '' });
        signForm.clearErrors();
    };

    const submitSign = (e: FormEvent) => {
        e.preventDefault();
        signForm.post(route('cases.intraop.sign', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                signForm.reset();
                setSigningCard(null);
            },
        });
    };

    const handleAdvanceStatus = () => {
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

    const computedLiveCountStatus = (key: string): 'S' | 'TS' | '' => {
        const row = countForm.data.counts[key];
        if (!row || row.final_count.trim() === '') {
            return '';
        }
        const expected = (Number(row.initial_count) || 0) + (Number(row.additional_count) || 0);
        return expected === Number(row.final_count) ? 'S' : 'TS';
    };

    const anyCountMismatch = Object.keys(instrumentCounts.rows).some((k) => computedLiveCountStatus(k) === 'TS');

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="intra-op">
            <Head title={`Intra-Operasi - ${caseHeader.case_number}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                <ConcurrencyAlert
                    message={
                        (chronoForm.errors as Record<string, string | undefined>).concurrency ??
                        (posForm.errors as Record<string, string | undefined>).concurrency ??
                        (specForm.errors as Record<string, string | undefined>).concurrency
                    }
                />

                {/* Bar Status & Transisi */}
                {(caseHeader.status === 'siap_operasi' || caseHeader.status === 'intra_op') && caseHeader.next_transition && (
                    <Card>
                        <CardContent className="flex flex-col gap-4 pt-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="space-y-1.5 text-xs">
                                <div className="text-foreground text-sm font-semibold">Syarat Menuju {caseHeader.next_transition.target_label}:</div>
                                <div className="flex flex-wrap gap-x-4 gap-y-1">
                                    {caseHeader.next_transition.checklist.map((item) => (
                                        <span key={item.label} className="inline-flex items-center gap-1.5">
                                            <span className={cn('h-2 w-2 rounded-full', item.met ? 'bg-emerald-600' : 'bg-amber-500')} />
                                            <span className={item.met ? 'text-muted-foreground line-through' : 'text-foreground'}>{item.label}</span>
                                        </span>
                                    ))}
                                </div>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button asChild variant="outline" className="min-h-11">
                                    <Link href={route('cases.ssc.time-out', caseHeader.id)}>SSC Time Out</Link>
                                </Button>
                                <Button asChild variant="outline" className="min-h-11">
                                    <Link href={route('cases.ssc.sign-out', caseHeader.id)}>SSC Sign Out</Link>
                                </Button>
                                {permissions.canAdvance && (
                                    <Button
                                        type="button"
                                        className="min-h-11"
                                        disabled={!caseHeader.next_transition.can_advance || advancing}
                                        onClick={handleAdvanceStatus}
                                    >
                                        Lanjut ke {caseHeader.next_transition.target_label}
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {caseHeader.surgery_profile === 'bedah_saraf' ? (
                    <IntraopEdhView
                        caseHeader={caseHeader}
                        chronology={chronology}
                        positionArea={positionArea}
                        vitalSigns={vitalSigns}
                        fluids={fluids}
                        nursingActions={nursingActions}
                        instrumentCounts={instrumentCounts}
                        specimenComplication={specimenComplication}
                        procedureDetails={procedureDetails}
                        options={options}
                        permissions={permissions}
                        pressureLabels={PRESSURE_LABELS}
                        antisepticLabels={ANTISEPTIC_LABELS}
                        discrepancyLabels={DISCREPANCY_LABELS}
                        nowWibLocalInput={nowWibLocalInput}
                    />
                ) : caseHeader.surgery_profile === 'bedah_ortopedi' ? (
                    <IntraopOrthoView
                        caseHeader={caseHeader}
                        chronology={chronology}
                        positionArea={positionArea}
                        vitalSigns={vitalSigns}
                        fluids={fluids}
                        nursingActions={nursingActions}
                        instrumentCounts={instrumentCounts}
                        specimenComplication={specimenComplication}
                        procedureDetails={procedureDetails}
                        options={options}
                        permissions={permissions}
                        pressureLabels={PRESSURE_LABELS}
                        antisepticLabels={ANTISEPTIC_LABELS}
                        discrepancyLabels={DISCREPANCY_LABELS}
                        nowWibLocalInput={nowWibLocalInput}
                    />
                ) : (
                    <>
                        {/* 1 & 2. Identitas Tindakan & Kronologi Waktu */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Clock className="text-primary h-5 w-5" />1 & 2. Identitas Tindakan, Jenis Anestesi & Kronologi Waktu
                                    Intra-Operatif
                                </CardTitle>
                                <CardDescription>
                                    Urutan waktu harus menaik. Mulai Anestesi terkunci sampai SSC Sign In Selesai; Mulai Insisi terkunci sampai SSC
                                    Time Out Selesai.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitChronology} className="space-y-4">
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="anesthesia_type_id">Jenis Anestesi</Label>
                                            <Select
                                                value={chronoForm.data.anesthesia_type_id}
                                                onValueChange={(val) => chronoForm.setData('anesthesia_type_id', val)}
                                                disabled={!permissions.canEditIntraop}
                                            >
                                                <SelectTrigger id="anesthesia_type_id" className="min-h-11">
                                                    <SelectValue placeholder="Pilih jenis anestesi" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {options.anesthesiaTypes.map((a) => (
                                                        <SelectItem key={a.id} value={String(a.id)}>
                                                            {a.code} - {a.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="operation_plan_actual">Rencana / Actual Operasi</Label>
                                            <Input
                                                id="operation_plan_actual"
                                                value={chronoForm.data.operation_plan_actual}
                                                onChange={(e) => chronoForm.setData('operation_plan_actual', e.target.value)}
                                                disabled={!permissions.canEditIntraop}
                                                placeholder="Tindakan operasi yang sebenarnya dilakukan"
                                                className="min-h-11"
                                            />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="postop_diagnosis">Diagnosis Pasca-Operasi</Label>
                                            <Input
                                                id="postop_diagnosis"
                                                value={chronoForm.data.postop_diagnosis}
                                                onChange={(e) => chronoForm.setData('postop_diagnosis', e.target.value)}
                                                disabled={!permissions.canEditIntraop}
                                                placeholder="Diagnosis akhir setelah operasi"
                                                className="min-h-11"
                                            />
                                            <InputError message={chronoForm.errors.postop_diagnosis} />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
                                        {[
                                            { key: 'entered_or_at', label: '1. Masuk OK', locked: false, lockReason: '' },
                                            {
                                                key: 'anesthesia_start_at',
                                                label: '2. Mulai Anestesi',
                                                locked: !chronology.sign_in_completed,
                                                lockReason: 'Selesaikan SSC Sign In dulu',
                                            },
                                            {
                                                key: 'incision_at',
                                                label: '3. Mulai Insisi',
                                                locked: !chronology.time_out_completed,
                                                lockReason: 'Selesaikan SSC Time Out dulu',
                                            },
                                            { key: 'surgery_end_at', label: '4. Selesai Operasi', locked: false, lockReason: '' },
                                            { key: 'left_or_at', label: '5. Keluar OK', locked: false, lockReason: '' },
                                            { key: 'anesthesia_end_at', label: '6. Selesai Anestesi', locked: false, lockReason: '' },
                                        ].map((item) => {
                                            const k = item.key as
                                                | 'entered_or_at'
                                                | 'anesthesia_start_at'
                                                | 'incision_at'
                                                | 'surgery_end_at'
                                                | 'left_or_at'
                                                | 'anesthesia_end_at';
                                            return (
                                                <div key={item.key} className="space-y-1.5 rounded-lg border p-3">
                                                    <div className="flex items-center justify-between gap-1">
                                                        <Label htmlFor={item.key} className="text-xs font-semibold">
                                                            {item.label}
                                                        </Label>
                                                        {item.locked && <Lock className="h-3.5 w-3.5 text-amber-600" />}
                                                    </div>
                                                    <Input
                                                        id={item.key}
                                                        type="datetime-local"
                                                        value={chronoForm.data[k]}
                                                        onChange={(e) => chronoForm.setData(k, e.target.value)}
                                                        disabled={!permissions.canEditIntraop || item.locked}
                                                        className="min-h-11 text-xs"
                                                    />
                                                    {permissions.canEditIntraop && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            className="w-full text-xs"
                                                            disabled={item.locked}
                                                            onClick={() => chronoForm.setData(k, nowWibLocalInput())}
                                                        >
                                                            Catat Sekarang
                                                        </Button>
                                                    )}
                                                    {item.locked && <p className="text-[11px] text-amber-700">{item.lockReason}</p>}
                                                    <InputError message={chronoForm.errors[k]} />
                                                </div>
                                            );
                                        })}
                                    </div>

                                    {permissions.canEditIntraop && (
                                        <div className="flex justify-end">
                                            <Button type="submit" className="min-h-11" disabled={chronoForm.processing}>
                                                <Save className="mr-2 h-4 w-4" />
                                                Simpan Kronologi Waktu
                                            </Button>
                                        </div>
                                    )}
                                </form>
                            </CardContent>
                        </Card>

                        {/* 3. Persiapan Posisi & Area Operasi */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Stethoscope className="text-primary h-5 w-5" />
                                    3. Persiapan Posisi & Area Operasi (KF-5 & KF-30)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitPosition} className="space-y-5">
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="position">Posisi Pembedahan</Label>
                                            <Select
                                                value={posForm.data.position}
                                                onValueChange={(val) => posForm.setData('position', val)}
                                                disabled={!permissions.canEditIntraop}
                                            >
                                                <SelectTrigger id="position" className="min-h-11">
                                                    <SelectValue placeholder="Pilih posisi pasien" />
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
                                            <Label htmlFor="grounding_plate_site">Lokasi Plate Grounding (ESU)</Label>
                                            <Input
                                                id="grounding_plate_site"
                                                value={posForm.data.grounding_plate_site}
                                                onChange={(e) => posForm.setData('grounding_plate_site', e.target.value)}
                                                disabled={!permissions.canEditIntraop}
                                                placeholder="mis. Paha kanan lateral"
                                                className="min-h-11"
                                            />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="room_temperature_c">Suhu Kamar Operasi (°C, standar 19–24)</Label>
                                            <Input
                                                id="room_temperature_c"
                                                type="number"
                                                step="0.1"
                                                value={posForm.data.room_temperature_c}
                                                onChange={(e) => posForm.setData('room_temperature_c', e.target.value)}
                                                disabled={!permissions.canEditIntraop}
                                                placeholder="mis. 21.5"
                                                className="min-h-11"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="skin_condition_pre">Kondisi Kulit Plate Pre-Op</Label>
                                            <Select
                                                value={posForm.data.skin_condition_pre}
                                                onValueChange={(val) => posForm.setData('skin_condition_pre', val)}
                                                disabled={!permissions.canEditIntraop}
                                            >
                                                <SelectTrigger id="skin_condition_pre" className="min-h-11">
                                                    <SelectValue placeholder="Pilih kondisi kulit pre" />
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
                                            <Label htmlFor="skin_condition_post">Kondisi Kulit Plate Post-Op</Label>
                                            <Select
                                                value={posForm.data.skin_condition_post}
                                                onValueChange={(val) => posForm.setData('skin_condition_post', val)}
                                                disabled={!permissions.canEditIntraop}
                                            >
                                                <SelectTrigger id="skin_condition_post" className="min-h-11">
                                                    <SelectValue placeholder="Pilih kondisi kulit post" />
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

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div className="space-y-2 rounded-lg border p-3">
                                            <Label className="text-xs font-semibold">Proteksi Area Penekanan</Label>
                                            {PRESSURE_LABELS.map((opt) => (
                                                <label key={opt.key} className="flex cursor-pointer items-center gap-2 text-sm">
                                                    <Checkbox
                                                        checked={posForm.data.pressure_protection.includes(opt.key)}
                                                        disabled={!permissions.canEditIntraop}
                                                        onCheckedChange={() =>
                                                            posForm.setData(
                                                                'pressure_protection',
                                                                toggleArrayValue(posForm.data.pressure_protection, opt.key),
                                                            )
                                                        }
                                                    />
                                                    <span>{opt.label}</span>
                                                </label>
                                            ))}
                                        </div>

                                        <div className="space-y-2 rounded-lg border p-3">
                                            <Label className="text-xs font-semibold">Persiapan Kulit (Antiseptik)</Label>
                                            {ANTISEPTIC_LABELS.map((opt) => (
                                                <label key={opt.key} className="flex cursor-pointer items-center gap-2 text-sm">
                                                    <Checkbox
                                                        checked={posForm.data.skin_antiseptic.includes(opt.key)}
                                                        disabled={!permissions.canEditIntraop}
                                                        onCheckedChange={() =>
                                                            posForm.setData(
                                                                'skin_antiseptic',
                                                                toggleArrayValue(posForm.data.skin_antiseptic, opt.key),
                                                            )
                                                        }
                                                    />
                                                    <span>{opt.label}</span>
                                                </label>
                                            ))}
                                        </div>

                                        <div className="space-y-2 rounded-lg border p-3">
                                            <Label className="text-xs font-semibold">Warming & Draping</Label>
                                            {WARMING_LABELS.map((opt) => (
                                                <label key={opt.key} className="flex cursor-pointer items-center gap-2 text-sm">
                                                    <Checkbox
                                                        checked={posForm.data.warming.includes(opt.key)}
                                                        disabled={!permissions.canEditIntraop}
                                                        onCheckedChange={() =>
                                                            posForm.setData('warming', toggleArrayValue(posForm.data.warming, opt.key))
                                                        }
                                                    />
                                                    <span>{opt.label}</span>
                                                </label>
                                            ))}
                                            <label className="flex cursor-pointer items-center gap-2 border-t pt-2 text-sm font-medium">
                                                <Checkbox
                                                    checked={posForm.data.draping_done}
                                                    disabled={!permissions.canEditIntraop}
                                                    onCheckedChange={(c) => posForm.setData('draping_done', c === true)}
                                                />
                                                <span>Draping Steril Terpasang</span>
                                            </label>
                                        </div>
                                    </div>

                                    {permissions.canEditIntraop && (
                                        <div className="flex justify-end">
                                            <Button type="submit" className="min-h-11" disabled={posForm.processing}>
                                                <Save className="mr-2 h-4 w-4" />
                                                Simpan Posisi & Area
                                            </Button>
                                        </div>
                                    )}
                                </form>
                            </CardContent>
                        </Card>

                        {/* 4. Diagnosis Keperawatan Intra-Op (SDKI/SLKI/SIKI) */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ListChecks className="text-primary h-5 w-5" />
                                    4. Diagnosis Keperawatan Intra-Operatif + SLKI/SIKI (KF-31 & KF-35)
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {permissions.canEditIntraop && (
                                    <form onSubmit={submitDiagnosis} className="grid grid-cols-1 gap-3 rounded-lg border p-3 md:grid-cols-3">
                                        <Select
                                            value={diagForm.data.nursing_diagnosis_id}
                                            onValueChange={(val) => diagForm.setData('nursing_diagnosis_id', val)}
                                        >
                                            <SelectTrigger className="min-h-11">
                                                <SelectValue placeholder="Pilih diagnosis intra-op (master)" />
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
                                            placeholder="Atau tuliskan diagnosis lainnya (opsional)"
                                            className="min-h-11"
                                        />
                                        <Button type="submit" className="min-h-11" disabled={diagForm.processing}>
                                            <Plus className="mr-2 h-4 w-4" />
                                            Tambah Diagnosis
                                        </Button>
                                    </form>
                                )}

                                {caseNursingDiagnoses.length === 0 ? (
                                    <p className="text-muted-foreground text-center text-sm">Belum ada diagnosis keperawatan intra-op ditambahkan.</p>
                                ) : (
                                    <div className="space-y-3">
                                        {caseNursingDiagnoses.map((cd) => (
                                            <div key={cd.id} className="space-y-2 rounded-lg border p-4">
                                                <div className="flex items-center justify-between gap-2">
                                                    <div className="font-semibold">
                                                        {cd.code ? `${cd.code} - ` : ''}
                                                        {cd.name}
                                                    </div>
                                                    {permissions.canEditIntraop && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                router.delete(route('cases.intraop.remove-diagnosis', [caseHeader.id, cd.id]), {
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
                                                        {cd.slki
                                                            .map((c) => `${c.criterion_text} (${c.expectation}, target ${c.target_score})`)
                                                            .join(' • ')}
                                                    </div>
                                                )}
                                                {cd.siki.map((int) => (
                                                    <div key={int.id} className="space-y-1 pt-1">
                                                        <div className="text-foreground text-xs font-semibold">{int.name}</div>
                                                        <div className="grid grid-cols-1 gap-1.5 md:grid-cols-2">
                                                            {int.items.map((item) => (
                                                                <label
                                                                    key={item.siki_item_id}
                                                                    className="hover:bg-muted/40 flex cursor-pointer items-start gap-2 rounded border px-2.5 py-1.5 text-xs"
                                                                >
                                                                    <Checkbox
                                                                        checked={item.is_done}
                                                                        disabled={!permissions.canEditIntraop || item.id === null}
                                                                        onCheckedChange={() => {
                                                                            if (item.id !== null) {
                                                                                router.post(
                                                                                    route('cases.intraop.toggle-intervention', [
                                                                                        caseHeader.id,
                                                                                        item.id,
                                                                                    ]),
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
                                                                            <span className="ml-1 font-medium text-emerald-700">
                                                                                ({item.done_at})
                                                                            </span>
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

                        {/* 5. Tindakan Keperawatan (Timeline + Template) */}
                        <Card>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <CardTitle className="text-base">5. Tindakan Keperawatan Intra-Operatif (Timeline - KF-12)</CardTitle>
                                        <CardDescription>
                                            Catat tindakan perawat sirkuler/instrumen beserta evaluasi dan paraf otomatis pembuat.
                                        </CardDescription>
                                    </div>
                                    {permissions.canEditIntraop && (
                                        <Button type="button" variant="outline" className="min-h-11" onClick={applyTemplates}>
                                            <ClipboardList className="mr-2 h-4 w-4" aria-hidden="true" />
                                            Tambah dari Template (7 Item PDF)
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {permissions.canEditIntraop && (
                                    <form onSubmit={submitNursingAction} className="grid grid-cols-1 gap-3 rounded-lg border p-3 md:grid-cols-12">
                                        <div className="md:col-span-3">
                                            <Label htmlFor="action_performed_at" className="sr-only">
                                                Waktu tindakan
                                            </Label>
                                            <Input
                                                id="action_performed_at"
                                                type="datetime-local"
                                                value={actionForm.data.performed_at}
                                                onChange={(e) => actionForm.setData('performed_at', e.target.value)}
                                                className="min-h-11 text-xs"
                                            />
                                        </div>
                                        <div className="md:col-span-2">
                                            <Label htmlFor="action_performed_by_role" className="sr-only">
                                                Peran perawat pelaksana
                                            </Label>
                                            <Select
                                                value={actionForm.data.performed_by_role}
                                                onValueChange={(v) => actionForm.setData('performed_by_role', v)}
                                            >
                                                <SelectTrigger id="action_performed_by_role" className="min-h-11 text-xs">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="sirkuler">Sirkuler</SelectItem>
                                                    <SelectItem value="instrumen">Instrumen</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="md:col-span-3">
                                            <Label htmlFor="action_text" className="sr-only">
                                                Tindakan keperawatan
                                            </Label>
                                            <Input
                                                id="action_text"
                                                value={actionForm.data.action}
                                                onChange={(e) => actionForm.setData('action', e.target.value)}
                                                placeholder="Tindakan keperawatan *"
                                                className="min-h-11 text-xs"
                                            />
                                            <InputError message={actionForm.errors.action} />
                                        </div>
                                        <div className="md:col-span-3">
                                            <Label htmlFor="action_evaluation" className="sr-only">
                                                Evaluasi atau respons pasien
                                            </Label>
                                            <Input
                                                id="action_evaluation"
                                                value={actionForm.data.evaluation}
                                                onChange={(e) => actionForm.setData('evaluation', e.target.value)}
                                                placeholder="Evaluasi / respons pasien"
                                                className="min-h-11 text-xs"
                                            />
                                        </div>
                                        <div className="md:col-span-1">
                                            <Button
                                                type="submit"
                                                aria-label="Tambah tindakan keperawatan"
                                                className="min-h-11 w-full"
                                                disabled={actionForm.processing}
                                            >
                                                <Plus className="h-4 w-4" aria-hidden="true" />
                                            </Button>
                                        </div>
                                    </form>
                                )}

                                <div className="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-[80px]">Waktu</TableHead>
                                                <TableHead className="w-[130px]">Peran</TableHead>
                                                <TableHead>Tindakan Keperawatan</TableHead>
                                                <TableHead>Evaluasi / Respons</TableHead>
                                                <TableHead className="w-[140px]">Paraf</TableHead>
                                                {permissions.canEditIntraop && <TableHead className="w-[50px]" />}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {nursingActions.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={6} className="text-muted-foreground py-6 text-center text-sm">
                                                        Belum ada catatan tindakan keperawatan intra-op.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                nursingActions.map((act) => (
                                                    <TableRow key={act.id}>
                                                        <TableCell className="font-mono text-xs">{act.performed_at}</TableCell>
                                                        <TableCell>
                                                            <Badge variant="outline" className="text-xs">
                                                                {act.performed_by_role_label}
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-sm">{act.action}</TableCell>
                                                        <TableCell className="text-muted-foreground text-sm">{act.evaluation}</TableCell>
                                                        <TableCell className="text-xs">{act.creator_name}</TableCell>
                                                        {permissions.canEditIntraop && (
                                                            <TableCell>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    aria-label="Hapus tindakan keperawatan"
                                                                    onClick={() =>
                                                                        router.delete(
                                                                            route('cases.intraop.actions.destroy', [caseHeader.id, act.id]),
                                                                            {
                                                                                preserveScroll: true,
                                                                            },
                                                                        )
                                                                    }
                                                                >
                                                                    <Trash2 className="text-destructive h-4 w-4" aria-hidden="true" />
                                                                </Button>
                                                            </TableCell>
                                                        )}
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>

                        {/* 6. Pemantauan TTV + Grafik, Obat, dan Cairan */}
                        <div className="space-y-6">
                            <VitalSignChart data={vitalSigns} />

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Activity className="text-primary h-5 w-5" />
                                        6A. Tabel Pemantauan TTV & Oksigenasi Intra-Op (KF-7 & KF-8)
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {permissions.canEditIntraop && (
                                        <form onSubmit={submitVital} className="space-y-3 rounded-lg border p-3">
                                            <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-7">
                                                <div>
                                                    <Label className="text-xs">Waktu *</Label>
                                                    <Input
                                                        type="datetime-local"
                                                        value={vitalForm.data.recorded_at}
                                                        onChange={(e) => vitalForm.setData('recorded_at', e.target.value)}
                                                        className="min-h-10 text-xs"
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">Sistolik</Label>
                                                    <Input
                                                        type="number"
                                                        value={vitalForm.data.bp_systolic}
                                                        onChange={(e) => vitalForm.setData('bp_systolic', e.target.value)}
                                                        placeholder="120"
                                                        className="min-h-10"
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">Diastolik</Label>
                                                    <Input
                                                        type="number"
                                                        value={vitalForm.data.bp_diastolic}
                                                        onChange={(e) => vitalForm.setData('bp_diastolic', e.target.value)}
                                                        placeholder="80"
                                                        className="min-h-10"
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">Nadi</Label>
                                                    <Input
                                                        type="number"
                                                        value={vitalForm.data.pulse}
                                                        onChange={(e) => vitalForm.setData('pulse', e.target.value)}
                                                        placeholder="80"
                                                        className="min-h-10"
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">Suhu (°C)</Label>
                                                    <Input
                                                        type="number"
                                                        step="0.1"
                                                        value={vitalForm.data.temperature}
                                                        onChange={(e) => vitalForm.setData('temperature', e.target.value)}
                                                        placeholder="36.5"
                                                        className="min-h-10"
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">RR</Label>
                                                    <Input
                                                        type="number"
                                                        value={vitalForm.data.respiratory_rate}
                                                        onChange={(e) => vitalForm.setData('respiratory_rate', e.target.value)}
                                                        placeholder="16"
                                                        className="min-h-10"
                                                    />
                                                </div>
                                                <div>
                                                    <Label className="text-xs">SpO₂ (%)</Label>
                                                    <Input
                                                        type="number"
                                                        value={vitalForm.data.spo2}
                                                        onChange={(e) => vitalForm.setData('spo2', e.target.value)}
                                                        placeholder="99"
                                                        className="min-h-10"
                                                    />
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
                                                <div>
                                                    <Label className="text-xs">Pola Napas *</Label>
                                                    <Select
                                                        value={vitalForm.data.breathing_pattern}
                                                        onValueChange={(v) => vitalForm.setData('breathing_pattern', v)}
                                                    >
                                                        <SelectTrigger className="min-h-10">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {options.breathingPatterns.map((b) => (
                                                                <SelectItem key={b.value} value={b.value}>
                                                                    {b.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                <div>
                                                    <Label className="text-xs">Alat Bantu O₂ *</Label>
                                                    <Select
                                                        value={vitalForm.data.oxygen_device}
                                                        onValueChange={(v) => vitalForm.setData('oxygen_device', v)}
                                                    >
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
                                                        Aliran O₂ (LPM 1–15) {vitalForm.data.oxygen_device !== 'tanpa_oksigen' ? '*' : ''}
                                                    </Label>
                                                    <Input
                                                        type="number"
                                                        min={1}
                                                        max={15}
                                                        disabled={vitalForm.data.oxygen_device === 'tanpa_oksigen'}
                                                        value={vitalForm.data.oxygen_flow_lpm}
                                                        onChange={(e) => vitalForm.setData('oxygen_flow_lpm', e.target.value)}
                                                        placeholder="1–15"
                                                        className="min-h-10"
                                                    />
                                                    <InputError message={vitalForm.errors.oxygen_flow_lpm} />
                                                </div>
                                                <div className="flex items-end">
                                                    <Button type="submit" className="min-h-10 w-full" disabled={vitalForm.processing}>
                                                        <Plus className="mr-2 h-4 w-4" />
                                                        Catat TTV
                                                    </Button>
                                                </div>
                                            </div>
                                        </form>
                                    )}

                                    <div className="overflow-x-auto rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Waktu</TableHead>
                                                    <TableHead>TD (mmHg)</TableHead>
                                                    <TableHead>Nadi</TableHead>
                                                    <TableHead>Suhu</TableHead>
                                                    <TableHead>RR</TableHead>
                                                    <TableHead>SpO₂</TableHead>
                                                    <TableHead>Pola Napas</TableHead>
                                                    <TableHead>Oksigen</TableHead>
                                                    {permissions.canEditIntraop && <TableHead className="w-[50px]" />}
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {vitalSigns.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell colSpan={9} className="text-muted-foreground py-6 text-center text-sm">
                                                            Belum ada data pemantauan TTV intra-op.
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    vitalSigns.map((v) => (
                                                        <TableRow key={v.id}>
                                                            <TableCell className="font-mono text-xs">{v.recorded_at}</TableCell>
                                                            <TableCell>
                                                                {v.bp_systolic ?? '-'}/{v.bp_diastolic ?? '-'}
                                                            </TableCell>
                                                            <TableCell>{v.pulse ?? '-'}</TableCell>
                                                            <TableCell>{v.temperature ?? '-'}</TableCell>
                                                            <TableCell>{v.respiratory_rate ?? '-'}</TableCell>
                                                            <TableCell>{v.spo2 ? `${v.spo2}%` : '-'}</TableCell>
                                                            <TableCell className="text-xs">{v.breathing_pattern}</TableCell>
                                                            <TableCell className="text-xs">
                                                                {v.oxygen_device}
                                                                {v.oxygen_flow_lpm ? ` (${v.oxygen_flow_lpm} LPM)` : ''}
                                                            </TableCell>
                                                            {permissions.canEditIntraop && (
                                                                <TableCell>
                                                                    <Button
                                                                        type="button"
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        aria-label="Hapus pemantauan TTV"
                                                                        onClick={() =>
                                                                            router.delete(
                                                                                route('cases.intraop.vitals.destroy', [caseHeader.id, v.id]),
                                                                                {
                                                                                    preserveScroll: true,
                                                                                },
                                                                            )
                                                                        }
                                                                    >
                                                                        <Trash2 className="text-destructive h-4 w-4" aria-hidden="true" />
                                                                    </Button>
                                                                </TableCell>
                                                            )}
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* 6B Obat & 6C Cairan */}
                            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <Pill className="text-primary h-5 w-5" />
                                            6B. Pemberian Obat Intra-Op (KF-6)
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {permissions.canEditAnesthesia && (
                                            <form onSubmit={submitMedication} className="grid grid-cols-1 gap-2 rounded-lg border p-3 sm:grid-cols-2">
                                                <Input
                                                    value={medForm.data.name}
                                                    onChange={(e) => medForm.setData('name', e.target.value)}
                                                    placeholder="Nama obat *"
                                                    className="min-h-10"
                                                />
                                                <Input
                                                    value={medForm.data.dose}
                                                    onChange={(e) => medForm.setData('dose', e.target.value)}
                                                    placeholder="Dosis (mis. 100 mg) *"
                                                    className="min-h-10"
                                                />
                                                <Input
                                                    value={medForm.data.route}
                                                    onChange={(e) => medForm.setData('route', e.target.value)}
                                                    placeholder="Rute (IV/IM/Inhalasi)"
                                                    className="min-h-10"
                                                />
                                                <Input
                                                    type="datetime-local"
                                                    value={medForm.data.given_at}
                                                    onChange={(e) => medForm.setData('given_at', e.target.value)}
                                                    className="min-h-10 text-xs"
                                                />
                                                <Button type="submit" className="min-h-10 sm:col-span-2" disabled={medForm.processing}>
                                                    <Plus className="mr-2 h-4 w-4" />
                                                    Tambah Obat
                                                </Button>
                                            </form>
                                        )}

                                        <div className="overflow-x-auto rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Waktu</TableHead>
                                                        <TableHead>Obat & Dosis</TableHead>
                                                        <TableHead>Rute</TableHead>
                                                        {permissions.canEditAnesthesia && <TableHead className="w-[44px]" />}
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {medications.length === 0 ? (
                                                        <TableRow>
                                                            <TableCell colSpan={4} className="text-muted-foreground py-4 text-center text-xs">
                                                                Belum ada obat tercatat.
                                                            </TableCell>
                                                        </TableRow>
                                                    ) : (
                                                        medications.map((m) => (
                                                            <TableRow key={m.id}>
                                                                <TableCell className="text-xs">{m.given_at}</TableCell>
                                                                <TableCell className="text-sm font-medium">
                                                                    {m.name} <span className="text-muted-foreground">({m.dose})</span>
                                                                </TableCell>
                                                                <TableCell className="text-xs">{m.route}</TableCell>
                                                                {permissions.canEditAnesthesia && (
                                                                    <TableCell>
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            aria-label="Hapus obat intra-operasi"
                                                                            onClick={() =>
                                                                                router.delete(
                                                                                    route('cases.intraop.medications.destroy', [caseHeader.id, m.id]),
                                                                                    { preserveScroll: true },
                                                                                )
                                                                            }
                                                                        >
                                                                            <Trash2 className="text-destructive h-4 w-4" aria-hidden="true" />
                                                                        </Button>
                                                                    </TableCell>
                                                                )}
                                                            </TableRow>
                                                        ))
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <Droplets className="text-primary h-5 w-5" />
                                            6C. Cairan Masuk & Keluar Intra-Op (KF-9..11)
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-3 gap-2 text-center">
                                            <div className="rounded-lg border bg-blue-50/60 p-2 dark:bg-blue-950/30">
                                                <div className="text-muted-foreground text-[11px]">Total Intake</div>
                                                <div className="text-base font-bold">{fluids.total_intake_ml} ml</div>
                                            </div>
                                            <div className="rounded-lg border bg-amber-50/60 p-2 dark:bg-amber-950/30">
                                                <div className="text-muted-foreground text-[11px]">Total Output</div>
                                                <div className="text-base font-bold">{fluids.total_output_ml} ml</div>
                                            </div>
                                            <div className="rounded-lg border p-2">
                                                <div className="text-muted-foreground text-[11px]">Balans</div>
                                                <div className="text-base font-bold">
                                                    {fluids.balance_ml >= 0 ? `+${fluids.balance_ml}` : fluids.balance_ml} ml
                                                </div>
                                            </div>
                                        </div>

                                        {permissions.canEditIntraop && (
                                            <form onSubmit={submitFluid} className="grid grid-cols-1 gap-2 rounded-lg border p-3 sm:grid-cols-3">
                                                <Select value={fluidForm.data.kind} onValueChange={(v) => fluidForm.setData('kind', v)}>
                                                    <SelectTrigger className="min-h-10 sm:col-span-2">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {options.fluidKinds.map((k) => (
                                                            <SelectItem key={k.value} value={k.value}>
                                                                [{k.direction === 'intake' ? 'Masuk' : 'Keluar'}] {k.label}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    value={fluidForm.data.volume_ml}
                                                    onChange={(e) => fluidForm.setData('volume_ml', e.target.value)}
                                                    placeholder="Volume (ml) *"
                                                    className="min-h-10"
                                                />
                                                <Button type="submit" className="min-h-10 sm:col-span-3" disabled={fluidForm.processing}>
                                                    <Plus className="mr-2 h-4 w-4" />
                                                    Catat Cairan
                                                </Button>
                                            </form>
                                        )}

                                        <div className="overflow-x-auto rounded-md border">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>Jenis</TableHead>
                                                        <TableHead>Arah</TableHead>
                                                        <TableHead className="text-right">Volume</TableHead>
                                                        {permissions.canEditIntraop && <TableHead className="w-[44px]" />}
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {fluids.items.length === 0 ? (
                                                        <TableRow>
                                                            <TableCell colSpan={4} className="text-muted-foreground py-4 text-center text-xs">
                                                                Belum ada catatan cairan.
                                                            </TableCell>
                                                        </TableRow>
                                                    ) : (
                                                        fluids.items.map((f) => (
                                                            <TableRow key={f.id}>
                                                                <TableCell className="text-xs font-medium">{f.kind_label}</TableCell>
                                                                <TableCell>
                                                                    <Badge
                                                                        variant={f.direction === 'intake' ? 'secondary' : 'outline'}
                                                                        className="text-[10px]"
                                                                    >
                                                                        {f.direction === 'intake' ? 'Masuk' : 'Keluar'}
                                                                    </Badge>
                                                                </TableCell>
                                                                <TableCell className="text-right font-mono text-xs">{f.volume_ml} ml</TableCell>
                                                                {permissions.canEditIntraop && (
                                                                    <TableCell>
                                                                        <Button
                                                                            type="button"
                                                                            variant="ghost"
                                                                            size="icon"
                                                                            aria-label="Hapus catatan cairan"
                                                                            onClick={() =>
                                                                                router.delete(
                                                                                    route('cases.intraop.fluids.destroy', [caseHeader.id, f.id]),
                                                                                    {
                                                                                        preserveScroll: true,
                                                                                    },
                                                                                )
                                                                            }
                                                                        >
                                                                            <Trash2 className="text-destructive h-4 w-4" aria-hidden="true" />
                                                                        </Button>
                                                                    </TableCell>
                                                                )}
                                                            </TableRow>
                                                        ))
                                                    )}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        {/* 7. Hitung Instrumen, Kasa, dan Jarum */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <FileCheck2 className="text-primary h-5 w-5" />
                                    7. Penghitungan Instrumen, Kasa, dan Jarum (Safety Instrument Count - KF-32)
                                </CardTitle>
                                <CardDescription>
                                    Status otomatis <strong>S (Sesuai)</strong> jika Hitungan Akhir = Awal + Tambahan; selain itu otomatis{' '}
                                    <strong>TS (Tidak Sesuai)</strong> dan wajib tindak lanjut SOP.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitCounts} className="space-y-4">
                                    <div className="overflow-x-auto rounded-md border">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Item Operasi</TableHead>
                                                    <TableHead className="w-[130px]">Hitungan Awal</TableHead>
                                                    <TableHead className="w-[130px]">Tambahan</TableHead>
                                                    <TableHead className="w-[130px]">Hitungan Akhir</TableHead>
                                                    <TableHead className="w-[120px] text-center">Status (Auto)</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {Object.values(instrumentCounts.rows).map((row) => {
                                                    const liveStatus = computedLiveCountStatus(row.item);
                                                    const current = countForm.data.counts[row.item] ?? {
                                                        initial_count: '0',
                                                        additional_count: '0',
                                                        final_count: '',
                                                    };
                                                    const setField = (field: 'initial_count' | 'additional_count' | 'final_count', val: string) => {
                                                        countForm.setData('counts', {
                                                            ...countForm.data.counts,
                                                            [row.item]: { ...current, [field]: val },
                                                        });
                                                    };

                                                    return (
                                                        <TableRow
                                                            key={row.item}
                                                            className={cn(liveStatus === 'TS' && 'bg-red-50/70 dark:bg-red-950/30')}
                                                        >
                                                            <TableCell className="font-medium">{row.label}</TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    type="number"
                                                                    min={0}
                                                                    disabled={!permissions.canEditIntraop}
                                                                    value={current.initial_count}
                                                                    onChange={(e) => setField('initial_count', e.target.value)}
                                                                    className="min-h-10"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    type="number"
                                                                    min={0}
                                                                    disabled={!permissions.canEditIntraop}
                                                                    value={current.additional_count}
                                                                    onChange={(e) => setField('additional_count', e.target.value)}
                                                                    className="min-h-10"
                                                                />
                                                            </TableCell>
                                                            <TableCell>
                                                                <Input
                                                                    type="number"
                                                                    min={0}
                                                                    disabled={!permissions.canEditIntraop}
                                                                    value={current.final_count}
                                                                    onChange={(e) => setField('final_count', e.target.value)}
                                                                    placeholder="Belum"
                                                                    className="min-h-10"
                                                                />
                                                            </TableCell>
                                                            <TableCell className="text-center">
                                                                {liveStatus === 'S' && (
                                                                    <Badge className="bg-emerald-600 text-white">S (Sesuai)</Badge>
                                                                )}
                                                                {liveStatus === 'TS' && <Badge variant="destructive">TS (Tidak Sesuai)</Badge>}
                                                                {liveStatus === '' && <Badge variant="outline">Belum</Badge>}
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })}
                                            </TableBody>
                                        </Table>
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="count_verification">Verifikasi Akhir Hitungan</Label>
                                            <Select
                                                value={countForm.data.count_verification}
                                                onValueChange={(v) => countForm.setData('count_verification', v)}
                                                disabled={!permissions.canEditIntraop}
                                            >
                                                <SelectTrigger id="count_verification" className="min-h-11">
                                                    <SelectValue placeholder="Pilih hasil verifikasi akhir" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {options.countVerifications.map((opt) => (
                                                        <SelectItem key={opt.value} value={opt.value}>
                                                            {opt.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError message={countForm.errors.count_verification} />
                                        </div>

                                        <div className="space-y-2 rounded-lg border p-3">
                                            <Label className="text-xs font-semibold">
                                                Tindakan bila tidak sesuai / selisih{' '}
                                                {(anyCountMismatch || countForm.data.count_verification === 'tidak_sesuai') && (
                                                    <span className="text-destructive">* (Wajib)</span>
                                                )}
                                            </Label>
                                            {DISCREPANCY_LABELS.map((opt) => (
                                                <label key={opt.key} className="flex cursor-pointer items-center gap-2 text-sm">
                                                    <Checkbox
                                                        checked={countForm.data.count_discrepancy_actions.includes(opt.key)}
                                                        disabled={!permissions.canEditIntraop}
                                                        onCheckedChange={() =>
                                                            countForm.setData(
                                                                'count_discrepancy_actions',
                                                                toggleArrayValue(countForm.data.count_discrepancy_actions, opt.key),
                                                            )
                                                        }
                                                    />
                                                    <span>{opt.label}</span>
                                                </label>
                                            ))}
                                            <InputError message={countForm.errors.count_discrepancy_actions} />
                                        </div>
                                    </div>

                                    {permissions.canEditIntraop && (
                                        <div className="flex justify-end">
                                            <Button type="submit" className="min-h-11" disabled={countForm.processing}>
                                                <Save className="mr-2 h-4 w-4" />
                                                Simpan Hitung Instrumen
                                            </Button>
                                        </div>
                                    )}
                                </form>
                            </CardContent>
                        </Card>

                        {/* 8. Spesimen & Kejadian Khusus / Komplikasi */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <FlaskConical className="text-primary h-5 w-5" />
                                    8. Pengelolaan Spesimen & Catatan Kejadian Khusus / Komplikasi (KF-33)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={submitSpecimenComp} className="space-y-5">
                                    <div className="space-y-3 rounded-lg border p-4">
                                        <label className="flex cursor-pointer items-center gap-2.5 text-sm font-semibold">
                                            <Checkbox
                                                checked={specForm.data.has_specimen}
                                                disabled={!permissions.canEditIntraop}
                                                onCheckedChange={(c) => specForm.setData('has_specimen', c === true)}
                                            />
                                            <span>Ada Spesimen Jaringan / Cairan Operasi</span>
                                        </label>

                                        {specForm.data.has_specimen && (
                                            <div className="grid grid-cols-1 gap-4 pt-2 md:grid-cols-2">
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="specimen_type">Jenis Spesimen / Jaringan *</Label>
                                                    <Input
                                                        id="specimen_type"
                                                        value={specForm.data.specimen_type}
                                                        onChange={(e) => specForm.setData('specimen_type', e.target.value)}
                                                        disabled={!permissions.canEditIntraop}
                                                        className="min-h-11"
                                                    />
                                                    <InputError message={specForm.errors.specimen_type} />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="origin_organ">Asal Jaringan / Organ *</Label>
                                                    <Input
                                                        id="origin_organ"
                                                        value={specForm.data.origin_organ}
                                                        onChange={(e) => specForm.setData('origin_organ', e.target.value)}
                                                        disabled={!permissions.canEditIntraop}
                                                        className="min-h-11"
                                                    />
                                                    <InputError message={specForm.errors.origin_organ} />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="purpose">Tujuan Pemeriksaan *</Label>
                                                    <Select
                                                        value={specForm.data.purpose}
                                                        onValueChange={(v) => specForm.setData('purpose', v)}
                                                        disabled={!permissions.canEditIntraop}
                                                    >
                                                        <SelectTrigger id="purpose" className="min-h-11">
                                                            <SelectValue placeholder="Pilih tujuan pemeriksaan" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {options.specimenPurposes.map((p) => (
                                                                <SelectItem key={p.value} value={p.value}>
                                                                    {p.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError message={specForm.errors.purpose} />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="preservative">Pengawet *</Label>
                                                    <Select
                                                        value={specForm.data.preservative}
                                                        onValueChange={(v) => specForm.setData('preservative', v)}
                                                        disabled={!permissions.canEditIntraop}
                                                    >
                                                        <SelectTrigger id="preservative" className="min-h-11">
                                                            <SelectValue placeholder="Pilih pengawet" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {options.specimenPreservatives.map((p) => (
                                                                <SelectItem key={p.value} value={p.value}>
                                                                    {p.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError message={specForm.errors.preservative} />
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    <div className="space-y-3 rounded-lg border p-4">
                                        <label className="flex cursor-pointer items-center gap-2.5 text-sm font-semibold">
                                            <Checkbox
                                                checked={specForm.data.has_complication}
                                                disabled={!permissions.canEditComplication}
                                                onCheckedChange={(c) => specForm.setData('has_complication', c === true)}
                                            />
                                            <span>Terdapat Kejadian Khusus / Komplikasi Intra-Operatif</span>
                                        </label>

                                        {specForm.data.has_complication && (
                                            <div className="grid grid-cols-1 gap-4 pt-2 md:grid-cols-2">
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="complication_description">Uraian Kejadian Khusus / Komplikasi *</Label>
                                                    <textarea
                                                        id="complication_description"
                                                        rows={3}
                                                        value={specForm.data.complication_description}
                                                        onChange={(e) => specForm.setData('complication_description', e.target.value)}
                                                        disabled={!permissions.canEditComplication}
                                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                                    />
                                                    <InputError message={specForm.errors.complication_description} />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <Label htmlFor="complication_handling">Tindakan Penanganan *</Label>
                                                    <textarea
                                                        id="complication_handling"
                                                        rows={3}
                                                        value={specForm.data.complication_handling}
                                                        onChange={(e) => specForm.setData('complication_handling', e.target.value)}
                                                        disabled={!permissions.canEditComplication}
                                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                                    />
                                                    <InputError message={specForm.errors.complication_handling} />
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {permissions.canEditComplication && (
                                        <div className="flex justify-end">
                                            <Button type="submit" className="min-h-11" disabled={specForm.processing}>
                                                <Save className="mr-2 h-4 w-4" />
                                                Simpan Spesimen &amp; Komplikasi
                                            </Button>
                                        </div>
                                    )}
                                </form>
                            </CardContent>
                        </Card>

                        {/* 8B. Detail Tindakan Khusus & Data Implan (Sesuai Profil Operasi) */}
                        <IntraopProcedureDetailsCard
                            caseId={caseHeader.id}
                            surgeryProfile={caseHeader.surgery_profile}
                            initialData={procedureDetails}
                            canEdit={permissions.canEditComplication}
                        />
                    </>
                )}

                {/* 9. Pengesahan Dokumentasi Intra-Operatif (4 Pihak) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ShieldCheck className="text-primary h-5 w-5" />
                            9. Pengesahan Dokumentasi Intra-Operatif (4 Pihak - KF-34)
                        </CardTitle>
                        <CardDescription>
                            Seluruh 4 pihak (Perawat Sirkuler, Perawat Instrumen, Dokter Anestesi, Dokter Operator) wajib mengesahkan sebelum kasus
                            dapat bertransisi ke tahap Pasca-Operasi.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {signoffCards.map((card) => (
                                <div
                                    key={card.role_label}
                                    className={cn(
                                        'flex flex-col justify-between gap-3 rounded-lg border p-4',
                                        card.signed
                                            ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-950/30'
                                            : 'border-border bg-card',
                                    )}
                                >
                                    <div className="space-y-1">
                                        <div className="text-muted-foreground text-xs font-medium">{card.title}</div>
                                        <div className="text-sm font-semibold">{card.assigned_name}</div>
                                        {card.signed ? (
                                            <div className="space-y-0.5 pt-2 text-xs text-emerald-800 dark:text-emerald-200">
                                                <div className="flex items-center gap-1 font-semibold">
                                                    <CheckCircle2 className="h-3.5 w-3.5" />
                                                    Disahkan
                                                </div>
                                                <div>
                                                    {card.id_label}: {card.signed.identifier ?? '-'}
                                                </div>
                                                <div>{card.signed.signed_at} WIB</div>
                                            </div>
                                        ) : (
                                            <div className="text-muted-foreground pt-2 text-xs">Belum disahkan</div>
                                        )}
                                    </div>
                                    {card.can_sign && (
                                        <Button type="button" className="min-h-10 w-full" onClick={() => openSignDialog(card)}>
                                            Sahkan Bagian Saya
                                        </Button>
                                    )}
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {Object.keys(chronoForm.errors).length > 0 && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Periksa kesalahan kronologi waktu</AlertTitle>
                        <AlertDescription>{Object.values(chronoForm.errors).join(' ')}</AlertDescription>
                    </Alert>
                )}
            </div>

            {/* Dialog Konfirmasi Kata Sandi Pengesahan Intra-Op */}
            <Dialog open={signingCard !== null} onOpenChange={(open) => !open && setSigningCard(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Pengesahan Intra-Op - {signingCard?.title}</DialogTitle>
                        <DialogDescription>
                            Masukkan kata sandi akun Anda untuk mengesahkan dokumentasi intra-operasi sebagai <strong>{signingCard?.title}</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitSign} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="intra_sign_password">Kata Sandi Akun</Label>
                            <Input
                                id="intra_sign_password"
                                type="password"
                                value={signForm.data.password}
                                onChange={(e) => signForm.setData('password', e.target.value)}
                                className="min-h-11"
                            />
                            <InputError message={signForm.errors.password} />
                            <InputError message={(signForm.errors as Record<string, string | undefined>).auth} />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setSigningCard(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={signForm.processing}>
                                {signForm.processing ? 'Mengesahkan...' : 'Konfirmasi Pengesahan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </CaseLayout>
    );
}
