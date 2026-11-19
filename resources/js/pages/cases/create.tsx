import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, FilePlus2, Search, UserPlus, UserRoundCheck } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type SelectedPatient = {
    id: number;
    rm_number: string;
    name: string;
    birth_date: string;
    age_label: string;
    gender_label: string;
    phone: string | null;
};

type GenderOption = {
    value: string;
    label: string;
};

type MasterOption = {
    id: number;
    name: string;
    code?: string;
};

type CreateCaseFormData = {
    patient_mode: 'existing' | 'new';
    patient_id: string;
    rm_number: string;
    patient_name: string;
    birth_date: string;
    gender: string;
    address: string;
    phone: string;
    admitted_at: string;
    origin_ward: string;
    origin_class: string;
    scheduled_at: string;
    medical_diagnosis_id: string;
    medical_diagnosis_other: string;
    surgery_type_id: string;
    surgery_type_other: string;
    surgery_site: string;
    anesthesia_type_id: string;
};

interface CreateCaseProps {
    selectedPatient: SelectedPatient | null;
    genders: GenderOption[];
    medicalDiagnoses: MasterOption[];
    surgeryTypes: MasterOption[];
    anesthesiaTypes: MasterOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kasus Operasi', href: '/kasus' },
    { title: 'Buat Kasus Baru', href: '/kasus/buat' },
];

function localDateTimeInput(offsetHours = 0): string {
    const dt = new Date(Date.now() + offsetHours * 3600 * 1000);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}T${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
}

