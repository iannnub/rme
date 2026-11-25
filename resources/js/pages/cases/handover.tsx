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
import CaseLayout, { type CaseHeaderData } from '@/layouts/case-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, ClipboardList, Download, Lock, Save, Send } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type OptionItem = { value: string; label: string };
type SummaryItem = { key: string; label: string; value: string };

interface HandoverPageProps {
    caseHeader: CaseHeaderData;
    canHandoverNow: boolean;
    summaryItems: SummaryItem[];
    handover: {
        id: number | null;
        destination: string;
        destination_detail: string;
        documents_complete: boolean;
        handed_by_name: string;
        received_by_name: string;
        handed_over_at: string;
        ongoing_issues: string;
        follow_up_instructions: string;
    };
    options: {
        destinations: OptionItem[];
    };
    permissions: {
        canEdit: boolean;
    };
}

export default function HandoverPage({ caseHeader, canHandoverNow, summaryItems, handover, options, permissions }: HandoverPageProps) {
    const form = useForm({
        destination: handover.destination,
        destination_detail: handover.destination_detail,
        documents_complete: handover.documents_complete,
        received_by_name: handover.received_by_name,
        handed_over_at: handover.handed_over_at,
        ongoing_issues: handover.ongoing_issues,
        follow_up_instructions: handover.follow_up_instructions,
        complete_case: false,
    });

    const [confirmFinishOpen, setConfirmFinishOpen] = useState(false);

    const submitDraft = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({ ...data, complete_case: false }));
        form.put(route('cases.handover.store', caseHeader.id), { preserveScroll: true });
    };

    const submitAndFinish = () => {
        form.transform((data) => ({ ...data, complete_case: true }));
        form.put(route('cases.handover.store', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => setConfirmFinishOpen(false),
        });
    };

    const isNeuro = caseHeader.surgery_profile === 'bedah_saraf';
    const destinationOptions = isNeuro ? options.destinations.filter((d) => d.value !== 'lainnya') : options.destinations;

    const formErrors = form.errors as Record<string, string | undefined>;

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="serah-terima">
            <Head title={`Serah Terima - ${caseHeader.case_number}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                {caseHeader.status === 'selesai' && (
                    <Alert className="border-emerald-300 bg-emerald-50/70 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-100">
                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        <AlertTitle>Kasus Operasi Telah Selesai</AlertTitle>
                        <AlertDescription>
                            Serah terima pasien telah diselesaikan dan catatan keperawatan terkunci untuk mencegah perubahan tidak sah.
                        </AlertDescription>
                    </Alert>
                )}

                {!canHandoverNow && caseHeader.status !== 'selesai' && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Kondisi Sebelum Pindah Belum Tercatat</AlertTitle>
                        <AlertDescription className="flex flex-wrap items-center justify-between gap-2">
                            <span>
                                Serah terima hanya dapat disimpan setelah minimal 1 observasi pasca-operasi dan kondisi pasien sebelum dipindahkan
                                (dinyatakan layak pindah) dicatat pada tab Pasca-Op.
                            </span>
                            <Button asChild size="sm" variant="outline" className="bg-background text-foreground">
                                <Link href={route('cases.postop', caseHeader.id)}>Lengkapi Tab Pasca-Op</Link>
                            </Button>
                        </AlertDescription>
                    </Alert>
                )}

                {formErrors.handover && (
                    <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Tidak Dapat Menyimpan Serah Terima</AlertTitle>
                        <AlertDescription>{formErrors.handover}</AlertDescription>
                    </Alert>
                )}

                {/* Ringkasan Otomatis 12 Item SKPL V.E */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ClipboardList className="text-primary h-5 w-5" />
                                    Ringkasan Otomatis Serah Terima Pasien (12 Item SKPL V.E)
                                </CardTitle>
                                <CardDescription>
                                    Dirangkum otomatis dari data Pre-Op, Intra-Op, SSC, dan Pasca-Op; disimpan sebagai snapshot permanen saat serah
                                    terima.
                                </CardDescription>
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="secondary">Read-only (Otomatis)</Badge>
                                <Button asChild variant="outline" size="sm" className="min-h-10">
                                    <a href={route('cases.pdf.handover', caseHeader.id)}>
                                        <Download className="mr-1.5 h-4 w-4" />
                                        Cetak PDF Serah Terima
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                            {summaryItems.map((item) => (
                                <div key={item.key} className="bg-muted/30 rounded-lg border p-3 text-sm">
                                    <div className="text-muted-foreground text-xs font-semibold">{item.label}</div>
                                    <div className="text-foreground mt-1">{item.value}</div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Form Serah Terima */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Formulir Pemindahan & Serah Terima Pasien (KF-18 & KF-37)</CardTitle>
                        <CardDescription>Lengkapi tujuan pemindahan, petugas penerima, keluhan aktif, dan instruksi tindak lanjut.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitDraft} className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="destination">Tujuan Pemindahan *</Label>
                                    <Select
                                        value={form.data.destination}
                                        onValueChange={(v) => form.setData('destination', v)}
                                        disabled={!permissions.canEdit}
                                    >
                                        <SelectTrigger id="destination" className="min-h-11">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {destinationOptions.map((d) => (
                                                <SelectItem key={d.value} value={d.value}>
                                                    {d.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.destination} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="destination_detail">
                                        Detail Ruang Tujuan {form.data.destination === 'lainnya' ? '*' : '(Opsional)'}
                                    </Label>
                                    <Input
                                        id="destination_detail"
                                        value={form.data.destination_detail}
                                        onChange={(e) => form.setData('destination_detail', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        placeholder="mis. Ruang Melati 2 Bed 4"
                                        className="min-h-11"
                                    />
                                    <InputError message={form.errors.destination_detail} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label>Nama Perawat Penyerah (Otomatis)</Label>
                                    <Input value={handover.handed_by_name} disabled className="bg-muted/50 min-h-11" />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="received_by_name">Nama Petugas Penerima *</Label>
                                    <Input
                                        id="received_by_name"
                                        value={form.data.received_by_name}
                                        onChange={(e) => form.setData('received_by_name', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        placeholder="Nama lengkap perawat ruangan / PACU / ICU"
                                        className="min-h-11"
                                    />
                                    <InputError message={form.errors.received_by_name} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="handed_over_at">Waktu Serah Terima *</Label>
                                    <Input
                                        id="handed_over_at"
                                        type="datetime-local"
                                        value={form.data.handed_over_at}
                                        onChange={(e) => form.setData('handed_over_at', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        className="min-h-11"
                                    />
                                    <InputError message={form.errors.handed_over_at} />
                                </div>

                                <div className="flex items-end">
                                    <label className="flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-md border px-3 text-sm font-medium">
                                        <Checkbox
                                            checked={form.data.documents_complete}
                                            disabled={!permissions.canEdit}
                                            onCheckedChange={(c) => form.setData('documents_complete', c === true)}
                                        />
                                        <span>Kelengkapan dokumen rekam medis & hasil penunjang lengkap</span>
                                    </label>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="ongoing_issues">Keluhan / Masalah yang Masih Ada</Label>
                                    <textarea
                                        id="ongoing_issues"
                                        rows={3}
                                        value={form.data.ongoing_issues}
                                        onChange={(e) => form.setData('ongoing_issues', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        placeholder="Keluhan nyeri, mual, atau kondisi khusus yang masih perlu perhatian..."
                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="follow_up_instructions">Instruksi & Tindak Lanjut Perawatan</Label>
                                    <textarea
                                        id="follow_up_instructions"
                                        rows={3}
                                        value={form.data.follow_up_instructions}
                                        onChange={(e) => form.setData('follow_up_instructions', e.target.value)}
                                        disabled={!permissions.canEdit}
                                        placeholder="Jadwal pemantauan TTV, cairan, analgetik, perawatan drain/luka..."
                                        className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm disabled:opacity-60"
                                    />
                                </div>
                            </div>

                            {permissions.canEdit && (
                                <div className="flex flex-wrap items-center justify-end gap-3 border-t pt-4">
                                    <Button type="submit" variant="outline" className="min-h-11" disabled={form.processing || !canHandoverNow}>
                                        <Save className="mr-2 h-4 w-4" />
                                        Simpan Draf Serah Terima
                                    </Button>
                                    <Button
                                        type="button"
                                        className="min-h-11"
                                        disabled={form.processing || !canHandoverNow}
                                        onClick={() => setConfirmFinishOpen(true)}
                                    >
                                        <Send className="mr-2 h-4 w-4" />
                                        Simpan & Selesaikan Kasus
                                    </Button>
                                </div>
                            )}
                        </form>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={confirmFinishOpen} onOpenChange={setConfirmFinishOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Lock className="text-primary h-5 w-5" />
                            Selesaikan Kasus Operasi?
                        </DialogTitle>
                        <DialogDescription>
                            Setelah status kasus diubah menjadi <strong>Selesai</strong>, seluruh formulir keperawatan akan dikunci dan tidak dapat
                            diedit lagi oleh perawat. Pastikan data serah terima sudah benar.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setConfirmFinishOpen(false)}>
                            Batal
                        </Button>
                        <Button type="button" disabled={form.processing} onClick={submitAndFinish}>
                            {form.processing ? 'Memproses...' : 'Ya, Selesaikan Kasus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </CaseLayout>
    );
}
