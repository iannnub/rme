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
import CaseLayout, { type CaseHeaderData } from '@/layouts/case-layout';
import { cn } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, ClipboardCheck, Save } from 'lucide-react';
import { type FormEvent } from 'react';

type OptionItem = { value: string; label: string };

type TimeOutData = {
    id: number | null;
    updated_at: string | null;
    status: string;
    intro_circulating: boolean;
    intro_scrub: boolean;
    intro_anesthesia_nurse: boolean;
    intro_surgeon: boolean;
    intro_anesthesiologist: boolean;
    identity_procedure_site_confirmed: boolean;
    antibiotic_prophylaxis: string;
    antibiotic_name: string;
    antibiotic_given_at: string;
    antibiotic_confirmed_by: string;
    surgeon_critical_steps: string;
    surgeon_critical_steps_text: string;
    surgeon_estimated_duration_min: string | number;
    surgeon_estimated_blood_loss_ml: string | number;
    anesthesia_special_condition: string;
    anesthesia_risk: string;
    anesthesia_note: string;
    nurse_sterility_confirmed: boolean;
    nurse_indicator_checked: boolean;
    equipment_problem: string;
    other_concern: string;
    nurse_note: string;
    imaging_needed: string;
    imaging_displayed: string;
    imaging_matches: string;
    signed_at: string | null;
    filled_by_name: string | null;
};

interface SscTimeOutPageProps {
    caseHeader: CaseHeaderData;
    timeOut: TimeOutData;
    options: {
        sscStatuses: OptionItem[];
    };
    permissions: {
        canEditNurseBlocks: boolean;
        canEditSurgeonBlock: boolean;
        canEditAnesthesiaBlock: boolean;
    };
}

function YesNoButtons({
    value,
    options,
    onChange,
    disabled,
}: {
    value: string;
    options: OptionItem[];
    onChange: (val: string) => void;
    disabled?: boolean;
}) {
    return (
        <div className="flex flex-wrap gap-2">
            {options.map((opt) => (
                <button
                    key={opt.value}
                    type="button"
                    disabled={disabled}
                    onClick={() => onChange(opt.value)}
                    className={cn(
                        'min-h-11 rounded-md border px-4 py-2 text-sm font-medium transition-colors',
                        disabled && 'cursor-not-allowed opacity-60',
                        value === opt.value ? 'border-primary bg-primary text-primary-foreground' : 'border-input bg-background hover:bg-muted',
                    )}
                >
                    {opt.label}
                </button>
            ))}
        </div>
    );
}

const YES_NO: OptionItem[] = [
    { value: 'ya', label: 'Ya' },
    { value: 'tidak', label: 'Tidak' },
];

const YES_NA: OptionItem[] = [
    { value: 'ya', label: 'Ya' },
    { value: 'na', label: 'Tidak Diindikasikan (N/A)' },
];

