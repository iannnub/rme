import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CaseLayout, { type CaseHeaderData } from '@/layouts/case-layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowRight, CheckCircle2, Circle, Pencil } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type MasterOption = {
    id: number;
    name: string;
    code?: string;
    default_medical_diagnosis_id?: number | null;
    default_procedure_name?: string | null;
};

type CaseDetailData = {
    id: number;
    igd_visit_number: string;
    admitted_at_input: string;
    igd_admitted_at_input: string;
    scheduled_at_input: string;
    origin_ward: string;
    origin_class: string | null;
    urgency_status: string;
    medical_diagnosis_id: number | null;
    medical_diagnosis_other: string | null;
    surgery_type_id: number | null;
    surgery_type_other: string | null;
    surgery_site: string | null;
    anesthesia_type_id: number | null;
    dpjp_name: string;
    postop_diagnosis: string;
    ready_at: string | null;
    entered_or_at: string | null;
    surgery_end_at: string | null;
    left_or_at: string | null;
    anesthesia_end_at: string | null;
    completed_at: string | null;
    created_by_name: string;
};

type EditCaseFormData = {
    igd_visit_number: string;
    admitted_at: string;
    igd_admitted_at: string;
    origin_ward: string;
    origin_class: string;
    scheduled_at: string;
    urgency_status: string;
    dpjp_name: string;
    postop_diagnosis: string;
    medical_diagnosis_id: string;
    medical_diagnosis_other: string;
    surgery_type_id: string;
    surgery_type_other: string;
    surgery_site: string;
    anesthesia_type_id: string;
};

interface CaseShowProps {
    caseHeader: CaseHeaderData;
    caseDetail: CaseDetailData;
    medicalDiagnoses: MasterOption[];
    surgeryTypes: MasterOption[];
    anesthesiaTypes: MasterOption[];
    canUpdate: boolean;
    canAdvance: boolean;
}

