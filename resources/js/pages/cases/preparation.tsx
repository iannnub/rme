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
import { AlertTriangle, ArrowRight, CheckCircle2, ClipboardCheck, Plus, Save, Trash2, Users, Wrench } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type RoomOption = { id: number; code: string; name: string };
type DoctorOption = { id: number; name: string; sip: string | null };
type NurseOption = { id: number; name: string; nurse_type: string | null; nurse_type_label: string };
type InstrumentOption = {
    id: number;
    name: string;
    category: string | null;
    surgery_profile?: string | null;
    description?: string | null;
};

type RoomConflictInfo = {
    case_id: number;
    case_number: string;
    patient_name: string;
    room_code: string;
};

type InstrumentCheckItem = {
    id?: number;
    instrument_id: number;
    instrument_name: string;
    instrument_category: string | null;
    instrument_description?: string | null;
    is_available: boolean;
    is_sterile: boolean;
    is_functional: boolean;
    is_rusty: boolean;
    note: string;
    checked_by_name?: string;
    checked_at?: string;
};

interface PreparationPageProps {
    caseHeader: CaseHeaderData;
    preparation: {
        room_id: number | null;
        surgeon_id: number | null;
        anesthesiologist_id: number | null;
        circulating_nurse_id: number | null;
        scrub_nurse_id: number | null;
        anesthesia_nurse_id: number | null;
        team_assigned_at: string | null;
    };
    roomConflict: RoomConflictInfo | null;
    activeIntraOpRooms: Record<string, RoomConflictInfo>;
    instrumentChecks: InstrumentCheckItem[];
    progress: {
        room_and_team: boolean;
        instrument_checks: boolean;
        sign_in: boolean;
    };
    options: {
        rooms: RoomOption[];
        surgeons: DoctorOption[];
        anesthesiologists: DoctorOption[];
        nurses: NurseOption[];
        instruments: InstrumentOption[];
    };
    permissions: {
        canEdit: boolean;
        canAdvance: boolean;
    };
}

