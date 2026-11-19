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
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, ClipboardCheck, ExternalLink, Save, ShieldCheck } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type OptionItem = { value: string; label: string };

type SignOutData = {
    id: number | null;
    updated_at: string | null;
    status: string;
    q1_procedure_confirmed: boolean | null;
    q2_counts_correct: boolean | null;
    q3_specimen_labeled: string;
    q4_equipment_problem: boolean | null;
    q5_recovery_discussed: boolean | null;
    extra_6_procedure_matches: string;
    extra_7_changes_recorded: string;
    extra_8_discrepancy_followup: string;
    extra_9_specimen_matches: string;
    extra_10_equipment_issue_recorded: string;
    extra_11_complication_communicated: string;
    extra_12_postop_monitoring_discussed: string;
    extra_13_devices_instructions: string;
    recovery_notes: string;
    issues_and_actions: string;
    signed_at: string | null;
    filled_by_name: string | null;
};

interface SscSignOutPageProps {
    caseHeader: CaseHeaderData;
    signOut: SignOutData;
    intraopSummary: {
        all_five_final: boolean;
        has_ts: boolean;
        count_verification: string;
        count_verification_label: string;
        discrepancy_actions: string[];
        has_specimen: boolean;
        specimen_summary: string;
        has_complication: boolean;
        complication_description: string | null;
    };
    verifications: Array<{
        id: number;
        role_label: string;
        user_name: string;
        sip: string | null;
        signed_at: string;
    }>;
    options: {
        signOutStatuses: OptionItem[];
    };
    permissions: {
        canEdit: boolean;
        canVerify: boolean;
    };
}

function ToggleChoices({
    value,
    options,
    onChange,
    disabled,
    dangerValues = [],
}: {
    value: string;
    options: OptionItem[];
    onChange: (val: string) => void;
    disabled?: boolean;
    dangerValues?: string[];
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((opt) => {
                const selected = value === opt.value;
                const isDanger = dangerValues.includes(opt.value);
                return (
                    <button
                        key={opt.value}
                        type="button"
                        disabled={disabled}
                        onClick={() => onChange(opt.value)}
                        className={cn(
                            'min-h-11 rounded-md border px-4 py-2 text-sm font-medium transition-colors',
                            disabled && 'cursor-not-allowed opacity-60',
                            selected && !isDanger && 'border-primary bg-primary text-primary-foreground',
                            selected && isDanger && 'border-destructive bg-destructive text-destructive-foreground',
                            !selected && 'border-input bg-background hover:bg-muted',
                        )}
                    >
                        {opt.label}
                    </button>
                );
            })}
        </div>
    );
}

function BoolChoice({
    value,
    onChange,
    disabled,
    trueLabel = 'Ya',
    falseLabel = 'Tidak',
    dangerOn,
}: {
    value: boolean | null;
    onChange: (val: boolean) => void;
    disabled?: boolean;
    trueLabel?: string;
    falseLabel?: string;
    dangerOn?: boolean;
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
                    value === true
                        ? dangerOn === true
                            ? 'border-destructive bg-destructive text-destructive-foreground'
                            : 'border-primary bg-primary text-primary-foreground'
                        : 'border-input bg-background hover:bg-muted',
                )}
            >
                {trueLabel}
            </button>
            <button
                type="button"
                disabled={disabled}
                onClick={() => onChange(false)}
                className={cn(
                    'min-h-11 rounded-md border px-4 py-2 text-sm font-medium transition-colors',
                    disabled && 'cursor-not-allowed opacity-60',
                    value === false
                        ? dangerOn === false
                            ? 'border-destructive bg-destructive text-destructive-foreground'
                            : 'border-primary bg-primary text-primary-foreground'
                        : 'border-input bg-background hover:bg-muted',
                )}
            >
                {falseLabel}
            </button>
        </div>
    );
}