export default function CasesCreate({ selectedPatient, genders, medicalDiagnoses, surgeryTypes, anesthesiaTypes }: CreateCaseProps) {
    const [activePatient, setActivePatient] = useState<SelectedPatient | null>(selectedPatient);
    const [patientQuery, setPatientQuery] = useState('');
    const [searchResults, setSearchResults] = useState<SelectedPatient[]>([]);
    const [searching, setSearching] = useState(false);

    const form = useForm<CreateCaseFormData>({
        patient_mode: selectedPatient ? 'existing' : 'new',
        patient_id: selectedPatient ? String(selectedPatient.id) : '',
        rm_number: '',
        patient_name: '',
        birth_date: '',
        gender: genders[0]?.value ?? 'L',
        address: '',
        phone: '',
        admitted_at: localDateTimeInput(0),
        origin_ward: '',
        origin_class: '',
        scheduled_at: localDateTimeInput(2),
        medical_diagnosis_id: '',
        medical_diagnosis_other: '',
        surgery_type_id: '',
        surgery_type_other: '',
        surgery_site: '',
        anesthesia_type_id: '',
    });

    useEffect(() => {
        if (form.data.patient_mode !== 'existing') {
            return;
        }

        const timer = window.setTimeout(async () => {
            setSearching(true);
            try {
                const res = await fetch(route('patients.search', { q: patientQuery }), {
                    headers: { Accept: 'application/json' },
                });
                if (res.ok) {
                    const payload = (await res.json()) as { patients: SelectedPatient[] };
                    setSearchResults(payload.patients);
                }
            } finally {
                setSearching(false);
            }
        }, 250);

        return () => window.clearTimeout(timer);
    }, [patientQuery, form.data.patient_mode]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('cases.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Kasus Operasi Baru" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={route('cases.index')}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali ke Daftar Kasus
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <FilePlus2 className="text-primary h-5 w-5" />
                                1. Identitas Pasien
                            </CardTitle>
                            <CardDescription>Pilih pasien yang sudah terdaftar atau daftarkan pasien baru secara manual.</CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-5">
                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="button"
                                    variant={form.data.patient_mode === 'existing' ? 'default' : 'outline'}
                                    onClick={() => form.setData('patient_mode', 'existing')}
                                >
                                    <UserRoundCheck className="h-4 w-4" />
                                    Pilih Pasien Lama
                                </Button>
                                <Button
                                    type="button"
                                    variant={form.data.patient_mode === 'new' ? 'default' : 'outline'}
                                    onClick={() => {
                                        form.setData('patient_mode', 'new');
                                    }}
                                >
                                    <UserPlus className="h-4 w-4" />
                                    Pasien Baru
                                </Button>
                            </div>

                            {form.data.patient_mode === 'existing' ? (
                                <div className="space-y-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="patient_search">Cari Pasien (No. RM atau Nama)</Label>
                                        <div className="relative">
                                            <Search className="text-muted-foreground pointer-events-none absolute top-3.5 left-3 h-4 w-4" />
                                            <Input
                                                id="patient_search"
                                                value={patientQuery}
                                                onChange={(e) => setPatientQuery(e.target.value)}
                                                placeholder="Ketik nomor RM atau nama pasien..."
                                                className="pl-9"
                                            />
                                        </div>
                                        <InputError message={form.errors.patient_id} />
                                    </div>

                                    {activePatient && (
                                        <div className="border-primary/40 bg-primary/5 flex items-center justify-between rounded-lg border p-4">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <CheckCircle2 className="text-primary h-4 w-4" />
                                                    <span className="font-semibold">{activePatient.name}</span>
                                                    <Badge variant="outline" className="font-mono">
                                                        {activePatient.rm_number}
                                                    </Badge>
                                                </div>
                                                <p className="text-muted-foreground mt-1 text-xs">
                                                    {activePatient.birth_date} ({activePatient.age_label}) • {activePatient.gender_label}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <div className="space-y-2">
                                        <p className="text-muted-foreground text-xs">{searching ? 'Mencari pasien...' : 'Hasil pencarian pasien:'}</p>
                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {searchResults.map((p) => {
                                                const selected = activePatient?.id === p.id;
                                                return (
                                                    <button
                                                        key={p.id}
                                                        type="button"
                                                        onClick={() => {
                                                            setActivePatient(p);
                                                            form.setData('patient_id', String(p.id));
                                                        }}
                                                        className={`min-h-11 rounded-lg border p-3 text-left transition-colors ${
                                                            selected ? 'border-primary bg-primary/10' : 'border-border hover:bg-muted/50'
                                                        }`}
                                                    >
                                                        <div className="flex items-center justify-between gap-2">
                                                            <span className="font-medium">{p.name}</span>
                                                            <Badge variant="outline" className="font-mono text-xs">
                                                                {p.rm_number}
                                                            </Badge>
                                                        </div>
                                                        <p className="text-muted-foreground mt-1 text-xs">
                                                            {p.birth_date} ({p.age_label}) • {p.gender_label}
                                                        </p>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="rm_number">Nomor Rekam Medis (RM)</Label>
                                        <Input
                                            id="rm_number"
                                            value={form.data.rm_number}
                                            onChange={(e) => form.setData('rm_number', e.target.value)}
                                            placeholder="contoh: RM-2026-001"
                                            required
                                        />
                                        <InputError message={form.errors.rm_number} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="patient_name">Nama Lengkap Pasien</Label>
                                        <Input
                                            id="patient_name"
                                            value={form.data.patient_name}
                                            onChange={(e) => form.setData('patient_name', e.target.value)}
                                            placeholder="Nama lengkap sesuai KTP/RM"
                                            required
                                        />
                                        <InputError message={form.errors.patient_name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="birth_date">Tanggal Lahir</Label>
                                        <Input
                                            id="birth_date"
                                            type="date"
                                            value={form.data.birth_date}
                                            onChange={(e) => form.setData('birth_date', e.target.value)}
                                            required
                                        />
                                        <InputError message={form.errors.birth_date} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="gender">Jenis Kelamin</Label>
                                        <Select value={form.data.gender} onValueChange={(v) => form.setData('gender', v)}>
                                            <SelectTrigger id="gender">
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

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">No. Telepon (Opsional)</Label>
                                        <Input
                                            id="phone"
                                            value={form.data.phone}
                                            onChange={(e) => form.setData('phone', e.target.value)}
                                            placeholder="08xxxxxxxxxx"
                                        />
                                        <InputError message={form.errors.phone} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="address">Alamat (Opsional)</Label>
                                        <Input
                                            id="address"
                                            value={form.data.address}
                                            onChange={(e) => form.setData('address', e.target.value)}
                                            placeholder="Alamat pasien"
                                        />
                                        <InputError message={form.errors.address} />
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">2. Jadwal & Rencana Awal Operasi</CardTitle>
                            <CardDescription>Status kasus otomatis dimulai pada tahap Pre-Operasi dan nomor kasus dibuat otomatis.</CardDescription>
                        </CardHeader>

                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="admitted_at">Tanggal & Jam Masuk</Label>
                                <Input
                                    id="admitted_at"
                                    type="datetime-local"
                                    value={form.data.admitted_at}
                                    onChange={(e) => form.setData('admitted_at', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.admitted_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="scheduled_at">Jadwal Operasi</Label>
                                <Input
                                    id="scheduled_at"
                                    type="datetime-local"
                                    value={form.data.scheduled_at}
                                    onChange={(e) => form.setData('scheduled_at', e.target.value)}
                                    required
                                />
                                <InputError message={form.errors.scheduled_at} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="origin_ward">Ruang Perawatan Asal</Label>
                                <Input
                                    id="origin_ward"
                                    value={form.data.origin_ward}
                                    onChange={(e) => form.setData('origin_ward', e.target.value)}
                                    placeholder="contoh: Ruang Mawar 1 / IGD"
                                    required
                                />
                                <InputError message={form.errors.origin_ward} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="origin_class">Kelas Perawatan (Opsional)</Label>
                                <Input
                                    id="origin_class"
                                    value={form.data.origin_class}
                                    onChange={(e) => form.setData('origin_class', e.target.value)}
                                    placeholder="contoh: Kelas 1 / VIP"
                                />
                                <InputError message={form.errors.origin_class} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="medical_diagnosis_id">Diagnosis Medis</Label>
                                <Select
                                    value={form.data.medical_diagnosis_id || 'none'}
                                    onValueChange={(value) => form.setData('medical_diagnosis_id', value === 'none' ? '' : value)}
                                >
                                    <SelectTrigger id="medical_diagnosis_id">
                                        <SelectValue placeholder="Pilih diagnosis medis" />
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
                                <InputError message={form.errors.medical_diagnosis_id} />
                            </div>

                            {form.data.medical_diagnosis_id === 'other' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="medical_diagnosis_other">Diagnosis Medis (Lainnya)</Label>
                                    <Input
                                        id="medical_diagnosis_other"
                                        value={form.data.medical_diagnosis_other}
                                        onChange={(e) => form.setData('medical_diagnosis_other', e.target.value)}
                                        placeholder="Ketik diagnosis medis"
                                    />
                                    <InputError message={form.errors.medical_diagnosis_other} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="surgery_type_id">Jenis Operasi</Label>
                                <Select
                                    value={form.data.surgery_type_id || 'none'}
                                    onValueChange={(value) => form.setData('surgery_type_id', value === 'none' ? '' : value)}
                                >
                                    <SelectTrigger id="surgery_type_id">
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
                                <InputError message={form.errors.surgery_type_id} />
                            </div>

                            {form.data.surgery_type_id === 'other' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="surgery_type_other">Jenis Operasi (Lainnya)</Label>
                                    <Input
                                        id="surgery_type_other"
                                        value={form.data.surgery_type_other}
                                        onChange={(e) => form.setData('surgery_type_other', e.target.value)}
                                        placeholder="Ketik jenis operasi"
                                    />
                                    <InputError message={form.errors.surgery_type_other} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="surgery_site">Lokasi Operasi (Opsional)</Label>
                                <Input
                                    id="surgery_site"
                                    value={form.data.surgery_site}
                                    onChange={(e) => form.setData('surgery_site', e.target.value)}
                                    placeholder="contoh: Abdomen kuadran kanan bawah"
                                />
                                <InputError message={form.errors.surgery_site} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="anesthesia_type_id">Jenis Anestesi (Opsional)</Label>
                                <Select
                                    value={form.data.anesthesia_type_id || 'none'}
                                    onValueChange={(value) => form.setData('anesthesia_type_id', value === 'none' ? '' : value)}
                                >
                                    <SelectTrigger id="anesthesia_type_id">
                                        <SelectValue placeholder="Pilih jenis anestesi" />
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
                                <InputError message={form.errors.anesthesia_type_id} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('cases.index')}>Batal</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Simpan & Buat Kasus Operasi
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
