import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FilePlus2, Pencil, Plus, Search, UserRound } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type PatientItem = {
    id: number;
    rm_number: string;
    name: string;
    birth_date: string;
    birth_date_label: string;
    age_label: string;
    gender: string;
    gender_label: string;
    address: string | null;
    phone: string | null;
    identity_number: string | null;
    guardian_name: string | null;
    guardian_relation: string | null;
    guardian_phone: string | null;
    surgery_cases_count: number;
};

type GenderOption = {
    value: string;
    label: string;
};

type PatientFormData = {
    rm_number: string;
    name: string;
    birth_date: string;
    gender: string;
    address: string;
    phone: string;
    identity_number: string;
    guardian_name: string;
    guardian_relation: string;
    guardian_phone: string;
};

interface PatientsIndexProps {
    patients: PaginatedData<PatientItem>;
    filters: { q: string };
    genders: GenderOption[];
    canManage: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Daftar Pasien', href: '/pasien' },
];

function formatPageLabel(label: string): string {
    if (label.includes('Previous') || label.includes('Sebelumnya')) {
        return 'Sebelumnya';
    }
    if (label.includes('Next') || label.includes('Berikutnya')) {
        return 'Berikutnya';
    }

    return label;
}

export default function PatientsIndex({ patients, filters, genders, canManage }: PatientsIndexProps) {
    const [search, setSearch] = useState(filters.q);
    const [formOpen, setFormOpen] = useState(false);
    const [editingPatient, setEditingPatient] = useState<PatientItem | null>(null);

    const form = useForm<PatientFormData>({
        rm_number: '',
        name: '',
        birth_date: '',
        gender: genders[0]?.value ?? 'L',
        address: '',
        phone: '',
        identity_number: '',
        guardian_name: '',
        guardian_relation: '',
        guardian_phone: '',
    });

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('patients.index'), { q: search || undefined }, { preserveState: true, replace: true });
    };

    const openCreateModal = () => {
        setEditingPatient(null);
        form.clearErrors();
        form.setData({
            rm_number: '',
            name: '',
            birth_date: '',
            gender: genders[0]?.value ?? 'L',
            address: '',
            phone: '',
            identity_number: '',
            guardian_name: '',
            guardian_relation: '',
            guardian_phone: '',
        });
        setFormOpen(true);
    };

    const openEditModal = (patient: PatientItem) => {
        setEditingPatient(patient);
        form.clearErrors();
        form.setData({
            rm_number: patient.rm_number,
            name: patient.name,
            birth_date: patient.birth_date,
            gender: patient.gender,
            address: patient.address ?? '',
            phone: patient.phone ?? '',
            identity_number: patient.identity_number ?? '',
            guardian_name: patient.guardian_name ?? '',
            guardian_relation: patient.guardian_relation ?? '',
            guardian_phone: patient.guardian_phone ?? '',
        });
        setFormOpen(true);
    };

    const submitForm: FormEventHandler = (e) => {
        e.preventDefault();

        if (editingPatient) {
            form.put(route('patients.update', editingPatient.id), {
                preserveScroll: true,
                onSuccess: () => setFormOpen(false),
            });
            return;
        }

        form.post(route('patients.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setFormOpen(false);
                form.reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daftar Pasien" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-xl">
                                <UserRound className="text-primary h-5 w-5" />
                                Daftar Pasien
                            </CardTitle>
                            <CardDescription>
                                Pencarian data pasien kamar operasi berdasarkan nomor rekam medis (RM) atau nama lengkap.
                            </CardDescription>
                        </div>
                        {canManage && (
                            <Button type="button" onClick={openCreateModal}>
                                <Plus className="h-4 w-4" />
                                Tambah Pasien
                            </Button>
                        )}
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <form onSubmit={handleSearch} className="flex flex-col gap-3 sm:flex-row">
                            <div className="relative flex-1">
                                <Search className="text-muted-foreground pointer-events-none absolute top-3.5 left-3 h-4 w-4" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nomor RM atau nama pasien..."
                                    className="pl-9"
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" variant="secondary">
                                    Cari
                                </Button>
                                {filters.q && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setSearch('');
                                            router.get(route('patients.index'), {}, { preserveState: true, replace: true });
                                        }}
                                    >
                                        Reset
                                    </Button>
                                )}
                            </div>
                        </form>

                        <div className="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. RM</TableHead>
                                        <TableHead>Nama Pasien</TableHead>
                                        <TableHead>Tgl Lahir / Umur</TableHead>
                                        <TableHead>JK</TableHead>
                                        <TableHead>Kontak & Alamat</TableHead>
                                        <TableHead>Kasus</TableHead>
                                        {canManage && <TableHead className="text-right">Aksi</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {patients.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={canManage ? 7 : 6} className="text-muted-foreground py-10 text-center">
                                                Belum ada data pasien yang sesuai pencarian.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        patients.data.map((patient) => (
                                            <TableRow key={patient.id}>
                                                <TableCell className="font-mono font-medium">{patient.rm_number}</TableCell>
                                                <TableCell className="font-medium">{patient.name}</TableCell>
                                                <TableCell className="text-xs">
                                                    <div>{patient.birth_date_label}</div>
                                                    <div className="text-muted-foreground">{patient.age_label}</div>
                                                </TableCell>
                                                <TableCell>{patient.gender_label}</TableCell>
                                                <TableCell className="max-w-xs text-xs">
                                                    <div>{patient.phone ?? '-'}</div>
                                                    <div className="text-muted-foreground truncate">{patient.address ?? '-'}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">{patient.surgery_cases_count} kasus</Badge>
                                                </TableCell>
                                                {canManage && (
                                                    <TableCell>
                                                        <div className="flex justify-end gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                className="min-h-10"
                                                                onClick={() => openEditModal(patient)}
                                                            >
                                                                <Pencil className="h-3.5 w-3.5" />
                                                                Edit
                                                            </Button>
                                                            <Button type="button" size="sm" className="min-h-10" asChild>
                                                                <Link href={route('cases.create', { patient_id: patient.id })}>
                                                                    <FilePlus2 className="h-3.5 w-3.5" />
                                                                    Buat Kasus
                                                                </Link>
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {patients.last_page > 1 && (
                            <div className="flex flex-col items-center justify-between gap-3 pt-2 sm:flex-row">
                                <p className="text-muted-foreground text-sm">
                                    Menampilkan {patients.from ?? 0}–{patients.to ?? 0} dari {patients.total} pasien
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                    {patients.links.map((link, index) =>
                                        link.url ? (
                                            <Button key={index} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                                                <Link href={link.url} preserveScroll>
                                                    {formatPageLabel(link.label)}
                                                </Link>
                                            </Button>
                                        ) : (
                                            <Button key={index} variant="outline" size="sm" disabled>
                                                {formatPageLabel(link.label)}
                                            </Button>
                                        ),
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {canManage && (
                <Dialog open={formOpen} onOpenChange={setFormOpen}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{editingPatient ? 'Edit Data Pasien' : 'Tambah Data Pasien'}</DialogTitle>
                            <DialogDescription>Masukkan identitas pasien secara manual sesuai rekam medis rumah sakit.</DialogDescription>
                        </DialogHeader>

                        <form onSubmit={submitForm} className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="patient_rm">Nomor Rekam Medis (RM)</Label>
                                    <Input
                                        id="patient_rm"
                                        value={form.data.rm_number}
                                        onChange={(e) => form.setData('rm_number', e.target.value)}
                                        placeholder="contoh: RM-001234"
                                        required
                                    />
                                    <InputError message={form.errors.rm_number} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="patient_gender">Jenis Kelamin</Label>
                                    <Select value={form.data.gender} onValueChange={(value) => form.setData('gender', value)}>
                                        <SelectTrigger id="patient_gender">
                                            <SelectValue placeholder="Pilih jenis kelamin" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {genders.map((g) => (
                                                <SelectItem key={g.value} value={g.value}>
                                                    {g.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors.gender} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="patient_name">Nama Lengkap Pasien</Label>
                                <Input
                                    id="patient_name"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    placeholder="Nama lengkap pasien"
                                    required
                                />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="patient_birth_date">Tanggal Lahir</Label>
                                    <Input
                                        id="patient_birth_date"
                                        type="date"
                                        value={form.data.birth_date}
                                        onChange={(e) => form.setData('birth_date', e.target.value)}
                                        required
                                    />
                                    <InputError message={form.errors.birth_date} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="patient_phone">No. Telepon (Opsional)</Label>
                                    <Input
                                        id="patient_phone"
                                        value={form.data.phone}
                                        onChange={(e) => form.setData('phone', e.target.value)}
                                        placeholder="08xxxxxxxxxx"
                                    />
                                    <InputError message={form.errors.phone} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="patient_identity_number">No. Identitas / NIK (Opsional)</Label>
                                    <Input
                                        id="patient_identity_number"
                                        value={form.data.identity_number}
                                        onChange={(e) => form.setData('identity_number', e.target.value)}
                                        placeholder="16 digit NIK / KTP"
                                    />
                                    <InputError message={form.errors.identity_number} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="patient_guardian_name">Nama Keluarga / Penanggung Jawab (Opsional)</Label>
                                    <Input
                                        id="patient_guardian_name"
                                        value={form.data.guardian_name}
                                        onChange={(e) => form.setData('guardian_name', e.target.value)}
                                        placeholder="Nama keluarga pasien"
                                    />
                                    <InputError message={form.errors.guardian_name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="patient_guardian_relation">Hubungan dengan Pasien (Opsional)</Label>
                                    <Input
                                        id="patient_guardian_relation"
                                        value={form.data.guardian_relation}
                                        onChange={(e) => form.setData('guardian_relation', e.target.value)}
                                        placeholder="contoh: Istri, Suami, Orang Tua, Anak"
                                    />
                                    <InputError message={form.errors.guardian_relation} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="patient_guardian_phone">No. Telepon Keluarga (Opsional)</Label>
                                    <Input
                                        id="patient_guardian_phone"
                                        value={form.data.guardian_phone}
                                        onChange={(e) => form.setData('guardian_phone', e.target.value)}
                                        placeholder="08xxxxxxxxxx"
                                    />
                                    <InputError message={form.errors.guardian_phone} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="patient_address">Alamat (Opsional)</Label>
                                <Input
                                    id="patient_address"
                                    value={form.data.address}
                                    onChange={(e) => form.setData('address', e.target.value)}
                                    placeholder="Alamat lengkap pasien"
                                />
                                <InputError message={form.errors.address} />
                            </div>

                            <DialogFooter className="gap-2 pt-2">
                                <Button type="button" variant="outline" onClick={() => setFormOpen(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" disabled={form.processing}>
                                    {editingPatient ? 'Simpan Perubahan' : 'Simpan Pasien'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}
        </AppLayout>
    );
}