export default function SscSignOutPage({ caseHeader, signOut, intraopSummary, verifications, options, permissions }: SscSignOutPageProps) {
    const form = useForm({
        status: signOut.status || 'draf',
        q1_procedure_confirmed: signOut.q1_procedure_confirmed,
        q2_counts_correct: signOut.q2_counts_correct,
        q3_specimen_labeled: signOut.q3_specimen_labeled,
        q4_equipment_problem: signOut.q4_equipment_problem,
        q5_recovery_discussed: signOut.q5_recovery_discussed,
        extra_6_procedure_matches: signOut.extra_6_procedure_matches,
        extra_7_changes_recorded: signOut.extra_7_changes_recorded,
        extra_8_discrepancy_followup: signOut.extra_8_discrepancy_followup,
        extra_9_specimen_matches: signOut.extra_9_specimen_matches,
        extra_10_equipment_issue_recorded: signOut.extra_10_equipment_issue_recorded,
        extra_11_complication_communicated: signOut.extra_11_complication_communicated,
        extra_12_postop_monitoring_discussed: signOut.extra_12_postop_monitoring_discussed,
        extra_13_devices_instructions: signOut.extra_13_devices_instructions,
        recovery_notes: signOut.recovery_notes,
        issues_and_actions: signOut.issues_and_actions,
    });

    const verifyForm = useForm({ password: '' });
    const [verifyOpen, setVerifyOpen] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            expected_updated_at: signOut.updated_at ?? '',
        }));
        form.put(route('cases.ssc.sign-out.update', caseHeader.id), {
            preserveScroll: true,
        });
    };

    const handleVerify = (e: FormEvent) => {
        e.preventDefault();
        verifyForm.post(route('cases.ssc.sign-out.verify', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => {
                verifyForm.reset();
                setVerifyOpen(false);
            },
        });
    };

    const errorMessages = Object.values(form.errors as Record<string, string | undefined>).filter((m): m is string => Boolean(m));
    const statusLabel = options.signOutStatuses.find((s) => s.value === signOut.status)?.label ?? 'Draf';
    const statusBadgeClass =
        signOut.status === 'terverifikasi'
            ? 'bg-emerald-600 text-white'
            : signOut.status === 'perlu_tindak_lanjut'
              ? 'bg-destructive text-destructive-foreground'
              : 'bg-muted text-muted-foreground';

    const showDiscrepancyExtra = form.data.q2_counts_correct === false || intraopSummary.has_ts;
    const showSpecimenExtra = form.data.q3_specimen_labeled === 'ya' || intraopSummary.has_specimen;
    const showEquipmentExtra = form.data.q4_equipment_problem === true;

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="ssc">
            <Head title={`SSC Sign Out - ${caseHeader.case_number}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                <ConcurrencyAlert message={(form.errors as Record<string, string | undefined>).concurrency} />

                <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href={route('cases.ssc.sign-in', caseHeader.id)}>1. Sign In (Sebelum Induksi)</Link>
                        </Button>
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href={route('cases.ssc.time-out', caseHeader.id)}>2. Time Out (Sebelum Insisi)</Link>
                        </Button>
                        <Button asChild variant="default" className="min-h-11">
                            <Link href={route('cases.ssc.sign-out', caseHeader.id)}>3. Sign Out (Akhir Operasi)</Link>
                        </Button>
                    </div>
                    <Badge className={statusBadgeClass}>Status Sign Out: {statusLabel}</Badge>
                </div>

                {/* Ringkasan sinkronisasi dari Intra-Op */}
                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <CardTitle className="text-sm">Ringkasan Sumber Data Intra-Op (Hitungan, Spesimen & Komplikasi)</CardTitle>
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('cases.intraop', caseHeader.id)}>
                                    Buka Tab Intra-Op
                                    <ExternalLink className="ml-1.5 h-3.5 w-3.5" />
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                            <div className="rounded-lg border p-3">
                                <div className="text-muted-foreground text-xs">Verifikasi Hitungan Instrumen</div>
                                <div className="mt-0.5 font-semibold">{intraopSummary.count_verification_label}</div>
                                <div className="text-muted-foreground text-xs">
                                    {intraopSummary.all_five_final ? '5 item hitungan akhir terisi' : 'Belum lengkap 5 item akhir'}
                                    {intraopSummary.has_ts ? ' • Terdapat selisih (TS)' : ''}
                                </div>
                            </div>
                            <div className="rounded-lg border p-3">
                                <div className="text-muted-foreground text-xs">Spesimen Operasi</div>
                                <div className="mt-0.5 font-semibold">{intraopSummary.specimen_summary}</div>
                            </div>
                            <div className="rounded-lg border p-3">
                                <div className="text-muted-foreground text-xs">Kejadian Khusus / Komplikasi</div>
                                <div className="mt-0.5 font-semibold">
                                    {intraopSummary.has_complication
                                        ? intraopSummary.complication_description || 'Ada komplikasi'
                                        : 'Tidak ada komplikasi'}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {errorMessages.length > 0 && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Validasi SSC Sign Out</AlertTitle>
                        <AlertDescription>
                            <ul className="list-disc space-y-1 pl-4">
                                {errorMessages.map((msg, idx) => (
                                    <li key={idx}>{msg}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* 5 Pertanyaan Utama WHO */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ClipboardCheck className="text-primary h-5 w-5" />
                                B. Checklist Utama WHO - Sign Out (5 Pemeriksaan)
                            </CardTitle>
                            <CardDescription>Pilih jawaban secara manual setelah konfirmasi lisan tim (tidak tercentang otomatis).</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="flex flex-col justify-between gap-2 rounded-lg border p-3 sm:flex-row sm:items-center">
                                <Label className="text-sm font-medium">
                                    1. Apakah nama tindakan operasi yang telah dilakukan sudah dikonfirmasi?
                                </Label>
                                <BoolChoice
                                    value={form.data.q1_procedure_confirmed}
                                    onChange={(val) => form.setData('q1_procedure_confirmed', val)}
                                    disabled={!permissions.canEdit}
                                    dangerOn={false}
                                />
                            </div>

                            <div className="flex flex-col justify-between gap-2 rounded-lg border p-3 sm:flex-row sm:items-center">
                                <Label className="text-sm font-medium">
                                    2. Apakah penghitungan instrumen, kasa, dan jarum telah selesai dan jumlahnya sesuai?
                                </Label>
                                <BoolChoice
                                    value={form.data.q2_counts_correct}
                                    onChange={(val) => form.setData('q2_counts_correct', val)}
                                    disabled={!permissions.canEdit}
                                    dangerOn={false}
                                />
                            </div>
                            <InputError message={form.errors.q2_counts_correct} />

                            <div className="flex flex-col justify-between gap-2 rounded-lg border p-3 sm:flex-row sm:items-center">
                                <Label className="text-sm font-medium">
                                    3. Apakah spesimen telah diberi label dengan benar, termasuk nama pasien?
                                </Label>
                                <ToggleChoices
                                    value={form.data.q3_specimen_labeled}
                                    options={[
                                        { value: 'ya', label: 'Ya' },
                                        { value: 'tidak', label: 'Tidak' },
                                        { value: 'tidak_berlaku', label: 'Tidak Berlaku' },
                                    ]}
                                    onChange={(val) => form.setData('q3_specimen_labeled', val)}
                                    disabled={!permissions.canEdit}
                                    dangerValues={['tidak']}
                                />
                            </div>
                            <InputError message={form.errors.q3_specimen_labeled} />

                            <div className="flex flex-col justify-between gap-2 rounded-lg border p-3 sm:flex-row sm:items-center">
                                <Label className="text-sm font-medium">4. Apakah terdapat masalah pada peralatan yang perlu ditangani?</Label>
                                <BoolChoice
                                    value={form.data.q4_equipment_problem}
                                    onChange={(val) => form.setData('q4_equipment_problem', val)}
                                    disabled={!permissions.canEdit}
                                    dangerOn={true}
                                />
                            </div>

                            <div className="flex flex-col justify-between gap-2 rounded-lg border p-3 sm:flex-row sm:items-center">
                                <Label className="text-sm font-medium">
                                    5. Konfirmasi bersama dokter operator, dokter anestesi, & perawat: Hal penting pemulihan & penatalaksanaan
                                    pasca-op sudah dibahas?
                                </Label>
                                <BoolChoice
                                    value={form.data.q5_recovery_discussed}
                                    onChange={(val) => form.setData('q5_recovery_discussed', val)}
                                    disabled={!permissions.canEdit}
                                    trueLabel="Sudah Dikonfirmasi"
                                    falseLabel="Belum"
                                    dangerOn={false}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* 8 Pertanyaan Tambahan Terpilih (Kondisional) */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">C. Pertanyaan Tambahan RME (Kondisional & Rencana Pemulihan)</CardTitle>
                            <CardDescription>Item selisih hitungan, spesimen, dan masalah alat muncul otomatis saat relevan.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5 rounded-lg border p-3">
                                    <Label className="text-xs">6. Nama prosedur di catatan sesuai tindakan yang benar-benar dilakukan?</Label>
                                    <ToggleChoices
                                        value={form.data.extra_6_procedure_matches}
                                        options={[
                                            { value: 'sesuai', label: 'Sesuai' },
                                            { value: 'belum_sesuai', label: 'Belum Sesuai' },
                                        ]}
                                        onChange={(val) => form.setData('extra_6_procedure_matches', val)}
                                        disabled={!permissions.canEdit}
                                        dangerValues={['belum_sesuai']}
                                    />
                                </div>

                                <div className="space-y-1.5 rounded-lg border p-3">
                                    <Label className="text-xs">7. Jika ada perubahan/tindakan tambahan saat operasi, apakah sudah dicatat?</Label>
                                    <ToggleChoices
                                        value={form.data.extra_7_changes_recorded}
                                        options={[
                                            { value: 'sudah', label: 'Sudah' },
                                            { value: 'belum', label: 'Belum' },
                                            { value: 'tidak_ada_perubahan', label: 'Tidak Ada Perubahan' },
                                        ]}
                                        onChange={(val) => form.setData('extra_7_changes_recorded', val)}
                                        disabled={!permissions.canEdit}
                                        dangerValues={['belum']}
                                    />
                                </div>

                                {showDiscrepancyExtra && (
                                    <div className="space-y-1.5 rounded-lg border border-amber-400 bg-amber-50/50 p-3 dark:bg-amber-950/20">
                                        <Label className="text-xs font-semibold">
                                            8. Ditemukan selisih hitungan instrumen/kasa/jarum - apakah tindak lanjut SOP sudah dilakukan? *
                                        </Label>
                                        <ToggleChoices
                                            value={form.data.extra_8_discrepancy_followup}
                                            options={[
                                                { value: 'sudah', label: 'Sudah' },
                                                { value: 'belum', label: 'Belum' },
                                                { value: 'tidak_ada_selisih', label: 'Tidak Ada Selisih' },
                                            ]}
                                            onChange={(val) => form.setData('extra_8_discrepancy_followup', val)}
                                            disabled={!permissions.canEdit}
                                            dangerValues={['belum']}
                                        />
                                    </div>
                                )}

                                {showSpecimenExtra && (
                                    <div className="space-y-1.5 rounded-lg border p-3">
                                        <Label className="text-xs">9. Jenis dan asal spesimen sudah sesuai dengan labelnya?</Label>
                                        <ToggleChoices
                                            value={form.data.extra_9_specimen_matches}
                                            options={[
                                                { value: 'sesuai', label: 'Sesuai' },
                                                { value: 'belum_sesuai', label: 'Belum Sesuai' },
                                                { value: 'tidak_ada_spesimen', label: 'Tidak Ada Spesimen' },
                                            ]}
                                            onChange={(val) => form.setData('extra_9_specimen_matches', val)}
                                            disabled={!permissions.canEdit}
                                            dangerValues={['belum_sesuai']}
                                        />
                                    </div>
                                )}

                                {showEquipmentExtra && (
                                    <div className="space-y-1.5 rounded-lg border border-amber-400 bg-amber-50/50 p-3 dark:bg-amber-950/20">
                                        <Label className="text-xs font-semibold">10. Masalah peralatan & tindak lanjutnya sudah dicatat? *</Label>
                                        <ToggleChoices
                                            value={form.data.extra_10_equipment_issue_recorded}
                                            options={[
                                                { value: 'sudah', label: 'Sudah' },
                                                { value: 'belum', label: 'Belum' },
                                                { value: 'tidak_ada_masalah', label: 'Tidak Ada Masalah' },
                                            ]}
                                            onChange={(val) => form.setData('extra_10_equipment_issue_recorded', val)}
                                            disabled={!permissions.canEdit}
                                            dangerValues={['belum']}
                                        />
                                    </div>
                                )}

                                <div className="space-y-1.5 rounded-lg border p-3">
                                    <Label className="text-xs">11. Jika ada komplikasi/kejadian penting, sudah disampaikan ke tim?</Label>
                                    <ToggleChoices
                                        value={form.data.extra_11_complication_communicated}
                                        options={[
                                            { value: 'sudah', label: 'Sudah' },
                                            { value: 'belum', label: 'Belum' },
                                            { value: 'tidak_ada_komplikasi', label: 'Tidak Ada Komplikasi' },
                                        ]}
                                        onChange={(val) => form.setData('extra_11_complication_communicated', val)}
                                        disabled={!permissions.canEdit}
                                        dangerValues={['belum']}
                                    />
                                </div>

                                <div className="space-y-1.5 rounded-lg border p-3">
                                    <Label className="text-xs">
                                        12. Kebutuhan pemantauan pasca-operasi (napas, perdarahan, nyeri) sudah dibahas?
                                    </Label>
                                    <ToggleChoices
                                        value={form.data.extra_12_postop_monitoring_discussed}
                                        options={[
                                            { value: 'sudah', label: 'Sudah' },
                                            { value: 'belum', label: 'Belum' },
                                        ]}
                                        onChange={(val) => form.setData('extra_12_postop_monitoring_discussed', val)}
                                        disabled={!permissions.canEdit}
                                        dangerValues={['belum']}
                                    />
                                </div>

                                <div className="space-y-1.5 rounded-lg border p-3">
                                    <Label className="text-xs">13. Kondisi alat terpasang & instruksi perawatan lanjutan sudah disampaikan?</Label>
                                    <ToggleChoices
                                        value={form.data.extra_13_devices_instructions}
                                        options={[
                                            { value: 'sudah', label: 'Sudah' },
                                            { value: 'belum', label: 'Belum' },
                                            { value: 'tidak_berlaku', label: 'Tidak Berlaku' },
                                        ]}
                                        onChange={(val) => form.setData('extra_13_devices_instructions', val)}
                                        disabled={!permissions.canEdit}
                                        dangerValues={['belum']}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 pt-2 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="recovery_notes">Catatan Rencana Pemulihan Pasien</Label>
                                    <textarea
                                        id="recovery_notes"
                                        rows={3}
                                        value={form.data.recovery_notes}
                                        onChange={(e) => form.setData('recovery_notes', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        placeholder="Hal penting yang perlu diperhatikan di ruang pemulihan..."
                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="issues_and_actions">Masalah yang Ditemukan & Tindakan yang Dilakukan</Label>
                                    <textarea
                                        id="issues_and_actions"
                                        rows={3}
                                        value={form.data.issues_and_actions}
                                        onChange={(e) => form.setData('issues_and_actions', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        placeholder="Wajib diisi jika ada selisih hitungan, masalah alat, atau kendala label spesimen..."
                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                    />
                                    <InputError message={form.errors.issues_and_actions} />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
                                {permissions.canEdit && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="status">Status Formulir Sign Out</Label>
                                        <Select value={form.data.status} onValueChange={(val) => form.setData('status', val)}>
                                            <SelectTrigger id="status" className="min-h-11">
                                                <SelectValue placeholder="Pilih status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.signOutStatuses.map((s) => (
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
                                        Petugas pengisi: <strong>{signOut.filled_by_name ?? 'Belum disimpan'}</strong>
                                    </div>
                                    <div>
                                        Waktu terverifikasi: <strong>{signOut.signed_at ? `${signOut.signed_at} WIB` : 'Belum terverifikasi'}</strong>
                                    </div>
                                </div>
                            </div>

                            {permissions.canEdit && (
                                <div className="flex justify-end pt-2">
                                    <Button type="submit" className="min-h-11" disabled={form.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {form.processing ? 'Menyimpan...' : 'Simpan SSC Sign Out'}
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </form>

                {/* Verifikasi Dokter Operator / Dokter Anestesi */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ShieldCheck className="text-primary h-5 w-5" />
                                    Verifikasi Dokter pada SSC Sign Out
                                </CardTitle>
                                <CardDescription>
                                    Dokter Operator dan Dokter Anestesi kasus ini dapat membubuhkan verifikasi Sign Out.
                                </CardDescription>
                            </div>
                            {permissions.canVerify && signOut.id !== null && (
                                <Button type="button" variant="outline" className="min-h-11" onClick={() => setVerifyOpen(true)}>
                                    <ShieldCheck className="mr-2 h-4 w-4" />
                                    Verifikasi Sign Out
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {verifications.length === 0 ? (
                            <div className="text-muted-foreground rounded-lg border border-dashed p-4 text-sm">
                                Belum ada verifikasi dokter pada formulir Sign Out ini.
                            </div>
                        ) : (
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                {verifications.map((v) => (
                                    <div
                                        key={v.id}
                                        className="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 text-sm text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100"
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
                    </CardContent>
                </Card>
            </div>

            <Dialog open={verifyOpen} onOpenChange={setVerifyOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Verifikasi SSC Sign Out</DialogTitle>
                        <DialogDescription>Masukkan kata sandi akun Anda untuk mengonfirmasi verifikasi SSC Sign Out.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleVerify} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="signout_verify_password">Kata Sandi</Label>
                            <Input
                                id="signout_verify_password"
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
                                {verifyForm.processing ? 'Memverifikasi...' : 'Konfirmasi Verifikasi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </CaseLayout>
    );
}