export default function PreparationPage({
    caseHeader,
    preparation,
    roomConflict,
    activeIntraOpRooms,
    instrumentChecks,
    progress,
    options,
    permissions,
}: PreparationPageProps) {
    const teamForm = useForm({
        room_id: preparation.room_id ? String(preparation.room_id) : '',
        surgeon_id: preparation.surgeon_id ? String(preparation.surgeon_id) : '',
        anesthesiologist_id: preparation.anesthesiologist_id ? String(preparation.anesthesiologist_id) : '',
        circulating_nurse_id: preparation.circulating_nurse_id ? String(preparation.circulating_nurse_id) : '',
        scrub_nurse_id: preparation.scrub_nurse_id ? String(preparation.scrub_nurse_id) : '',
        anesthesia_nurse_id: preparation.anesthesia_nurse_id ? String(preparation.anesthesia_nurse_id) : '',
    });

    const instrumentsForm = useForm<{ items: InstrumentCheckItem[] }>({
        items: instrumentChecks.map((item) => ({ ...item })),
    });

    const [pickerOpen, setPickerOpen] = useState(false);
    const [selectedInstrumentIds, setSelectedInstrumentIds] = useState<number[]>([]);
    const [instrumentSearch, setInstrumentSearch] = useState('');
    const [categoryFilter, setCategoryFilter] = useState<string>('all');
    const [advancing, setAdvancing] = useState(false);

    const selectedRoomConflict = teamForm.data.room_id ? (activeIntraOpRooms[teamForm.data.room_id] ?? null) : roomConflict;

    const handleSaveTeam = (e: FormEvent) => {
        e.preventDefault();
        teamForm.put(route('cases.preparation.team', caseHeader.id), {
            preserveScroll: true,
        });
    };

    const handleSaveInstruments = (e: FormEvent) => {
        e.preventDefault();
        instrumentsForm.put(route('cases.preparation.instruments', caseHeader.id), {
            preserveScroll: true,
        });
    };

    const existingInstrumentIds = new Set(instrumentsForm.data.items.map((item) => item.instrument_id));
    const instrumentCategories = Array.from(new Set(options.instruments.map((inst) => inst.category).filter((cat): cat is string => Boolean(cat))));

    const openPicker = () => {
        setSelectedInstrumentIds([]);
        setInstrumentSearch('');
        setCategoryFilter('all');
        setPickerOpen(true);
    };

    const loadStandardInstruments = () => {
        const missing = options.instruments
            .filter((inst) => !existingInstrumentIds.has(inst.id))
            .map(
                (inst): InstrumentCheckItem => ({
                    instrument_id: inst.id,
                    instrument_name: inst.name,
                    instrument_category: inst.category,
                    instrument_description: inst.description ?? null,
                    is_available: true,
                    is_sterile: true,
                    is_functional: true,
                    is_rusty: false,
                    note: '',
                }),
            );

        if (missing.length > 0) {
            instrumentsForm.setData('items', [...instrumentsForm.data.items, ...missing]);
        }
    };

    const togglePickerSelection = (id: number) => {
        setSelectedInstrumentIds((prev) => (prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]));
    };

    const confirmAddInstruments = () => {
        const toAdd = options.instruments
            .filter((inst) => selectedInstrumentIds.includes(inst.id) && !existingInstrumentIds.has(inst.id))
            .map(
                (inst): InstrumentCheckItem => ({
                    instrument_id: inst.id,
                    instrument_name: inst.name,
                    instrument_category: inst.category,
                    instrument_description: inst.description ?? null,
                    is_available: true,
                    is_sterile: true,
                    is_functional: true,
                    is_rusty: false,
                    note: '',
                }),
            );

        if (toAdd.length > 0) {
            instrumentsForm.setData('items', [...instrumentsForm.data.items, ...toAdd]);
        }
        setPickerOpen(false);
    };

    const updateInstrumentRow = (index: number, patch: Partial<InstrumentCheckItem>) => {
        const updated = [...instrumentsForm.data.items];
        updated[index] = { ...updated[index], ...patch };
        instrumentsForm.setData('items', updated);
    };

    const removeInstrumentRow = (index: number) => {
        const updated = instrumentsForm.data.items.filter((_, idx) => idx !== index);
        instrumentsForm.setData('items', updated);
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

    const filteredMasterInstruments = options.instruments.filter((inst) => {
        if (categoryFilter !== 'all' && inst.category !== categoryFilter) {
            return false;
        }
        const q = instrumentSearch.trim().toLowerCase();
        if (!q) {
            return true;
        }
        return (
            inst.name.toLowerCase().includes(q) ||
            (inst.category ?? '').toLowerCase().includes(q) ||
            (inst.description ?? '').toLowerCase().includes(q)
        );
    });

    const instrumentErrors = instrumentsForm.errors as Record<string, string | undefined>;
    const hasInstrumentErrors = Object.keys(instrumentErrors).length > 0;

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="persiapan">
            <Head title={`Persiapan - ${caseHeader.case_number}`} />

            <div className="mx-auto max-w-6xl space-y-6">
                {/* Indikator Progres Persiapan */}
                <Card>
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div className="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-3">
                                <div
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg border p-3',
                                        progress.room_and_team
                                            ? 'border-emerald-200 bg-emerald-50/70 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'
                                            : 'border-border bg-muted/30 text-muted-foreground',
                                    )}
                                >
                                    <CheckCircle2 className={cn('h-5 w-5 shrink-0', progress.room_and_team ? 'text-emerald-600' : 'opacity-40')} />
                                    <div className="text-sm">
                                        <div className="font-semibold">1. Ruang & Tim</div>
                                        <div className="text-xs opacity-80">{progress.room_and_team ? 'Lengkap ditetapkan' : 'Belum lengkap'}</div>
                                    </div>
                                </div>

                                <div
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg border p-3',
                                        progress.instrument_checks
                                            ? 'border-emerald-200 bg-emerald-50/70 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'
                                            : 'border-border bg-muted/30 text-muted-foreground',
                                    )}
                                >
                                    <CheckCircle2
                                        className={cn('h-5 w-5 shrink-0', progress.instrument_checks ? 'text-emerald-600' : 'opacity-40')}
                                    />
                                    <div className="text-sm">
                                        <div className="font-semibold">2. Cek Alat</div>
                                        <div className="text-xs opacity-80">
                                            {progress.instrument_checks ? `${instrumentChecks.length} alat terverifikasi` : 'Belum selesai'}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg border p-3',
                                        progress.sign_in
                                            ? 'border-emerald-200 bg-emerald-50/70 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'
                                            : 'border-border bg-muted/30 text-muted-foreground',
                                    )}
                                >
                                    <CheckCircle2 className={cn('h-5 w-5 shrink-0', progress.sign_in ? 'text-emerald-600' : 'opacity-40')} />
                                    <div className="text-sm">
                                        <div className="font-semibold">3. SSC Sign In</div>
                                        <div className="text-xs opacity-80">{progress.sign_in ? 'Selesai' : 'Belum selesai'}</div>
                                    </div>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <Button asChild variant="outline" className="min-h-11">
                                    <Link href={route('cases.ssc.sign-in', caseHeader.id)}>
                                        <ClipboardCheck className="mr-2 h-4 w-4" />
                                        Buka SSC Sign In
                                    </Link>
                                </Button>

                                {permissions.canAdvance && caseHeader.status === 'pre_op' && caseHeader.next_transition && (
                                    <Button
                                        type="button"
                                        className="min-h-11"
                                        disabled={!caseHeader.next_transition.can_advance || advancing}
                                        onClick={handleAdvanceToSiap}
                                    >
                                        Lanjut ke {caseHeader.next_transition.target_label}
                                        <ArrowRight className="ml-2 h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </div>

                        {caseHeader.status === 'pre_op' && caseHeader.next_transition && !caseHeader.next_transition.can_advance && (
                            <div className="bg-muted/40 mt-4 rounded-md border p-3 text-xs">
                                <p className="text-foreground mb-1.5 font-semibold">Syarat menuju status Siap Operasi:</p>
                                <div className="grid grid-cols-1 gap-1 sm:grid-cols-2">
                                    {caseHeader.next_transition.checklist.map((item) => (
                                        <div key={item.label} className="flex items-center gap-2">
                                            <span className={cn('inline-block h-2 w-2 rounded-full', item.met ? 'bg-emerald-600' : 'bg-amber-500')} />
                                            <span className={item.met ? 'text-muted-foreground line-through' : 'text-foreground'}>{item.label}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Bagian 1: Penetapan Ruang Operasi dan Tim */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Users className="text-primary h-5 w-5" />
                                    A. Penetapan Ruang Operasi & Tim Operasi
                                </CardTitle>
                                <CardDescription>
                                    Tetapkan kamar operasi dan 5 petugas inti (dokter operator, dokter anestesi, perawat sirkuler, instrumen, dan
                                    anestesi).
                                </CardDescription>
                            </div>
                            {preparation.team_assigned_at && (
                                <Badge variant="secondary" className="text-xs">
                                    Ditetapkan: {preparation.team_assigned_at} WIB
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {selectedRoomConflict && (
                            <Alert className="mb-4 border-amber-400 bg-amber-50 text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
                                <AlertTriangle className="h-4 w-4 text-amber-600" />
                                <AlertTitle>Peringatan Bentrok Ruang Operasi</AlertTitle>
                                <AlertDescription>
                                    Ruang <strong>{selectedRoomConflict.room_code}</strong> saat ini sedang digunakan oleh kasus{' '}
                                    <strong>{selectedRoomConflict.case_number}</strong> ({selectedRoomConflict.patient_name}) yang berstatus{' '}
                                    <strong>Intra-Operasi</strong>. Pastikan jadwal atau pilih ruang lain bila diperlukan.
                                </AlertDescription>
                            </Alert>
                        )}

                        <form onSubmit={handleSaveTeam} className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="room_id">Kamar Operasi *</Label>
                                    <Select
                                        value={teamForm.data.room_id}
                                        onValueChange={(val) => teamForm.setData('room_id', val)}
                                        disabled={!permissions.canEdit}
                                    >
                                        <SelectTrigger id="room_id" className="min-h-11">
                                            <SelectValue placeholder="Pilih kamar operasi" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.rooms.map((room) => {
                                                const busy = activeIntraOpRooms[String(room.id)];
                                                return (
                                                    <SelectItem key={room.id} value={String(room.id)}>
                                                        {room.code} - {room.name}
                                                        {busy ? ' (Sedang Intra-Op)' : ''}
                                                    </SelectItem>
                                                );
                                            })}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={teamForm.errors.room_id} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="surgeon_id">Dokter Operator *</Label>
                                    <Select
                                        value={teamForm.data.surgeon_id}
                                        onValueChange={(val) => teamForm.setData('surgeon_id', val)}
                                        disabled={!permissions.canEdit}
                                    >
                                        <SelectTrigger id="surgeon_id" className="min-h-11">
                                            <SelectValue placeholder="Pilih dokter operator" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.surgeons.map((doc) => (
                                                <SelectItem key={doc.id} value={String(doc.id)}>
                                                    {doc.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={teamForm.errors.surgeon_id} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="anesthesiologist_id">Dokter Anestesi *</Label>
                                    <Select
                                        value={teamForm.data.anesthesiologist_id}
                                        onValueChange={(val) => teamForm.setData('anesthesiologist_id', val)}
                                        disabled={!permissions.canEdit}
                                    >
                                        <SelectTrigger id="anesthesiologist_id" className="min-h-11">
                                            <SelectValue placeholder="Pilih dokter anestesi" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.anesthesiologists.map((doc) => (
                                                <SelectItem key={doc.id} value={String(doc.id)}>
                                                    {doc.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={teamForm.errors.anesthesiologist_id} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="circulating_nurse_id">Perawat Sirkuler *</Label>
                                    <Select
                                        value={teamForm.data.circulating_nurse_id}
                                        onValueChange={(val) => teamForm.setData('circulating_nurse_id', val)}
                                        disabled={!permissions.canEdit}
                                    >
                                        <SelectTrigger id="circulating_nurse_id" className="min-h-11">
                                            <SelectValue placeholder="Pilih perawat sirkuler" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.nurses.map((nurse) => (
                                                <SelectItem key={nurse.id} value={String(nurse.id)}>
                                                    {nurse.name} ({nurse.nurse_type_label})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={teamForm.errors.circulating_nurse_id} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="scrub_nurse_id">Perawat Instrumen *</Label>
                                    <Select
                                        value={teamForm.data.scrub_nurse_id}
                                        onValueChange={(val) => teamForm.setData('scrub_nurse_id', val)}
                                        disabled={!permissions.canEdit}
                                    >
                                        <SelectTrigger id="scrub_nurse_id" className="min-h-11">
                                            <SelectValue placeholder="Pilih perawat instrumen" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.nurses.map((nurse) => (
                                                <SelectItem key={nurse.id} value={String(nurse.id)}>
                                                    {nurse.name} ({nurse.nurse_type_label})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={teamForm.errors.scrub_nurse_id} />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="anesthesia_nurse_id">Perawat Anestesi *</Label>
                                    <Select
                                        value={teamForm.data.anesthesia_nurse_id}
                                        onValueChange={(val) => teamForm.setData('anesthesia_nurse_id', val)}
                                        disabled={!permissions.canEdit}
                                    >
                                        <SelectTrigger id="anesthesia_nurse_id" className="min-h-11">
                                            <SelectValue placeholder="Pilih perawat anestesi" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {options.nurses.map((nurse) => (
                                                <SelectItem key={nurse.id} value={String(nurse.id)}>
                                                    {nurse.name} ({nurse.nurse_type_label})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={teamForm.errors.anesthesia_nurse_id} />
                                </div>
                            </div>

                            {permissions.canEdit && (
                                <div className="flex justify-end pt-2">
                                    <Button type="submit" className="min-h-11" disabled={teamForm.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {teamForm.processing ? 'Menyimpan...' : 'Simpan Ruang & Tim'}
                                    </Button>
                                </div>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Bagian 2: Cek Ketersediaan dan Kesterilan Alat */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <Wrench className="text-primary h-5 w-5" />
                                        B. Cek Ketersediaan dan Kesterilan Alat
                                    </CardTitle>
                                    {caseHeader.surgery_profile_label && (
                                        <Badge variant="outline" className="rounded-lg text-xs">
                                            Profil: {caseHeader.surgery_profile_label} ({options.instruments.length} standar)
                                        </Badge>
                                    )}
                                </div>
                                <CardDescription>
                                    Daftar alat disaring sesuai profil operasi. Jika ada alat bermasalah (tidak tersedia/steril/berfungsi atau
                                    berkarat), catatan tindak lanjut wajib diisi.
                                </CardDescription>
                            </div>
                            {permissions.canEdit && (
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        className="min-h-11"
                                        onClick={loadStandardInstruments}
                                        disabled={options.instruments.every((i) => existingInstrumentIds.has(i.id))}
                                    >
                                        <ClipboardCheck className="mr-2 h-4 w-4" />
                                        Muat Semua Alat Standar
                                    </Button>
                                    <Button type="button" variant="outline" className="min-h-11" onClick={openPicker}>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Tambah Alat
                                    </Button>
                                </div>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSaveInstruments} className="space-y-4">
                            {hasInstrumentErrors && (
                                <Alert variant="destructive">
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertTitle>Periksa kembali daftar cek alat</AlertTitle>
                                    <AlertDescription>
                                        {instrumentErrors.items ?? 'Pastikan setiap alat bermasalah dilengkapi catatan tindak lanjut.'}
                                    </AlertDescription>
                                </Alert>
                            )}

                            {instrumentsForm.data.items.length === 0 ? (
                                <div className="text-muted-foreground rounded-lg border border-dashed p-8 text-center text-sm">
                                    Belum ada alat operasi yang diperiksa untuk kasus ini. Klik tombol <strong>Tambah Alat</strong> untuk memilih alat
                                    dari master data.
                                </div>
                            ) : (
                                <div className="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="min-w-[200px]">Nama Alat</TableHead>
                                                <TableHead className="w-[90px] text-center">Tersedia</TableHead>
                                                <TableHead className="w-[90px] text-center">Steril</TableHead>
                                                <TableHead className="w-[90px] text-center">Berfungsi</TableHead>
                                                <TableHead className="w-[90px] text-center">Berkarat</TableHead>
                                                <TableHead className="min-w-[260px]">Catatan Tindak Lanjut</TableHead>
                                                {permissions.canEdit && <TableHead className="w-[60px] text-center">Aksi</TableHead>}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {instrumentsForm.data.items.map((item, idx) => {
                                                const hasIssue = !item.is_available || !item.is_sterile || !item.is_functional || item.is_rusty;
                                                const rowNoteError = instrumentErrors[`items.${idx}.note`];

                                                return (
                                                    <TableRow
                                                        key={item.instrument_id}
                                                        className={cn(
                                                            hasIssue &&
                                                                'bg-amber-50/80 hover:bg-amber-50 dark:bg-amber-950/30 dark:hover:bg-amber-950/40',
                                                        )}
                                                    >
                                                        <TableCell>
                                                            <div className="text-foreground font-medium">{item.instrument_name}</div>
                                                            <div className="text-muted-foreground flex flex-wrap items-center gap-1.5 text-xs">
                                                                {item.instrument_category && <span>{item.instrument_category}</span>}
                                                                {hasIssue && (
                                                                    <Badge variant="destructive" className="text-[10px]">
                                                                        Bermasalah - Wajib Catatan
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            {item.instrument_description && (
                                                                <div className="text-muted-foreground text-[11px]">{item.instrument_description}</div>
                                                            )}
                                                            {item.checked_at && (
                                                                <div className="text-muted-foreground mt-0.5 text-[11px]">
                                                                    Dicek: {item.checked_by_name} ({item.checked_at})
                                                                </div>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            <Checkbox
                                                                checked={item.is_available}
                                                                disabled={!permissions.canEdit}
                                                                onCheckedChange={(checked) =>
                                                                    updateInstrumentRow(idx, { is_available: checked === true })
                                                                }
                                                                aria-label={`${item.instrument_name} tersedia`}
                                                            />
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            <Checkbox
                                                                checked={item.is_sterile}
                                                                disabled={!permissions.canEdit}
                                                                onCheckedChange={(checked) =>
                                                                    updateInstrumentRow(idx, { is_sterile: checked === true })
                                                                }
                                                                aria-label={`${item.instrument_name} steril`}
                                                            />
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            <Checkbox
                                                                checked={item.is_functional}
                                                                disabled={!permissions.canEdit}
                                                                onCheckedChange={(checked) =>
                                                                    updateInstrumentRow(idx, { is_functional: checked === true })
                                                                }
                                                                aria-label={`${item.instrument_name} berfungsi`}
                                                            />
                                                        </TableCell>
                                                        <TableCell className="text-center">
                                                            <Checkbox
                                                                checked={item.is_rusty}
                                                                disabled={!permissions.canEdit}
                                                                onCheckedChange={(checked) =>
                                                                    updateInstrumentRow(idx, { is_rusty: checked === true })
                                                                }
                                                                aria-label={`${item.instrument_name} berkarat`}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <Input
                                                                value={item.note}
                                                                disabled={!permissions.canEdit}
                                                                onChange={(e) => updateInstrumentRow(idx, { note: e.target.value })}
                                                                placeholder={
                                                                    hasIssue ? 'Wajib isi catatan tindak lanjut...' : 'Catatan tambahan (opsional)'
                                                                }
                                                                className={cn(
                                                                    'min-h-10',
                                                                    hasIssue &&
                                                                        !item.note.trim() &&
                                                                        'border-destructive focus-visible:ring-destructive',
                                                                )}
                                                            />
                                                            <InputError message={rowNoteError} className="mt-1" />
                                                        </TableCell>
                                                        {permissions.canEdit && (
                                                            <TableCell className="text-center">
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => removeInstrumentRow(idx)}
                                                                    aria-label={`Hapus ${item.instrument_name}`}
                                                                >
                                                                    <Trash2 className="text-destructive h-4 w-4" />
                                                                </Button>
                                                            </TableCell>
                                                        )}
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}

                            {permissions.canEdit && instrumentsForm.data.items.length > 0 && (
                                <div className="flex justify-end pt-2">
                                    <Button type="submit" className="min-h-11" disabled={instrumentsForm.processing}>
                                        <Save className="mr-2 h-4 w-4" />
                                        {instrumentsForm.processing ? 'Menyimpan...' : 'Simpan Cek Alat'}
                                    </Button>
                                </div>
                            )}
                        </form>
                    </CardContent>
                </Card>
            </div>

            {/* Dialog Multi-Pilih Alat dari Master */}
            <Dialog open={pickerOpen} onOpenChange={setPickerOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Pilih Alat dari Master Data</DialogTitle>
                        <DialogDescription>Pilih satu atau beberapa alat operasi yang akan diperiksa untuk kasus ini.</DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3">
                        <Input
                            placeholder="Cari nama, kategori, atau fungsi alat..."
                            value={instrumentSearch}
                            onChange={(e) => setInstrumentSearch(e.target.value)}
                            className="min-h-11"
                        />

                        {instrumentCategories.length > 0 && (
                            <div className="flex flex-wrap gap-1.5">
                                <button
                                    type="button"
                                    onClick={() => setCategoryFilter('all')}
                                    className={cn(
                                        'min-h-11 rounded-lg border px-3 py-1 text-xs font-medium transition-colors',
                                        categoryFilter === 'all'
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-border bg-background text-muted-foreground hover:bg-muted',
                                    )}
                                >
                                    Semua ({options.instruments.length})
                                </button>
                                {instrumentCategories.map((cat) => (
                                    <button
                                        key={cat}
                                        type="button"
                                        onClick={() => setCategoryFilter(cat)}
                                        className={cn(
                                            'min-h-11 rounded-lg border px-3 py-1 text-xs font-medium transition-colors',
                                            categoryFilter === cat
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-border bg-background text-muted-foreground hover:bg-muted',
                                        )}
                                    >
                                        {cat}
                                    </button>
                                ))}
                            </div>
                        )}

                        <div className="max-h-72 space-y-1.5 overflow-y-auto rounded-md border p-2">
                            {filteredMasterInstruments.length === 0 ? (
                                <p className="text-muted-foreground py-6 text-center text-sm">Tidak ada alat yang cocok.</p>
                            ) : (
                                filteredMasterInstruments.map((inst) => {
                                    const alreadyAdded = existingInstrumentIds.has(inst.id);
                                    const checked = alreadyAdded || selectedInstrumentIds.includes(inst.id);

                                    return (
                                        <label
                                            key={inst.id}
                                            className={cn(
                                                'flex cursor-pointer items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors',
                                                alreadyAdded ? 'bg-muted/50 opacity-60' : 'hover:bg-muted/60',
                                            )}
                                        >
                                            <Checkbox
                                                checked={checked}
                                                disabled={alreadyAdded}
                                                onCheckedChange={() => togglePickerSelection(inst.id)}
                                            />
                                            <div className="flex-1">
                                                <div className="font-medium">{inst.name}</div>
                                                {inst.category && <div className="text-muted-foreground text-xs">{inst.category}</div>}
                                                {inst.description && <div className="text-muted-foreground text-[11px]">{inst.description}</div>}
                                            </div>
                                            {alreadyAdded && (
                                                <Badge variant="outline" className="text-xs">
                                                    Sudah di daftar
                                                </Badge>
                                            )}
                                        </label>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setPickerOpen(false)}>
                            Batal
                        </Button>
                        <Button type="button" onClick={confirmAddInstruments} disabled={selectedInstrumentIds.length === 0}>
                            Tambahkan ({selectedInstrumentIds.length})
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </CaseLayout>
    );
}
