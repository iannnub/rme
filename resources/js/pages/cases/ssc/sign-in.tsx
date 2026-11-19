import { ConcurrencyAlert } from '@/components/concurrency-alert';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CaseLayout, { type CaseHeaderData } from '@/layouts/case-layout';
import { cn } from '@/lib/utils';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, CheckCircle2, ClipboardCheck, Save, ShieldCheck } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type OptionItem = { value: string; label: string };

type SignInData = {
    id: number | null;
    updated_at: string | null;
    status: string;
    identity_confirmed: string;
    procedure_confirmed: string;
    site_confirmed: string;
    consent_surgery: string;
    consent_anesthesia: string;
    site_marking: string;
    readiness: Record<string, string>;
    pulse_ox_attached: boolean | null;
    pulse_ox_functional: boolean | null;
    spo2: string | number;
    pulse: string | number;
    pulse_ox_note: string;
    allergy_status: string;
    allergy_type: string;
    allergy_agent: string;
    allergy_reaction: string;
    allergy_followup: string;
    difficult_airway_risk: string;
    aspiration_risk: string;
    anesthesiologist_assessment: string;
    anticipation_plan: string;
    airway_equipment_ready: string;
    bleeding_risk: string;
    estimated_blood_loss_ml: string | number;
    iv_access: string;
    fluids_ready: string;
    blood_ready: string;
    bleeding_note: string;
    notes_followup: string;
    signed_at: string | null;
    filled_by_name: string | null;
};

type VerificationInfo = {
    id: number;
    user_name: string;
    sip: string | null;
    signed_at: string;
};

type RevokedVerificationInfo = {
    id: number;
    user_name: string;
    signed_at: string;
    revoked_at: string | null;
    revoke_reason: string | null;
};

interface SscSignInPageProps {
    caseHeader: CaseHeaderData;
    roomAndTeamAssigned: boolean;
    signIn: SignInData;
    verificationState: {
        activeVerification: VerificationInfo | null;
        revokedVerifications: RevokedVerificationInfo[];
    };
    options: {
        sscStatuses: OptionItem[];
        matchStatuses: OptionItem[];
        consentStatuses: OptionItem[];
        siteMarkingStatuses: OptionItem[];
        readinessOptions: OptionItem[];
        allergyStatuses: OptionItem[];
        riskPresences: OptionItem[];
        ivAccessStatuses: OptionItem[];
        bloodReadinessOptions: OptionItem[];
    };
    permissions: {
        canEditAll: boolean;
        canEditAnesthesiaRisk: boolean;
        canVerify: boolean;
        canAdvance: boolean;
    };
}

const READINESS_LABELS: Array<{ key: string; label: string }> = [
    { key: 'anesthesia_machine', label: '1. Mesin Anestesi' },
    { key: 'oxygen_source', label: '2. Sumber Oksigen' },
    { key: 'anesthesia_drugs', label: '3. Obat-obatan Anestesi' },
    { key: 'airway_aids', label: '4. Alat Bantu Jalan Napas' },
    { key: 'suction', label: '5. Alat Suction' },
    { key: 'monitoring_device', label: '6. Alat Monitoring Pasien' },
    { key: 'emergency_drugs', label: '7. Obat Emergensi' },
];

function ChoiceButtons({
    value,
    options,
    onChange,
    disabled,
    problemValues = [],
}: {
    value: string;
    options: OptionItem[];
    onChange: (val: string) => void;
    disabled?: boolean;
    problemValues?: string[];
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((opt) => {
                const isSelected = value === opt.value;
                const isProblem = problemValues.includes(opt.value);

                return (
                    <button
                        key={opt.value}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange(opt.value)}
                        className={cn(
                            'min-h-11 rounded-md border px-4 py-2 text-sm font-medium transition-colors',
                            disabled && 'cursor-not-allowed opacity-60',
                            isSelected && !isProblem && 'border-primary bg-primary text-primary-foreground',
                            isSelected && isProblem && 'border-destructive bg-destructive text-destructive-foreground',
                            !isSelected && 'border-input bg-background text-foreground hover:bg-muted',
                        )}
                    >
                        {opt.label}
                    </button>
                );
            })}
        </div>
    );
}

