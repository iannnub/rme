import { CaseStatusBadge } from '@/components/cases/case-status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ClipboardList, Eye, Plus, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type CaseListItem = {
    id: number;
    case_number: string;
    status: string;
    status_label: string;
    scheduled_at: string;
    patient_name: string;
    patient_rm: string;
    diagnosis: string;
    surgery_type: string;
    room_code: string | null;
    surgeon_name: string | null;
};

type OptionItem = {
    value: string;
    label: string;
};

type RoomOption = {
    id: number;
    code: string;
    name: string;
};

type SurgeonOption = {
    id: number;
    name: string;
};

interface CasesIndexProps {
    cases: PaginatedData<CaseListItem>;
    filters: {
        q: string;
        status: string;
        date: string;
        room_id: string;
        surgeon_id: string;
    };
    statuses: OptionItem[];
    rooms: RoomOption[];
    surgeons: SurgeonOption[];
    canCreate: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Kasus Operasi', href: '/kasus' },
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

export default function CasesIndex({ cases, filters, statuses, rooms, surgeons, canCreate }: CasesIndexProps) {
    const [search, setSearch] = useState(filters.q);
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [dateFilter, setDateFilter] = useState(filters.date);
    const [roomFilter, setRoomFilter] = useState(filters.room_id || 'all');
    const [surgeonFilter, setSurgeonFilter] = useState(filters.surgeon_id || 'all');

    const applyFilters = (next: { q: string; status: string; date: string; room_id: string; surgeon_id: string }) => {
        router.get(
            route('cases.index'),
            {
                q: next.q || undefined,
                status: next.status !== 'all' ? next.status : undefined,
                date: next.date || undefined,
                room_id: next.room_id !== 'all' ? next.room_id : undefined,
                surgeon_id: next.surgeon_id !== 'all' ? next.surgeon_id : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters({
            q: search,
            status: statusFilter,
            date: dateFilter,
            room_id: roomFilter,
            surgeon_id: surgeonFilter,
        });
    };

    const hasActiveFilters = Boolean(filters.q || filters.status || filters.date || filters.room_id || filters.surgeon_id);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daftar Kasus Operasi" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-xl">
                                <ClipboardList className="text-primary h-5 w-5" />
                                Daftar Kasus Operasi
                            </CardTitle>
                            <CardDescription>Pantau seluruh kasus pembedahan kamar operasi beserta status tahap perioperatifnya.</CardDescription>
                        </div>
                        {canCreate && (
                            <Button asChild>
                                <Link href={route('cases.create')}>
                                    <Plus className="h-4 w-4" />
                                    Buat Kasus Baru
                                </Link>
                            </Button>
                        )}
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <form onSubmit={handleSearch} className="grid gap-3 md:grid-cols-2 lg:grid-cols-[1fr_180px_170px_160px_200px_auto]">
                            <div className="relative">
                                <Search className="text-muted-foreground pointer-events-none absolute top-3.5 left-3 h-4 w-4" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari No. Kasus, No. RM, atau nama pasien..."
                                    className="pl-9"
                                />
                            </div>

                            <Select
                                value={statusFilter}
                                onValueChange={(value) => {
                                    setStatusFilter(value);
                                    applyFilters({ q: search, status: value, date: dateFilter, room_id: roomFilter, surgeon_id: surgeonFilter });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    {statuses.map((st) => (
                                        <SelectItem key={st.value} value={st.value}>
                                            {st.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Input
                                type="date"
                                value={dateFilter}
                                onChange={(e) => {
                                    const nextDate = e.target.value;
                                    setDateFilter(nextDate);
                                    applyFilters({ q: search, status: statusFilter, date: nextDate, room_id: roomFilter, surgeon_id: surgeonFilter });
                                }}
                            />

                            <Select
                                value={roomFilter}
                                onValueChange={(value) => {
                                    setRoomFilter(value);
                                    applyFilters({ q: search, status: statusFilter, date: dateFilter, room_id: value, surgeon_id: surgeonFilter });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Ruang" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Ruang</SelectItem>
                                    {rooms.map((room) => (
                                        <SelectItem key={room.id} value={String(room.id)}>
                                            {room.code}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={surgeonFilter}
                                onValueChange={(value) => {
                                    setSurgeonFilter(value);
                                    applyFilters({ q: search, status: statusFilter, date: dateFilter, room_id: roomFilter, surgeon_id: value });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Dokter" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Dokter</SelectItem>
                                    {surgeons.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>
                                            {s.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="flex gap-2">
                                <Button type="submit" variant="secondary">
                                    Cari
                                </Button>
                                {hasActiveFilters && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setSearch('');
                                            setStatusFilter('all');
                                            setDateFilter('');
                                            setRoomFilter('all');
                                            setSurgeonFilter('all');
                                            applyFilters({ q: '', status: 'all', date: '', room_id: 'all', surgeon_id: 'all' });
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
                                        <TableHead>No. Kasus & Jadwal</TableHead>
                                        <TableHead>Pasien</TableHead>
                                        <TableHead>Diagnosis & Tindakan</TableHead>
                                        <TableHead>Ruang & Operator</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {cases.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                                Belum ada kasus operasi yang sesuai dengan filter.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        cases.data.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    <div className="font-mono text-sm font-semibold">{item.case_number}</div>
                                                    <div className="text-muted-foreground text-xs">{item.scheduled_at} WIB</div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{item.patient_name}</div>
                                                    <div className="text-muted-foreground font-mono text-xs">RM: {item.patient_rm}</div>
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <div className="font-medium">{item.diagnosis}</div>
                                                    <div className="text-muted-foreground">{item.surgery_type}</div>
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <div>{item.room_code ?? 'Ruang belum ditetapkan'}</div>
                                                    <div className="text-muted-foreground">{item.surgeon_name ?? 'Operator belum ditetapkan'}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <CaseStatusBadge status={item.status} label={item.status_label} />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button variant="outline" size="sm" className="min-h-10" asChild>
                                                        <Link href={route('cases.show', item.id)}>
                                                            <Eye className="h-3.5 w-3.5" />
                                                            Buka Kasus
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {cases.last_page > 1 && (
                            <div className="flex flex-col items-center justify-between gap-3 pt-2 sm:flex-row">
                                <p className="text-muted-foreground text-sm">
                                    Menampilkan {cases.from ?? 0}–{cases.to ?? 0} dari {cases.total} kasus
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                    {cases.links.map((link, index) =>
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
        </AppLayout>
    );
}
