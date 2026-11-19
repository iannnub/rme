import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileSearch, RotateCcw, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Audit Log Global', href: '/audit' },
];

type AuditRow = {
    id: number;
    created_at: string;
    case_id: number | null;
    case_number: string | null;
    causer_name: string;
    event: string;
    event_label: string;
    module_label: string;
    reason: string | null;
    ip_address: string;
    changes: Array<{ field: string; old: string | null; new: string | null }>;
};

interface AuditIndexPageProps {
    logs: {
        data: AuditRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        total: number;
    };
    filters: {
        case_id: string;
        user_id: string;
        event: string;
        date_from: string;
        date_to: string;
        q: string;
    };
    options: {
        cases: Array<{ id: number; case_number: string }>;
        users: Array<{ id: number; name: string }>;
    };
}

export default function AuditIndexPage({ logs, filters, options }: AuditIndexPageProps) {
    const [caseId, setCaseId] = useState(filters.case_id || 'all');
    const [userId, setUserId] = useState(filters.user_id || 'all');
    const [event, setEvent] = useState(filters.event || 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);
    const [q, setQ] = useState(filters.q);

    const handleFilter = (e: FormEvent) => {
        e.preventDefault();
        router.get(
            '/audit',
            {
                case_id: caseId === 'all' ? '' : caseId,
                user_id: userId === 'all' ? '' : userId,
                event: event === 'all' ? '' : event,
                date_from: dateFrom,
                date_to: dateTo,
                q,
            },
            { preserveState: true },
        );
    };

    const handleReset = () => {
        setCaseId('all');
        setUserId('all');
        setEvent('all');
        setDateFrom('');
        setDateTo('');
        setQ('');
        router.get('/audit');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Log Global" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="flex items-center gap-2 text-xl font-bold">
                        <FileSearch className="text-primary h-5 w-5" />
                        Audit Log Global Perubahan Data Klinis
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Catatan permanen (read-only) seluruh perubahan data klinis dan pengesahan. Hanya dapat diakses oleh Kepala Ruangan.
                    </p>
                </div>

                {/* Filter Audit */}
                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleFilter} className="grid grid-cols-1 gap-3 md:grid-cols-6">
                            <div className="space-y-1">
                                <Label className="text-xs">Kasus</Label>
                                <Select value={caseId} onValueChange={setCaseId}>
                                    <SelectTrigger className="min-h-10">
                                        <SelectValue placeholder="Semua kasus" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Kasus</SelectItem>
                                        {options.cases.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.case_number}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Pengguna</Label>
                                <Select value={userId} onValueChange={setUserId}>
                                    <SelectTrigger className="min-h-10">
                                        <SelectValue placeholder="Semua pengguna" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Pengguna</SelectItem>
                                        {options.users.map((u) => (
                                            <SelectItem key={u.id} value={String(u.id)}>
                                                {u.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Aktivitas</Label>
                                <Select value={event} onValueChange={setEvent}>
                                    <SelectTrigger className="min-h-10">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Aktivitas</SelectItem>
                                        <SelectItem value="created">Dibuat</SelectItem>
                                        <SelectItem value="updated">Diubah</SelectItem>
                                        <SelectItem value="deleted">Dihapus</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Dari Tanggal</Label>
                                <Input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="min-h-10" />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Sampai Tanggal</Label>
                                <Input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="min-h-10" />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Kata Kunci / Alasan</Label>
                                <Input
                                    value={q}
                                    onChange={(e) => setQ(e.target.value)}
                                    placeholder="Cari alasan / kasus / user"
                                    className="min-h-10"
                                />
                            </div>

                            <div className="flex flex-wrap justify-end gap-2 md:col-span-6">
                                <Button type="button" variant="outline" onClick={handleReset}>
                                    <RotateCcw className="mr-1.5 h-4 w-4" />
                                    Reset
                                </Button>
                                <Button type="submit">
                                    <Search className="mr-1.5 h-4 w-4" />
                                    Filter Audit
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Tabel Audit Global */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Daftar Aktivitas ({logs.total} entri)</CardTitle>
                        <CardDescription>Menampilkan waktu WIB, petugas, modul, perubahan nilai lama → baru, dan alasan.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[130px]">Waktu (WIB)</TableHead>
                                        <TableHead>Kasus</TableHead>
                                        <TableHead>Pengguna</TableHead>
                                        <TableHead>Modul & Aksi</TableHead>
                                        <TableHead className="min-w-[260px]">Nilai Lama → Baru</TableHead>
                                        <TableHead>Alasan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logs.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground py-8 text-center text-sm">
                                                Tidak ada catatan audit yang cocok dengan filter.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        logs.data.map((log) => (
                                            <TableRow key={log.id}>
                                                <TableCell className="font-mono text-xs">{log.created_at}</TableCell>
                                                <TableCell className="text-xs">
                                                    {log.case_id && log.case_number ? (
                                                        <Link
                                                            href={route('cases.show', log.case_id)}
                                                            className="text-primary font-mono font-semibold hover:underline"
                                                        >
                                                            {log.case_number}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-muted-foreground">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-xs font-medium">{log.causer_name}</TableCell>
                                                <TableCell className="text-xs">
                                                    <div className="font-medium">{log.module_label}</div>
                                                    <Badge variant="outline" className="mt-0.5 text-[10px]">
                                                        {log.event_label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    {log.changes.length === 0 ? (
                                                        <span className="text-muted-foreground">-</span>
                                                    ) : (
                                                        <div className="space-y-1">
                                                            {log.changes.slice(0, 5).map((ch) => (
                                                                <div key={ch.field} className="break-all">
                                                                    <span className="font-semibold">{ch.field}:</span>{' '}
                                                                    <span className="text-muted-foreground">{ch.old ?? '∅'}</span> →{' '}
                                                                    <span className="text-foreground">{ch.new ?? '∅'}</span>
                                                                </div>
                                                            ))}
                                                            {log.changes.length > 5 && (
                                                                <span className="text-muted-foreground text-[11px]">
                                                                    +{log.changes.length - 5} field lainnya
                                                                </span>
                                                            )}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-xs">{log.reason ?? '-'}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {logs.links.length > 3 && (
                            <div className="flex flex-wrap justify-end gap-1">
                                {logs.links.map((link, idx) => (
                                    <Button
                                        key={idx}
                                        size="sm"
                                        variant={link.active ? 'default' : 'outline'}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.visit(link.url, { preserveState: true })}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