export default function CaseShow({ caseHeader, caseDetail, medicalDiagnoses, surgeryTypes, anesthesiaTypes, canUpdate, canAdvance }: CaseShowProps) {
    const pageErrors = usePage().props.errors as Record<string, string | string[] | undefined>;
    const transitionErrorRaw = pageErrors.transition;
    const transitionErrors = Array.isArray(transitionErrorRaw)
        ? transitionErrorRaw
        : typeof transitionErrorRaw === 'string' && transitionErrorRaw !== ''
          ? [transitionErrorRaw]
          : [];

    const [editOpen, setEditOpen] = useState(false);
    const [advanceOpen, setAdvanceOpen] = useState(false);

    const editForm = useForm<EditCaseFormData>({
        igd_visit_number: caseDetail.igd_visit_number ?? '',
        admitted_at: caseDetail.admitted_at_input,
        igd_admitted_at: caseDetail.igd_admitted_at_input ?? '',
        origin_ward: caseDetail.origin_ward,
        origin_class: caseDetail.origin_class ?? '',
        scheduled_at: caseDetail.scheduled_at_input,
        urgency_status: caseDetail.urgency_status ?? 'emergency',
        dpjp_name: caseDetail.dpjp_name ?? '',
        postop_diagnosis: caseDetail.postop_diagnosis ?? '',
        medical_diagnosis_id: caseDetail.medical_diagnosis_id
            ? String(caseDetail.medical_diagnosis_id)
            : caseDetail.medical_diagnosis_other
              ? 'other'
              : '',
        medical_diagnosis_other: caseDetail.medical_diagnosis_other ?? '',
        surgery_type_id: caseDetail.surgery_type_id ? String(caseDetail.surgery_type_id) : caseDetail.surgery_type_other ? 'other' : '',
        surgery_type_other: caseDetail.surgery_type_other ?? '',
        surgery_site: caseDetail.surgery_site ?? '',
        anesthesia_type_id: caseDetail.anesthesia_type_id ? String(caseDetail.anesthesia_type_id) : '',
    });

    const advanceForm = useForm<{ target_status: string; reason: string }>({
        target_status: caseHeader.next_transition?.target ?? '',
        reason: '',
    });

    const submitEdit: FormEventHandler = (e) => {
        e.preventDefault();
        editForm.put(route('cases.update', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => setEditOpen(false),
        });
    };

    const submitAdvance: FormEventHandler = (e) => {
        e.preventDefault();
        if (!caseHeader.next_transition) {
            return;
        }
        advanceForm.transform((data) => ({
            ...data,
            target_status: caseHeader.next_transition?.target ?? '',
        }));
        advanceForm.post(route('cases.advance-status', caseHeader.id), {
            preserveScroll: true,
            onSuccess: () => setAdvanceOpen(false),
            onError: () => setAdvanceOpen(false),
        });
    };

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="ringkasan">
            <Head title={`Kasus ${caseHeader.case_number}`} />

            <div className="space-y-6">
                {transitionErrors.length > 0 && (
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>Tahap Belum Dapat Dilanjutkan</AlertTitle>
                        <AlertDescription>
                            <ul className="mt-1 list-disc space-y-1 pl-4 text-sm">
                                {transitionErrors.map((err, idx) => (
                                    <li key={idx}>{err}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                {caseHeader.next_transition && (
                    <Card>
                        <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle className="text-lg">Syarat Menuju Tahap: {caseHeader.next_transition.target_label}</CardTitle>
                                <CardDescription>
                                    Status hanya dapat dilanjutkan di server setelah seluruh syarat tahap ini terpenuhi.
                                </CardDescription>
                            </div>
                            {canAdvance && (
                                <Button type="button" onClick={() => setAdvanceOpen(true)}>
                                    Lanjut ke {caseHeader.next_transition.target_label}
                                    <ArrowRight className="h-4 w-4" />
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-2 sm:grid-cols-2">
                                {caseHeader.next_transition.checklist.map((item, idx) => (
                                    <div key={idx} className="bg-muted/20 flex items-start gap-2.5 rounded-lg border p-3 text-sm">
                                        {item.met ? (
                                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" />
                                        ) : (
                                            <Circle className="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
                                        )}
                                        <span className={item.met ? 'text-foreground' : 'text-muted-foreground'}>{item.label}</span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base">Informasi Dasar & Jadwal Operasi</CardTitle>
                                <CardDescription>Dibuat oleh {caseDetail.created_by_name}</CardDescription>
                            </div>
                            {canUpdate && (
                                <Button type="button" variant="outline" size="sm" onClick={() => setEditOpen(true)}>
                                    <Pencil className="h-3.5 w-3.5" />
                                    Edit Info Dasar
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <p className="text-muted-foreground text-xs">Nomor Kasus</p>
                                <p className="font-mono font-semibold">{caseHeader.case_number}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Status Tindakan</p>
                                <p className="font-medium">{caseHeader.urgency_label ?? 'Operasi Darurat (Emergency)'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Jadwal Operasi</p>
                                <p className="font-medium">{caseHeader.scheduled_at} WIB</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Masuk Kamar Operasi</p>
                                <p className="font-medium">{caseHeader.admitted_at} WIB</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">No. Kunjungan IGD</p>
                                <p className="font-mono font-medium">{caseHeader.igd_visit_number || '-'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Waktu Masuk IGD</p>
                                <p className="font-medium">{caseHeader.igd_admitted_at ? `${caseHeader.igd_admitted_at} WIB` : '-'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Ruang Asal / Kelas</p>
                                <p className="font-medium">
                                    {caseHeader.origin_ward}
                                    {caseHeader.origin_class ? ` (${caseHeader.origin_class})` : ''}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">DPJP</p>
                                <p className="font-medium">{caseHeader.dpjp_name || '-'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Diagnosis Medis</p>
                                <p className="font-medium">{caseHeader.diagnosis}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Jenis Operasi</p>
                                <p className="font-medium">{caseHeader.surgery_type}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Diagnosis Pasca-Operasi</p>
                                <p className="font-medium">{caseHeader.postop_diagnosis || '-'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Lokasi Operasi</p>
                                <p className="font-medium">{caseHeader.surgery_site}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Jenis Anestesi</p>
                                <p className="font-medium">{caseHeader.anesthesia_type}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Penanggung Jawab Pasien</p>
                                <p className="font-medium">
                                    {caseHeader.patient.guardian_name
                                        ? `${caseHeader.patient.guardian_name}${caseHeader.patient.guardian_relation ? ` (${caseHeader.patient.guardian_relation})` : ''}${caseHeader.patient.guardian_phone ? ` - ${caseHeader.patient.guardian_phone}` : ''}`
                                        : '-'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Ruang, Tim Operasi & Penanda Waktu</CardTitle>
                            <CardDescription>Penetapan ruang dan tim lengkap dikelola di tab Persiapan (Fase 4).</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <p className="text-muted-foreground text-xs">Kamar Operasi</p>
                                <p className="font-medium">
                                    {caseHeader.room ? `${caseHeader.room.code} - ${caseHeader.room.name}` : 'Belum ditetapkan'}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Dokter Operator</p>
                                <p className="font-medium">{caseHeader.team.surgeon ?? 'Belum ditetapkan'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Dokter Anestesi</p>
                                <p className="font-medium">{caseHeader.team.anesthesiologist ?? 'Belum ditetapkan'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Perawat Sirkuler</p>
                                <p className="font-medium">{caseHeader.team.circulating_nurse ?? 'Belum ditetapkan'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Perawat Instrumen</p>
                                <p className="font-medium">{caseHeader.team.scrub_nurse ?? 'Belum ditetapkan'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Perawat Anestesi</p>
                                <p className="font-medium">{caseHeader.team.anesthesia_nurse ?? 'Belum ditetapkan'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Siap Operasi</p>
                                <p className="font-medium">{caseDetail.ready_at ? `${caseDetail.ready_at} WIB` : '-'}</p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs">Selesai Kasus</p>
                                <p className="font-medium">{caseDetail.completed_at ? `${caseDetail.completed_at} WIB` : '-'}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Modal Edit Informasi Dasar */}
            <Dialog open={editOpen} onOpenChange={setEditOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Edit Informasi Dasar Kasus</DialogTitle>
                        <DialogDescription>Perubahan dicatat otomatis pada tab Riwayat perubahan.</DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="edit_igd_visit_number">No. Kunjungan IGD</Label>
                                <Input
                                    id="edit_igd_visit_number"
                                    value={editForm.data.igd_visit_number}
                                    onChange={(e) => editForm.setData('igd_visit_number', e.target.value)}
                                    placeholder="contoh: IGD-20261123-001"
                                />
                                <InputError message={editForm.errors.igd_visit_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_igd_admitted_at">Waktu Masuk IGD</Label>
                                <Input
                                    id="edit_igd_admitted_at"
                                    type="datetime-local"
                                    value={editForm.data.igd_admitted_at}
                                    onChange={(e) => editForm.setData('igd_admitted_at', e.target.value)}
                                />
                                <InputError message={editForm.errors.igd_admitted_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_admitted_at">Masuk Kamar Operasi</Label>
                                <Input
                                    id="edit_admitted_at"
                                    type="datetime-local"
                                    value={editForm.data.admitted_at}
                                    onChange={(e) => editForm.setData('admitted_at', e.target.value)}
                                    required
                                />
                                <InputError message={editForm.errors.admitted_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_scheduled_at">Jadwal Operasi</Label>
                                <Input
                                    id="edit_scheduled_at"
                                    type="datetime-local"
                                    value={editForm.data.scheduled_at}
                                    onChange={(e) => editForm.setData('scheduled_at', e.target.value)}
                                    required
                                />
                                <InputError message={editForm.errors.scheduled_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_origin_ward">Ruang Asal</Label>
                                <Input
                                    id="edit_origin_ward"
                                    value={editForm.data.origin_ward}
                                    onChange={(e) => editForm.setData('origin_ward', e.target.value)}
                                    required
                                />
                                <InputError message={editForm.errors.origin_ward} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_origin_class">Kelas (Opsional)</Label>
                                <Input
                                    id="edit_origin_class"
                                    value={editForm.data.origin_class}
                                    onChange={(e) => editForm.setData('origin_class', e.target.value)}
                                />
                                <InputError message={editForm.errors.origin_class} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_urgency_status">Status Tindakan</Label>
                                <Select value={editForm.data.urgency_status} onValueChange={(v) => editForm.setData('urgency_status', v)}>
                                    <SelectTrigger id="edit_urgency_status">
                                        <SelectValue placeholder="Pilih status tindakan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="emergency">Operasi Darurat (Emergency)</SelectItem>
                                        <SelectItem value="elektif">Operasi Elektif (Terjadwal)</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={editForm.errors.urgency_status} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_dpjp_name">Dokter Penanggung Jawab (DPJP)</Label>
                                <Input
                                    id="edit_dpjp_name"
                                    value={editForm.data.dpjp_name}
                                    onChange={(e) => editForm.setData('dpjp_name', e.target.value)}
                                    placeholder="Nama DPJP"
                                />
                                <InputError message={editForm.errors.dpjp_name} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="edit_surgery_type">Jenis Operasi</Label>
                            <Select
                                value={editForm.data.surgery_type_id || 'none'}
                                onValueChange={(v) => {
                                    const nextVal = v === 'none' ? '' : v;
                                    const chosen = surgeryTypes.find((item) => String(item.id) === nextVal);
                                    editForm.setData((prev) => ({
                                        ...prev,
                                        surgery_type_id: nextVal,
                                        medical_diagnosis_id: chosen?.default_medical_diagnosis_id
                                            ? String(chosen.default_medical_diagnosis_id)
                                            : prev.medical_diagnosis_id,
                                    }));
                                }}
                            >
                                <SelectTrigger id="edit_surgery_type">
                                    <SelectValue placeholder="Pilih jenis operasi" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Belum ditentukan</SelectItem>
                                    {surgeryTypes.map((item) => (
                                        <SelectItem key={item.id} value={String(item.id)}>
                                            {item.name}
                                        </SelectItem>
                                    ))}
                                    <SelectItem value="other">Lainnya (ketik manual)</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={editForm.errors.surgery_type_id} />
                        </div>

                        {editForm.data.surgery_type_id === 'other' && (
                            <div className="grid gap-2">
                                <Label htmlFor="edit_surgery_type_other">Jenis Operasi (Lainnya)</Label>
                                <Input
                                    id="edit_surgery_type_other"
                                    value={editForm.data.surgery_type_other}
                                    onChange={(e) => editForm.setData('surgery_type_other', e.target.value)}
                                />
                                <InputError message={editForm.errors.surgery_type_other} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="edit_diagnosis">Diagnosis Medis</Label>
                            <Select
                                value={editForm.data.medical_diagnosis_id || 'none'}
                                onValueChange={(v) => editForm.setData('medical_diagnosis_id', v === 'none' ? '' : v)}
                            >
                                <SelectTrigger id="edit_diagnosis">
                                    <SelectValue placeholder="Pilih diagnosis" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Belum ditentukan</SelectItem>
                                    {medicalDiagnoses.map((item) => (
                                        <SelectItem key={item.id} value={String(item.id)}>
                                            {item.name}
                                        </SelectItem>
                                    ))}
                                    <SelectItem value="other">Lainnya (ketik manual)</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={editForm.errors.medical_diagnosis_id} />
                        </div>

                        {editForm.data.medical_diagnosis_id === 'other' && (
                            <div className="grid gap-2">
                                <Label htmlFor="edit_diagnosis_other">Diagnosis Medis (Lainnya)</Label>
                                <Input
                                    id="edit_diagnosis_other"
                                    value={editForm.data.medical_diagnosis_other}
                                    onChange={(e) => editForm.setData('medical_diagnosis_other', e.target.value)}
                                />
                                <InputError message={editForm.errors.medical_diagnosis_other} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="edit_postop_diagnosis">Diagnosis Pasca-Operasi</Label>
                            <Input
                                id="edit_postop_diagnosis"
                                value={editForm.data.postop_diagnosis}
                                onChange={(e) => editForm.setData('postop_diagnosis', e.target.value)}
                                placeholder="Diagnosis setelah tindakan operasi"
                            />
                            <InputError message={editForm.errors.postop_diagnosis} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="edit_surgery_site">Lokasi Operasi</Label>
                                <Input
                                    id="edit_surgery_site"
                                    value={editForm.data.surgery_site}
                                    onChange={(e) => editForm.setData('surgery_site', e.target.value)}
                                />
                                <InputError message={editForm.errors.surgery_site} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit_anesthesia">Jenis Anestesi</Label>
                                <Select
                                    value={editForm.data.anesthesia_type_id || 'none'}
                                    onValueChange={(v) => editForm.setData('anesthesia_type_id', v === 'none' ? '' : v)}
                                >
                                    <SelectTrigger id="edit_anesthesia">
                                        <SelectValue placeholder="Pilih anestesi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Belum ditentukan</SelectItem>
                                        {anesthesiaTypes.map((item) => (
                                            <SelectItem key={item.id} value={String(item.id)}>
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={editForm.errors.anesthesia_type_id} />
                            </div>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button type="button" variant="outline" onClick={() => setEditOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                Simpan Perubahan
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Dialog Konfirmasi Lanjut Status */}
            <Dialog open={advanceOpen} onOpenChange={setAdvanceOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Lanjutkan ke {caseHeader.next_transition?.target_label}?</DialogTitle>
                        <DialogDescription>Status kasus tidak dapat dikembalikan ke tahap sebelumnya setelah dilanjutkan.</DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitAdvance} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="advance_reason">Catatan (Opsional)</Label>
                            <Input
                                id="advance_reason"
                                value={advanceForm.data.reason}
                                onChange={(e) => advanceForm.setData('reason', e.target.value)}
                                placeholder="Catatan singkat perpindahan tahap"
                            />
                        </div>
                        <DialogFooter className="gap-2">
                            <Button type="button" variant="outline" onClick={() => setAdvanceOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={advanceForm.processing}>
                                Ya, Lanjutkan Tahap
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </CaseLayout>
    );
}