export default function SscTimeOutPage({ caseHeader, timeOut, options, permissions }: SscTimeOutPageProps) {
    const form = useForm({
        status: timeOut.status || 'dalam_proses',
        intro_circulating: timeOut.intro_circulating,
        intro_scrub: timeOut.intro_scrub,
        intro_anesthesia_nurse: timeOut.intro_anesthesia_nurse,
        intro_surgeon: timeOut.intro_surgeon,
        intro_anesthesiologist: timeOut.intro_anesthesiologist,
        identity_procedure_site_confirmed: timeOut.identity_procedure_site_confirmed,
        antibiotic_prophylaxis: timeOut.antibiotic_prophylaxis,
        antibiotic_name: timeOut.antibiotic_name,
        antibiotic_given_at: timeOut.antibiotic_given_at,
        antibiotic_confirmed_by: timeOut.antibiotic_confirmed_by,
        surgeon_critical_steps: timeOut.surgeon_critical_steps,
        surgeon_critical_steps_text: timeOut.surgeon_critical_steps_text,
        surgeon_estimated_duration_min: timeOut.surgeon_estimated_duration_min === '' ? '' : String(timeOut.surgeon_estimated_duration_min),
        surgeon_estimated_blood_loss_ml: timeOut.surgeon_estimated_blood_loss_ml === '' ? '' : String(timeOut.surgeon_estimated_blood_loss_ml),
        anesthesia_special_condition: timeOut.anesthesia_special_condition,
        anesthesia_risk: timeOut.anesthesia_risk,
        anesthesia_note: timeOut.anesthesia_note,
        nurse_sterility_confirmed: timeOut.nurse_sterility_confirmed,
        nurse_indicator_checked: timeOut.nurse_indicator_checked,
        equipment_problem: timeOut.equipment_problem,
        other_concern: timeOut.other_concern,
        nurse_note: timeOut.nurse_note,
        imaging_needed: timeOut.imaging_needed,
        imaging_displayed: timeOut.imaging_displayed,
        imaging_matches: timeOut.imaging_matches,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            expected_updated_at: timeOut.updated_at ?? '',
        }));
        form.put(route('cases.ssc.time-out.update', caseHeader.id), {
            preserveScroll: true,
        });
    };

    const canEditAny = permissions.canEditNurseBlocks || permissions.canEditSurgeonBlock || permissions.canEditAnesthesiaBlock;
    const errorMessages = Object.values(form.errors as Record<string, string | undefined>).filter((m): m is string => Boolean(m));
    const statusLabel = options.sscStatuses.find((s) => s.value === timeOut.status)?.label ?? 'Belum Dimulai';
    const statusBadgeClass =
        timeOut.status === 'selesai'
            ? 'bg-emerald-600 text-white'
            : timeOut.status === 'dalam_proses'
              ? 'bg-amber-500 text-white'
              : 'bg-muted text-muted-foreground';

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="ssc">
            <Head title={`SSC Time Out - ${caseHeader.case_number}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                <ConcurrencyAlert message={(form.errors as Record<string, string | undefined>).concurrency} />

                <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href={route('cases.ssc.sign-in', caseHeader.id)}>1. Sign In (Sebelum Induksi)</Link>
                        </Button>
                        <Button asChild variant="default" className="min-h-11">
                            <Link href={route('cases.ssc.time-out', caseHeader.id)}>2. Time Out (Sebelum Insisi)</Link>
                        </Button>
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href={route('cases.ssc.sign-out', caseHeader.id)}>3. Sign Out (Akhir Operasi)</Link>
                        </Button>
                    </div>
                    <Badge className={statusBadgeClass}>Status Time Out: {statusLabel}</Badge>
                </div>

                {errorMessages.length > 0 && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Periksa isian SSC Time Out</AlertTitle>
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
                    {/* 1-3: Perkenalan, Konfirmasi, Antibiotik (Perawat) */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ClipboardCheck className="text-primary h-5 w-5" />
                                1. Perkenalan Tim, Konfirmasi & Antibiotik Profilaksis
                            </CardTitle>
                            <CardDescription>
                                Time Out wajib berstatus <strong>Selesai</strong> sebelum waktu mulai insisi dapat dicatat pada tab Intra-Op.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="space-y-2">
                                <Label className="text-sm font-semibold">1. Apakah seluruh anggota tim telah memperkenalkan nama dan perannya?</Label>
                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                                    {[
                                        { key: 'intro_circulating', label: 'Perawat Sirkuler', name: caseHeader.team.circulating_nurse },
                                        { key: 'intro_scrub', label: 'Perawat Instrumen', name: caseHeader.team.scrub_nurse },
                                        { key: 'intro_anesthesia_nurse', label: 'Perawat Anestesi', name: caseHeader.team.anesthesia_nurse },
                                        { key: 'intro_surgeon', label: 'Dokter Operator', name: caseHeader.team.surgeon },
                                        { key: 'intro_anesthesiologist', label: 'Dokter Anestesi', name: caseHeader.team.anesthesiologist },
                                    ].map((item) => {
                                        const k = item.key as
                                            | 'intro_circulating'
                                            | 'intro_scrub'
                                            | 'intro_anesthesia_nurse'
                                            | 'intro_surgeon'
                                            | 'intro_anesthesiologist';
                                        return (
                                            <label
                                                key={item.key}
                                                className="hover:bg-muted/40 flex cursor-pointer items-start gap-2.5 rounded-lg border p-3 text-sm"
                                            >
                                                <Checkbox
                                                    checked={form.data[k]}
                                                    disabled={!permissions.canEditNurseBlocks}
                                                    onCheckedChange={(checked) => form.setData(k, checked === true)}
                                                />
                                                <div className="leading-tight">
                                                    <div className="font-medium">{item.label}</div>
                                                    <div className="text-muted-foreground text-xs">{item.name ?? '-'}</div>
                                                </div>
                                            </label>
                                        );
                                    })}
                                </div>
                            </div>

                            <label className="hover:bg-muted/40 flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm">
                                <Checkbox
                                    checked={form.data.identity_procedure_site_confirmed}
                                    disabled={!permissions.canEditNurseBlocks}
                                    onCheckedChange={(checked) => form.setData('identity_procedure_site_confirmed', checked === true)}
                                />
                                <span className="font-medium">
                                    2. Konfirmasi identitas pasien ({caseHeader.patient.name} / {caseHeader.patient.rm_number}), jenis tindakan (
                                    {caseHeader.surgery_type}), dan lokasi operasi ({caseHeader.surgery_site}) telah dikonfirmasi bersama tim.
                                </span>
                            </label>

                            <div className="space-y-3">
                                <Label className="text-sm font-semibold">
                                    3. Apakah antibiotik profilaksis telah diberikan dalam 60 menit terakhir jika diindikasikan?
                                </Label>
                                <YesNoButtons
                                    value={form.data.antibiotic_prophylaxis}
                                    options={YES_NA}
                                    onChange={(val) => form.setData('antibiotic_prophylaxis', val)}
                                    disabled={!permissions.canEditNurseBlocks}
                                />

                                {form.data.antibiotic_prophylaxis === 'ya' && (
                                    <div className="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-3">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="antibiotic_name">Nama Antibiotik *</Label>
                                            <Input
                                                id="antibiotic_name"
                                                value={form.data.antibiotic_name}
                                                onChange={(e) => form.setData('antibiotic_name', e.target.value)}
                                                disabled={!permissions.canEditNurseBlocks}
                                                placeholder="mis. Cefazolin 1g IV"
                                                className="min-h-11"
                                            />
                                            <InputError message={form.errors.antibiotic_name} />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="antibiotic_given_at">Waktu Pemberian *</Label>
                                            <Input
                                                id="antibiotic_given_at"
                                                type="datetime-local"
                                                value={form.data.antibiotic_given_at}
                                                onChange={(e) => form.setData('antibiotic_given_at', e.target.value)}
                                                disabled={!permissions.canEditNurseBlocks}
                                                className="min-h-11"
                                            />
                                            <InputError message={form.errors.antibiotic_given_at} />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="antibiotic_confirmed_by">Petugas Konfirmasi</Label>
                                            <Input
                                                id="antibiotic_confirmed_by"
                                                value={form.data.antibiotic_confirmed_by}
                                                onChange={(e) => form.setData('antibiotic_confirmed_by', e.target.value)}
                                                disabled={!permissions.canEditNurseBlocks}
                                                placeholder="Nama petugas"
                                                className="min-h-11"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Blok Dokter Operator */}
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <CardTitle className="text-base">Blok Dokter Operator</CardTitle>
                                    <CardDescription>
                                        Diisi oleh Dokter Operator yang ditetapkan pada kasus ini ({caseHeader.team.surgeon ?? 'Belum ditetapkan'}).
                                    </CardDescription>
                                </div>
                                <Badge variant={permissions.canEditSurgeonBlock ? 'default' : 'outline'}>
                                    {permissions.canEditSurgeonBlock ? 'Anda dapat mengisi blok ini' : 'Khusus Dokter Operator Kasus'}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-1.5">
                                <Label>Apakah terdapat langkah kritis atau tindakan yang tidak rutin?</Label>
                                <YesNoButtons
                                    value={form.data.surgeon_critical_steps}
                                    options={YES_NO}
                                    onChange={(val) => form.setData('surgeon_critical_steps', val)}
                                    disabled={!permissions.canEditSurgeonBlock}
                                />
                            </div>
                            {form.data.surgeon_critical_steps === 'ya' && (
                                <div className="space-y-1.5">
                                    <Label htmlFor="surgeon_critical_steps_text">Uraian Langkah Kritis *</Label>
                                    <textarea
                                        id="surgeon_critical_steps_text"
                                        rows={2}
                                        value={form.data.surgeon_critical_steps_text}
                                        onChange={(e) => form.setData('surgeon_critical_steps_text', e.target.value)}
                                        disabled={!permissions.canEditSurgeonBlock}
                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                    />
                                    <InputError message={form.errors.surgeon_critical_steps_text} />
                                </div>
                            )}
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="surgeon_estimated_duration_min">Perkiraan Lama Operasi (menit)</Label>
                                    <Input
                                        id="surgeon_estimated_duration_min"
                                        type="number"
                                        min={1}
                                        value={form.data.surgeon_estimated_duration_min}
                                        onChange={(e) => form.setData('surgeon_estimated_duration_min', e.target.value)}
                                        disabled={!permissions.canEditSurgeonBlock}
                                        className="min-h-11"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="surgeon_estimated_blood_loss_ml">Perkiraan Kehilangan Darah (ml)</Label>
                                    <Input
                                        id="surgeon_estimated_blood_loss_ml"
                                        type="number"
                                        min={0}
                                        value={form.data.surgeon_estimated_blood_loss_ml}
                                        onChange={(e) => form.setData('surgeon_estimated_blood_loss_ml', e.target.value)}
                                        disabled={!permissions.canEditSurgeonBlock}
                                        className="min-h-11"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Blok Dokter Anestesi */}
                    <Card>
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <CardTitle className="text-base">Blok Dokter Anestesi</CardTitle>
                                    <CardDescription>
                                        Diisi oleh Dokter Anestesi kasus ini ({caseHeader.team.anesthesiologist ?? 'Belum ditetapkan'}).
                                    </CardDescription>
                                </div>
                                <Badge variant={permissions.canEditAnesthesiaBlock ? 'default' : 'outline'}>
                                    {permissions.canEditAnesthesiaBlock ? 'Anda dapat mengisi blok ini' : 'Khusus Dokter Anestesi Kasus'}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label>Apakah terdapat kondisi khusus pada pasien yang perlu diperhatikan?</Label>
                                    <YesNoButtons
                                        value={form.data.anesthesia_special_condition}
                                        options={YES_NO}
                                        onChange={(val) => form.setData('anesthesia_special_condition', val)}
                                        disabled={!permissions.canEditAnesthesiaBlock}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Apakah terdapat risiko terkait kondisi anestesi pasien?</Label>
                                    <YesNoButtons
                                        value={form.data.anesthesia_risk}
                                        options={YES_NO}
                                        onChange={(val) => form.setData('anesthesia_risk', val)}
                                        disabled={!permissions.canEditAnesthesiaBlock}
                                    />
                                </div>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="anesthesia_note">Keterangan Dokter Anestesi</Label>
                                <textarea
                                    id="anesthesia_note"
                                    rows={2}
                                    value={form.data.anesthesia_note}
                                    onChange={(e) => form.setData('anesthesia_note', e.target.value)}
                                    disabled={!permissions.canEditAnesthesiaBlock}
                                    className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Blok Tim Perawat & Pencitraan Medis */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Blok Tim Perawat & Penampilan Pencitraan Medis</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <label className="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm">
                                    <Checkbox
                                        checked={form.data.nurse_sterility_confirmed}
                                        disabled={!permissions.canEditNurseBlocks}
                                        onCheckedChange={(checked) => form.setData('nurse_sterility_confirmed', checked === true)}
                                    />
                                    <span>Sterilitas instrumen sudah dikonfirmasi</span>
                                </label>
                                <label className="flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm">
                                    <Checkbox
                                        checked={form.data.nurse_indicator_checked}
                                        disabled={!permissions.canEditNurseBlocks}
                                        onCheckedChange={(checked) => form.setData('nurse_indicator_checked', checked === true)}
                                    />
                                    <span>Hasil indikator sterilisasi sudah diperiksa</span>
                                </label>
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label>Apakah terdapat masalah pada peralatan operasi?</Label>
                                    <YesNoButtons
                                        value={form.data.equipment_problem}
                                        options={YES_NO}
                                        onChange={(val) => form.setData('equipment_problem', val)}
                                        disabled={!permissions.canEditNurseBlocks}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label>Apakah terdapat kekhawatiran lain terkait kesiapan operasi?</Label>
                                    <YesNoButtons
                                        value={form.data.other_concern}
                                        options={YES_NO}
                                        onChange={(val) => form.setData('other_concern', val)}
                                        disabled={!permissions.canEditNurseBlocks}
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="nurse_note">Catatan Tim Perawat</Label>
                                <Input
                                    id="nurse_note"
                                    value={form.data.nurse_note}
                                    onChange={(e) => form.setData('nurse_note', e.target.value)}
                                    disabled={!permissions.canEditNurseBlocks}
                                    placeholder="Catatan kendala alat/kesiapan jika ada"
                                    className="min-h-11"
                                />
                                <InputError message={form.errors.nurse_note} />
                            </div>

                            <div className="space-y-3 border-t pt-4">
                                <div className="space-y-1.5">
                                    <Label className="font-semibold">Apakah pencitraan medis diperlukan untuk operasi ini?</Label>
                                    <YesNoButtons
                                        value={form.data.imaging_needed}
                                        options={YES_NO}
                                        onChange={(val) => form.setData('imaging_needed', val)}
                                        disabled={!permissions.canEditNurseBlocks}
                                    />
                                </div>

                                {form.data.imaging_needed === 'ya' && (
                                    <div className="grid grid-cols-1 gap-4 rounded-lg border p-4 md:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label>Apakah hasil pencitraan sudah ditampilkan?</Label>
                                            <YesNoButtons
                                                value={form.data.imaging_displayed}
                                                options={YES_NO}
                                                onChange={(val) => form.setData('imaging_displayed', val)}
                                                disabled={!permissions.canEditNurseBlocks}
                                            />
                                        </div>
                                        <div className="space-y-1.5">
                                            <Label>Apakah hasil pencitraan sesuai dengan pasien & lokasi operasi?</Label>
                                            <YesNoButtons
                                                value={form.data.imaging_matches}
                                                options={YES_NO}
                                                onChange={(val) => form.setData('imaging_matches', val)}
                                                disabled={!permissions.canEditNurseBlocks}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
                                {permissions.canEditNurseBlocks && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="status">Status Penyelesaian Time Out</Label>
                                        <Select value={form.data.status} onValueChange={(val) => form.setData('status', val)}>
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
                                        Terakhir diisi: <strong>{timeOut.filled_by_name ?? 'Belum disimpan'}</strong>
                                    </div>
                                    <div>
                                        Waktu selesai Time Out: <strong>{timeOut.signed_at ? `${timeOut.signed_at} WIB` : 'Belum selesai'}</strong>
                                    </div>
                                </div>
                            </div>

                            {canEditAny && (
                                <div className="flex justify-end pt-2">
                                    <Button type="submit" className="min-h-11" disabled={form.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {form.processing ? 'Menyimpan...' : 'Simpan SSC Time Out'}
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </form>
            </div>
        </CaseLayout>
    );
}