function BoolButtons({
    value,
    onChange,
    disabled,
    falseIsProblem = false,
}: {
    value: boolean | null;
    onChange: (val: boolean) => void;
    disabled?: boolean;
    falseIsProblem?: boolean;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            <button
                type="button"
                disabled={disabled}
                onClick={() => onChange(true)}
                className={cn(
                    'min-h-11 rounded-md border px-4 py-2 text-sm font-medium transition-colors',
                    disabled && 'cursor-not-allowed opacity-60',
                    value === true ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background hover:bg-muted',
                )}
            >
                Ya
            </button>
            <button
                type="button"
                disabled={disabled}
                onClick={() => onChange(false)}
                className={cn(
                    'min-h-11 rounded-md border px-4 py-2 text-sm font-medium transition-colors',
                    disabled && 'cursor-not-allowed opacity-60',
                    value === false
                        ? falseIsProblem
                            ? 'border-destructive bg-destructive text-destructive-foreground'
                            : 'border-primary bg-primary text-primary-foreground'
                        : 'border-input bg-background hover:bg-muted',
                )}
            >
                Tidak
            </button>
        </div>
    );
}

export default function SscSignInPage({ caseHeader, roomAndTeamAssigned, signIn, verificationState, options, permissions }: SscSignInPageProps) {
    const form = useForm({
        status: signIn.status || 'dalam_proses',
        identity_confirmed: signIn.identity_confirmed,
        procedure_confirmed: signIn.procedure_confirmed,
        site_confirmed: signIn.site_confirmed,
        consent_surgery: signIn.consent_surgery,
        consent_anesthesia: signIn.consent_anesthesia,
        site_marking: signIn.site_marking,
        readiness: { ...signIn.readiness },
        pulse_ox_attached: signIn.pulse_ox_attached,
        pulse_ox_functional: signIn.pulse_ox_functional,
        spo2: signIn.spo2 === '' ? '' : String(signIn.spo2),
        pulse: signIn.pulse === '' ? '' : String(signIn.pulse),
        pulse_ox_note: signIn.pulse_ox_note,
        allergy_status: signIn.allergy_status,
        allergy_type: signIn.allergy_type,
        allergy_agent: signIn.allergy_agent,
        allergy_reaction: signIn.allergy_reaction,
        allergy_followup: signIn.allergy_followup,
        difficult_airway_risk: signIn.difficult_airway_risk,
        aspiration_risk: signIn.aspiration_risk,
        anesthesiologist_assessment: signIn.anesthesiologist_assessment,
        anticipation_plan: signIn.anticipation_plan,
        airway_equipment_ready: signIn.airway_equipment_ready,
        bleeding_risk: signIn.bleeding_risk,
        estimated_blood_loss_ml: signIn.estimated_blood_loss_ml === '' ? '' : String(signIn.estimated_blood_loss_ml),
        iv_access: signIn.iv_access,
        fluids_ready: signIn.fluids_ready,
        blood_ready: signIn.blood_ready,
        bleeding_note: signIn.bleeding_note,
        notes_followup: signIn.notes_followup,
    });

    const verifyForm = useForm({
        password: '',
    });

    const [verifyDialogOpen, setVerifyDialogOpen] = useState(false);
    const [advancing, setAdvancing] = useState(false);

    const hasProblematicItem =
        form.data.identity_confirmed === 'tidak_sesuai' ||
        form.data.procedure_confirmed === 'tidak_sesuai' ||
        form.data.site_confirmed === 'tidak_sesuai' ||
        form.data.consent_surgery === 'belum_ada' ||
        form.data.consent_anesthesia === 'belum_ada' ||
        form.data.site_marking === 'tidak_ada' ||
        Object.values(form.data.readiness).some((v) => v === 'tidak_siap') ||
        form.data.pulse_ox_attached === false ||
        form.data.pulse_ox_functional === false ||
        form.data.airway_equipment_ready === 'tidak_siap' ||
        form.data.iv_access === 'tidak_memadai' ||
        form.data.fluids_ready === 'tidak_siap' ||
        form.data.blood_ready === 'tidak_siap';

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            expected_updated_at: signIn.updated_at ?? '',
        }));
        form.put(route('cases.ssc.sign-in.update', caseHeader.id), {
            preserveScroll: true,
        });
    };

    const handleVerify = (e: FormEvent) => {
        e.preventDefault();
        verifyForm.post(route('cases.ssc.sign-in.verify', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                verifyForm.reset();
                setVerifyDialogOpen(false);
            },
        });
    };

    const handleAdvanceToSiap = () => {
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

    const formErrors = form.errors as Record<string, string | undefined>;
    const errorMessages = Object.values(formErrors).filter((msg): msg is string => Boolean(msg));

    const statusBadgeClass =
        signIn.status === 'selesai'
            ? 'bg-emerald-600 text-white'
            : signIn.status === 'dalam_proses'
              ? 'bg-amber-500 text-white'
              : 'bg-muted text-muted-foreground';

    const statusLabel = options.sscStatuses.find((s) => s.value === signIn.status)?.label ?? 'Belum Dimulai';

    const canEditAny = permissions.canEditAll || permissions.canEditAnesthesiaRisk;
    const isNurseFullEdit = permissions.canEditAll && roomAndTeamAssigned;
    const isAnesthesiaRiskEdit = permissions.canEditAnesthesiaRisk && roomAndTeamAssigned;

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="ssc">
            <Head title={`SSC Sign In - ${caseHeader.case_number}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                <ConcurrencyAlert message={formErrors.concurrency} />

                {/* Sub-tab navigasi SSC */}
                <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="default" className="min-h-11">
                            <Link href={route('cases.ssc.sign-in', caseHeader.id)}>1. Sign In (Sebelum Induksi)</Link>
                        </Button>
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href={route('cases.ssc.time-out', caseHeader.id)}>2. Time Out (Sebelum Insisi)</Link>
                        </Button>
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href={route('cases.ssc.sign-out', caseHeader.id)}>3. Sign Out (Akhir Operasi)</Link>
                        </Button>
                    </div>

                    <div className="flex items-center gap-2">
                        <Badge className={statusBadgeClass}>Status Sign In: {statusLabel}</Badge>
                        {permissions.canAdvance && caseHeader.status === 'pre_op' && caseHeader.next_transition?.can_advance && (
                            <Button type="button" className="min-h-11" disabled={advancing} onClick={handleAdvanceToSiap}>
                                Lanjut ke {caseHeader.next_transition.target_label}
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Button>
                        )}
                    </div>
                </div>

                {/* Peringatan aturan Q1 jika ruang & tim belum ditetapkan */}
                {!roomAndTeamAssigned && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Ruang Operasi & Tim Belum Lengkap</AlertTitle>
                        <AlertDescription className="flex flex-wrap items-center justify-between gap-2">
                            <span>
                                Sesuai aturan alur, ruang operasi dan seluruh 5 anggota tim wajib ditetapkan di tab Persiapan sebelum mengisi SSC Sign
                                In.
                            </span>
                            <Button asChild size="sm" variant="outline" className="bg-background text-foreground">
                                <Link href={route('cases.preparation', caseHeader.id)}>Tetapkan Ruang & Tim</Link>
                            </Button>
                        </AlertDescription>
                    </Alert>
                )}

                {errorMessages.length > 0 && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Terdapat kesalahan pada formulir SSC Sign In</AlertTitle>
                        <AlertDescription>
                            <ul className="list-disc space-y-1 pl-4">
                                {errorMessages.map((msg, idx) => (
                                    <li key={idx}>{msg}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Bagian A: Identitas Pasien & Tim (Read-only) */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <ClipboardCheck className="text-primary h-5 w-5" />
                            A. Identitas Pasien & Tim Operasi (Terintegrasi dari Data Kasus)
                        </CardTitle>
                        <CardDescription>Data ini diambil otomatis dari rekam kasus dan tidak perlu diisi ulang.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <div className="text-muted-foreground text-xs">Nama Pasien</div>
                                <div className="font-semibold">{caseHeader.patient.name}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">No. Rekam Medis</div>
                                <div className="font-mono font-semibold">{caseHeader.patient.rm_number}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Tgl Lahir / JK</div>
                                <div className="font-medium">
                                    {caseHeader.patient.birth_date} ({caseHeader.patient.gender_label})
                                </div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Diagnosis Medis</div>
                                <div className="font-medium">{caseHeader.diagnosis}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Kamar Operasi</div>
                                <div className="font-semibold">
                                    {caseHeader.room ? `${caseHeader.room.code} - ${caseHeader.room.name}` : 'Belum ditetapkan'}
                                </div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Jenis Operasi</div>
                                <div className="font-medium">{caseHeader.surgery_type}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Lokasi Operasi</div>
                                <div className="font-medium">{caseHeader.surgery_site}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Dokter Operator</div>
                                <div className="font-medium">{caseHeader.team.surgeon ?? 'Belum ditetapkan'}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Dokter Anestesi</div>
                                <div className="font-medium">{caseHeader.team.anesthesiologist ?? 'Belum ditetapkan'}</div>
                            </div>
                            <div>
                                <div className="text-muted-foreground text-xs">Perawat Sirkuler</div>
                                <div className="font-medium">{caseHeader.team.circulating_nurse ?? 'Belum ditetapkan'}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Bagian B: Konfirmasi Identitas dan Tindakan Operasi */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">B. Konfirmasi Identitas dan Tindakan Operasi</CardTitle>
                            <CardDescription>Konfirmasi secara lisan bersama pasien dan berkas rekam medis sebelum induksi anestesi.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label>1. Identitas Pasien</Label>
                                    <ChoiceButtons
                                        value={form.data.identity_confirmed}
                                        options={options.matchStatuses}
                                        onChange={(val) => form.setData('identity_confirmed', val)}
                                        disabled={!isNurseFullEdit}
                                        problemValues={['tidak_sesuai']}
                                    />
                                    <InputError message={form.errors.identity_confirmed} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>2. Jenis Tindakan Operasi</Label>
                                    <ChoiceButtons
                                        value={form.data.procedure_confirmed}
                                        options={options.matchStatuses}
                                        onChange={(val) => form.setData('procedure_confirmed', val)}
                                        disabled={!isNurseFullEdit}
                                        problemValues={['tidak_sesuai']}
                                    />
                                    <InputError message={form.errors.procedure_confirmed} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>3. Lokasi atau Sisi Operasi</Label>
                                    <ChoiceButtons
                                        value={form.data.site_confirmed}
                                        options={options.matchStatuses}
                                        onChange={(val) => form.setData('site_confirmed', val)}
                                        disabled={!isNurseFullEdit}
                                        problemValues={['tidak_sesuai']}
                                    />
                                    <InputError message={form.errors.site_confirmed} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>4. Informed Consent Operasi</Label>
                                    <ChoiceButtons
                                        value={form.data.consent_surgery}
                                        options={options.consentStatuses}
                                        onChange={(val) => form.setData('consent_surgery', val)}
                                        disabled={!isNurseFullEdit}
                                        problemValues={['belum_ada']}
                                    />
                                    <InputError message={form.errors.consent_surgery} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>5. Informed Consent Anestesi</Label>
                                    <ChoiceButtons
                                        value={form.data.consent_anesthesia}
                                        options={options.consentStatuses}
                                        onChange={(val) => form.setData('consent_anesthesia', val)}
                                        disabled={!isNurseFullEdit}
                                        problemValues={['belum_ada']}
                                    />
                                    <InputError message={form.errors.consent_anesthesia} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>6. Penandaan Lokasi Operasi</Label>
                                    <ChoiceButtons
                                        value={form.data.site_marking}
                                        options={options.siteMarkingStatuses}
                                        onChange={(val) => form.setData('site_marking', val)}
                                        disabled={!isNurseFullEdit}
                                        problemValues={['tidak_ada']}
                                    />
                                    <InputError message={form.errors.site_marking} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Bagian C: Kesiapan Alat dan Obat Anestesi */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">C. Kesiapan Alat dan Obat Anestesi</CardTitle>
                            <CardDescription>Pastikan seluruh 7 perangkat dan obat anestesi diperiksa kesiapannya.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                {READINESS_LABELS.map(({ key, label }) => (
                                    <div key={key} className="flex flex-col justify-between gap-2 rounded-lg border p-3 sm:flex-row sm:items-center">
                                        <Label className="text-sm font-medium">{label}</Label>
                                        <ChoiceButtons
                                            value={form.data.readiness[key] ?? ''}
                                            options={options.readinessOptions}
                                            onChange={(val) =>
                                                form.setData('readiness', {
                                                    ...form.data.readiness,
                                                    [key]: val,
                                                })
                                            }
                                            disabled={!isNurseFullEdit}
                                            problemValues={['tidak_siap']}
                                        />
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Bagian D: Pemeriksaan Pulse Oximeter */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">D. Pemeriksaan Pulse Oximeter</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div className="space-y-1.5">
                                    <Label>Pulse Oximeter Terpasang</Label>
                                    <BoolButtons
                                        value={form.data.pulse_ox_attached}
                                        onChange={(val) => form.setData('pulse_ox_attached', val)}
                                        disabled={!isNurseFullEdit}
                                        falseIsProblem
                                    />
                                    <InputError message={form.errors.pulse_ox_attached} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Alat Berfungsi</Label>
                                    <BoolButtons
                                        value={form.data.pulse_ox_functional}
                                        onChange={(val) => form.setData('pulse_ox_functional', val)}
                                        disabled={!isNurseFullEdit}
                                        falseIsProblem
                                    />
                                    <InputError message={form.errors.pulse_ox_functional} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="spo2">SpO₂ (%)</Label>
                                    <Input
                                        id="spo2"
                                        type="number"
                                        min={0}
                                        max={100}
                                        value={form.data.spo2}
                                        onChange={(e) => form.setData('spo2', e.target.value)}
                                        disabled={!isNurseFullEdit}
                                        placeholder="mis. 99"
                                        className="min-h-11"
                                    />
                                    <InputError message={form.errors.spo2} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="pulse">Denyut Nadi (x/menit)</Label>
                                    <Input
                                        id="pulse"
                                        type="number"
                                        min={0}
                                        max={300}
                                        value={form.data.pulse}
                                        onChange={(e) => form.setData('pulse', e.target.value)}
                                        disabled={!isNurseFullEdit}
                                        placeholder="mis. 80"
                                        className="min-h-11"
                                    />
                                    <InputError message={form.errors.pulse} />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="pulse_ox_note">Keterangan Pulse Oximeter</Label>
                                <Input
                                    id="pulse_ox_note"
                                    value={form.data.pulse_ox_note}
                                    onChange={(e) => form.setData('pulse_ox_note', e.target.value)}
                                    disabled={!isNurseFullEdit}
                                    placeholder="Catatan lokasi pemasangan atau kendala alat"
                                    className="min-h-11"
                                />
                                <InputError message={form.errors.pulse_ox_note} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Bagian E: Pemeriksaan Riwayat Alergi */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">E. Pemeriksaan Riwayat Alergi</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-1.5">
                                <Label>Riwayat Alergi</Label>
                                <ChoiceButtons
                                    value={form.data.allergy_status}
                                    options={options.allergyStatuses}
                                    onChange={(val) => form.setData('allergy_status', val)}
                                    disabled={!isNurseFullEdit}
                                    problemValues={['ada']}
                                />
                                <InputError message={form.errors.allergy_status} />
                            </div>

                            {form.data.allergy_status === 'ada' && (
                                <div className="grid grid-cols-1 gap-4 rounded-lg border border-red-200 bg-red-50/50 p-4 md:grid-cols-2 dark:border-red-900 dark:bg-red-950/20">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="allergy_type">Jenis Alergi</Label>
                                        <Input
                                            id="allergy_type"
                                            value={form.data.allergy_type}
                                            onChange={(e) => form.setData('allergy_type', e.target.value)}
                                            disabled={!isNurseFullEdit}
                                            placeholder="Obat / Lateks / Makanan / Lainnya"
                                            className="min-h-11"
                                        />
                                        <InputError message={form.errors.allergy_type} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="allergy_agent">Nama Bahan Penyebab *</Label>
                                        <Input
                                            id="allergy_agent"
                                            value={form.data.allergy_agent}
                                            onChange={(e) => form.setData('allergy_agent', e.target.value)}
                                            disabled={!isNurseFullEdit}
                                            placeholder="Nama obat atau bahan penyebab"
                                            className="min-h-11"
                                        />
                                        <InputError message={form.errors.allergy_agent} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="allergy_reaction">Reaksi Alergi</Label>
                                        <Input
                                            id="allergy_reaction"
                                            value={form.data.allergy_reaction}
                                            onChange={(e) => form.setData('allergy_reaction', e.target.value)}
                                            disabled={!isNurseFullEdit}
                                            placeholder="Jenis reaksi yang pernah dialami"
                                            className="min-h-11"
                                        />
                                        <InputError message={form.errors.allergy_reaction} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="allergy_followup">Tindak Lanjut Alergi</Label>
                                        <Input
                                            id="allergy_followup"
                                            value={form.data.allergy_followup}
                                            onChange={(e) => form.setData('allergy_followup', e.target.value)}
                                            disabled={!isNurseFullEdit}
                                            placeholder="Catatan dan rencana penanganan"
                                            className="min-h-11"
                                        />
                                        <InputError message={form.errors.allergy_followup} />
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Bagian F: Pemeriksaan Risiko Jalan Napas dan Aspirasi */}
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <CardTitle className="text-base">F. Pemeriksaan Risiko Jalan Napas dan Aspirasi</CardTitle>
                                    <CardDescription>
                                        Bagian ini dapat diisi oleh perawat bersama atau diperbarui langsung oleh Dokter Anestesi kasus ini.
                                    </CardDescription>
                                </div>
                                <Badge variant="outline">Kolaborasi Perawat & Dokter Anestesi</Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div className="space-y-1.5">
                                    <Label>Risiko Kesulitan Jalan Napas</Label>
                                    <ChoiceButtons
                                        value={form.data.difficult_airway_risk}
                                        options={options.riskPresences}
                                        onChange={(val) => form.setData('difficult_airway_risk', val)}
                                        disabled={!isAnesthesiaRiskEdit}
                                    />
                                    <InputError message={form.errors.difficult_airway_risk} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Risiko Aspirasi</Label>
                                    <ChoiceButtons
                                        value={form.data.aspiration_risk}
                                        options={options.riskPresences}
                                        onChange={(val) => form.setData('aspiration_risk', val)}
                                        disabled={!isAnesthesiaRiskEdit}
                                    />
                                    <InputError message={form.errors.aspiration_risk} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Kesiapan Alat Pendukung</Label>
                                    <ChoiceButtons
                                        value={form.data.airway_equipment_ready}
                                        options={options.readinessOptions}
                                        onChange={(val) => form.setData('airway_equipment_ready', val)}
                                        disabled={!isAnesthesiaRiskEdit}
                                        problemValues={['tidak_siap']}
                                    />
                                    <InputError message={form.errors.airway_equipment_ready} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="anesthesiologist_assessment">Penilaian Dokter Anestesi</Label>
                                    <textarea
                                        id="anesthesiologist_assessment"
                                        rows={3}
                                        value={form.data.anesthesiologist_assessment}
                                        onChange={(e) => form.setData('anesthesiologist_assessment', e.target.value)}
                                        disabled={!isAnesthesiaRiskEdit}
                                        placeholder="Hasil pengkajian jalan napas (skor Mallampati, status ASA, dsb.)"
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError message={form.errors.anesthesiologist_assessment} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="anticipation_plan">Rencana Antisipasi</Label>
                                    <textarea
                                        id="anticipation_plan"
                                        rows={3}
                                        value={form.data.anticipation_plan}
                                        onChange={(e) => form.setData('anticipation_plan', e.target.value)}
                                        disabled={!isAnesthesiaRiskEdit}
                                        placeholder="Rencana antisipasi bila terdapat risiko kesulitan jalan napas/aspirasi"
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    <InputError message={form.errors.anticipation_plan} />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Bagian G: Pemeriksaan Risiko Perdarahan */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">G. Pemeriksaan Risiko Perdarahan</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div className="space-y-1.5">
                                    <Label>Risiko Perdarahan (&gt; 500 ml / 7 ml/kgBB)</Label>
                                    <ChoiceButtons
                                        value={form.data.bleeding_risk}
                                        options={options.riskPresences}
                                        onChange={(val) => form.setData('bleeding_risk', val)}
                                        disabled={!isAnesthesiaRiskEdit}
                                    />
                                    <InputError message={form.errors.bleeding_risk} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="estimated_blood_loss_ml">Perkiraan Kehilangan Darah (ml)</Label>
                                    <Input
                                        id="estimated_blood_loss_ml"
                                        type="number"
                                        min={0}
                                        max={20000}
                                        value={form.data.estimated_blood_loss_ml}
                                        onChange={(e) => form.setData('estimated_blood_loss_ml', e.target.value)}
                                        disabled={!isAnesthesiaRiskEdit}
                                        placeholder="mis. 200"
                                        className="min-h-11"
                                    />
                                    <InputError message={form.errors.estimated_blood_loss_ml} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Akses Intravena</Label>
                                    <ChoiceButtons
                                        value={form.data.iv_access}
                                        options={options.ivAccessStatuses}
                                        onChange={(val) => form.setData('iv_access', val)}
                                        disabled={!isAnesthesiaRiskEdit}
                                        problemValues={['tidak_memadai']}
                                    />
                                    <InputError message={form.errors.iv_access} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Kesiapan Cairan</Label>
                                    <ChoiceButtons
                                        value={form.data.fluids_ready}
                                        options={options.readinessOptions}
                                        onChange={(val) => form.setData('fluids_ready', val)}
                                        disabled={!isAnesthesiaRiskEdit}
                                        problemValues={['tidak_siap']}
                                    />
                                    <InputError message={form.errors.fluids_ready} />
                                </div>

                                <div className="space-y-1.5 md:col-span-2">
                                    <Label>Kesiapan Darah</Label>
                                    <ChoiceButtons
                                        value={form.data.blood_ready}
                                        options={options.bloodReadinessOptions}
                                        onChange={(val) => form.setData('blood_ready', val)}
                                        disabled={!isAnesthesiaRiskEdit}
                                        problemValues={['tidak_siap']}
                                    />
                                    <InputError message={form.errors.blood_ready} />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="bleeding_note">Keterangan Risiko Perdarahan</Label>
                                <Input
                                    id="bleeding_note"
                                    value={form.data.bleeding_note}
                                    onChange={(e) => form.setData('bleeding_note', e.target.value)}
                                    disabled={!isAnesthesiaRiskEdit}
                                    placeholder="Jenis/ukuran kanula IV, persiapan crossmatch darah, dsb."
                                    className="min-h-11"
                                />
                                <InputError message={form.errors.bleeding_note} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Dokumentasi Petugas, Catatan Tindak Lanjut & Status Checklist */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Dokumentasi Petugas & Penyelesaian Sign In</CardTitle>
                            <CardDescription>
                                Jika terdapat item bermasalah (Tidak Sesuai / Belum Ada / Tidak Siap / Tidak Memadai), catatan tindak lanjut wajib
                                diisi sebelum menyelesaikan checklist.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {hasProblematicItem && (
                                <Alert className="border-amber-400 bg-amber-50 text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
                                    <AlertTriangle className="h-4 w-4 text-amber-600" />
                                    <AlertTitle>Item Bermasalah Terdeteksi</AlertTitle>
                                    <AlertDescription>
                                        Terdapat item Sign In yang belum sesuai/siap. Tuliskan temuan dan tindakan penyelesaiannya di kolom Catatan &
                                        Tindak Lanjut sebelum mengubah status menjadi <strong>Selesai</strong>.
                                    </AlertDescription>
                                </Alert>
                            )}

                            <div className="space-y-1.5">
                                <Label htmlFor="notes_followup">
                                    Catatan Temuan & Tindak Lanjut{' '}
                                    {hasProblematicItem ? <span className="text-destructive">* (Wajib)</span> : '(Opsional)'}
                                </Label>
                                <textarea
                                    id="notes_followup"
                                    rows={3}
                                    value={form.data.notes_followup}
                                    onChange={(e) => form.setData('notes_followup', e.target.value)}
                                    disabled={!isAnesthesiaRiskEdit}
                                    placeholder="Tuliskan temuan keselamatan pasien dan tindak lanjut yang sudah dilakukan tim..."
                                    className={cn(
                                        'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                                        hasProblematicItem && !form.data.notes_followup.trim() && 'border-destructive',
                                    )}
                                />
                                <InputError message={form.errors.notes_followup} />
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                {permissions.canEditAll && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="status">Status Penyelesaian Checklist</Label>
                                        <Select
                                            value={form.data.status}
                                            onValueChange={(val) => form.setData('status', val)}
                                            disabled={!isNurseFullEdit}
                                        >
                                            <SelectTrigger id="status" className="min-h-11">
                                                <SelectValue placeholder="Pilih status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.sscStatuses.map((s) => (
                                                    <SelectItem key={s.value} value={s.value}>
                                                        {s.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={form.errors.status} />
                                    </div>
                                )}

                                <div className="bg-muted/40 flex flex-col justify-center rounded-lg border p-3 text-xs">
                                    <div>
                                        Terakhir diisi oleh: <strong>{signIn.filled_by_name ?? 'Belum disimpan'}</strong>
                                    </div>
                                    <div>
                                        Waktu selesai Sign In: <strong>{signIn.signed_at ? `${signIn.signed_at} WIB` : 'Belum selesai'}</strong>
                                    </div>
                                </div>
                            </div>

                            {canEditAny && roomAndTeamAssigned && (
                                <div className="flex justify-end pt-2">
                                    <Button type="submit" className="min-h-11" disabled={form.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {form.processing
                                            ? 'Menyimpan...'
                                            : permissions.canEditAll
                                              ? 'Simpan SSC Sign In'
                                              : 'Simpan Penilaian Risiko Anestesi'}
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </form>

                {/* Verifikasi Dokter Anestesi */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ShieldCheck className="text-primary h-5 w-5" />
                                    Verifikasi Dokter Anestesi (Sign In)
                                </CardTitle>
                                <CardDescription>
                                    Dokter anestesi yang ditetapkan pada kasus ini dapat memverifikasi formulir Sign In dengan konfirmasi kata sandi.
                                </CardDescription>
                            </div>
                            {permissions.canVerify && signIn.id !== null && (
                                <Button type="button" variant="outline" className="min-h-11" onClick={() => setVerifyDialogOpen(true)}>
                                    <ShieldCheck className="mr-2 h-4 w-4" />
                                    Verifikasi Sign In
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {verificationState.activeVerification ? (
                            <div className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100">
                                <CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
                                <div>
                                    <div className="font-semibold">Terverifikasi oleh {verificationState.activeVerification.user_name}</div>
                                    <div className="text-xs opacity-85">
                                        SIP: {verificationState.activeVerification.sip ?? '-'} • Waktu:{' '}
                                        {verificationState.activeVerification.signed_at} WIB
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="text-muted-foreground rounded-lg border border-dashed p-4 text-sm">
                                Belum ada verifikasi aktif dari Dokter Anestesi untuk formulir Sign In ini.
                            </div>
                        )}

                        {verificationState.revokedVerifications.length > 0 && (
                            <div className="space-y-1.5 pt-2">
                                <div className="text-muted-foreground text-xs font-semibold">Riwayat Verifikasi yang Dibatalkan:</div>
                                {verificationState.revokedVerifications.map((rev) => (
                                    <div
                                        key={rev.id}
                                        className="rounded-md border border-amber-200 bg-amber-50/60 p-2.5 text-xs dark:border-amber-900 dark:bg-amber-950/20"
                                    >
                                        <span className="font-medium">{rev.user_name}</span> (disahkan {rev.signed_at}) - dibatalkan pada{' '}
                                        {rev.revoked_at}: <em>{rev.revoke_reason ?? 'Data diperbarui'}</em>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Dialog Konfirmasi Kata Sandi Verifikasi Dokter Anestesi */}
            <Dialog open={verifyDialogOpen} onOpenChange={setVerifyDialogOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Verifikasi SSC Sign In</DialogTitle>
                        <DialogDescription>
                            Masukkan kata sandi akun Dokter Anestesi Anda untuk mengonfirmasi verifikasi formulir Sign In kasus{' '}
                            <strong>{caseHeader.case_number}</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleVerify} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="verify_password">Kata Sandi Akun</Label>
                            <Input
                                id="verify_password"
                                type="password"
                                value={verifyForm.data.password}
                                onChange={(e) => verifyForm.setData('password', e.target.value)}
                                className="min-h-11"
                                autoComplete="current-password"
                            />
                            <InputError message={verifyForm.errors.password} />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setVerifyDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={verifyForm.processing}>
                                {verifyForm.processing ? 'Memverifikasi...' : 'Konfirmasi Verifikasi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </CaseLayout>
    );
}
