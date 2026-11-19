import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import CaseLayout, { type CaseHeaderData } from '@/layouts/case-layout';
import { type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileClock, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type AuditChange = {
    field: string;
    label: string;
    old: string | null;
    new: string | null;
};

type AuditLogItem = {
    id: number;
    created_at: string;
    causer_name: string;
    event: string;
    event_label: string;
    module_label: string;
    reason: string | null;
    changes: AuditChange[];
};

interface CaseHistoryProps {
    caseHeader: CaseHeaderData;
    logs: PaginatedData<AuditLogItem>;
    filters: {
        event: string;
        q: string;
    };
}

function formatPageLabel(label: string): string {
    if (label.includes('Previous') || label.includes('Sebelumnya')) {
        return 'Sebelumnya';
    }
    if (label.includes('Next') || label.includes('Berikutnya')) {
        return 'Berikutnya';
    }

    return label;
}

export default function CaseHistory({ caseHeader, logs, filters }: CaseHistoryProps) {
    const [search, setSearch] = useState(filters.q);
    const [eventFilter, setEventFilter] = useState(filters.event || 'all');

    const applyFilters = (nextQ: string, nextEvent: string) => {
        router.get(
            route('cases.history', caseHeader.id),
            {
                q: nextQ || undefined,
                event: nextEvent !== 'all' ? nextEvent : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleSearch: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, eventFilter);
    };

    return (
        <CaseLayout caseHeader={caseHeader} activeTab="riwayat">
            <Head title={`Riwayat Perubahan - ${caseHeader.case_number}`} />

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                        <FileClock className="text-primary h-5 w-5" />
                        Riwayat Perubahan Kasus
                    </CardTitle>
                    <CardDescription>Jejak audit read-only setiap pembuatan, perubahan data, dan perpindahan status kasus ini.</CardDescription>
                </CardHeader>

                <CardContent className="space-y-4">
                    <form onSubmit={handleSearch} className="grid gap-3 md:grid-cols-[1fr_200px_auto]">
                        <div className="relative">
                            <Search className="text-muted-foreground pointer-events-none absolute top-3.5 left-3 h-4 w-4" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama petugas, field, nilai, atau alasan..."
                                className="pl-9"
                            />
                        </div>

                        <Select
                            value={eventFilter}
                            onValueChange={(value) => {
                                setEventFilter(value);
                                applyFilters(search, value);
                            }}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Aksi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Aksi</SelectItem>
                                <SelectItem value="created">Dibuat</SelectItem>
                                <SelectItem value="updated">Diubah</SelectItem>
                                <SelectItem value="deleted">Dihapus</SelectItem>
                                <SelectItem value="restored">Dipulihkan</SelectItem>
                            </SelectContent>
                        </Select>

                        <div className="flex gap-2">
                            <Button type="submit" variant="secondary">
                                Cari
                            </Button>
                            {(filters.q || filters.event) && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch('');
                                        setEventFilter('all');
                                        applyFilters('', 'all');
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
                                    <TableHead>Waktu (WIB)</TableHead>
                                    <TableHead>Pengguna</TableHead>
                                    <TableHead>Modul & Aksi</TableHead>
                                    <TableHead>Perubahan Data (Lama → Baru)</TableHead>
                                    <TableHead>Alasan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-muted-foreground py-10 text-center">
                                            Belum ada catatan perubahan untuk kasus ini.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    logs.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="text-xs whitespace-nowrap">{item.created_at} WIB</TableCell>
                                            <TableCell className="text-sm font-medium">{item.causer_name}</TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1.5">
                                                    <Badge variant="outline">{item.module_label}</Badge>
                                                    <Badge variant="secondary">{item.event_label}</Badge>
                                                </div>
                                            </TableCell>
                                            <TableCell className="max-w-md text-xs">
                                                {item.changes.length === 0 ? (
                                                    <span className="text-muted-foreground">-</span>
                                                ) : (
                                                    <ul className="space-y-1">
                                                        {item.changes.map((chg, idx) => (
                                                            <li key={idx} className="leading-relaxed">
                                                                <span className="font-semibold">{chg.label}:</span>{' '}
                                                                {chg.old !== null ? (
                                                                    <>
                                                                        <span className="text-muted-foreground line-through">{chg.old}</span>{' '}
                                                                        <span>→</span>{' '}
                                                                    </>
                                                                ) : null}
                                                                <span className="text-foreground font-medium">{chg.new ?? '(kosong)'}</span>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground text-xs">{item.reason ?? '-'}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {logs.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 pt-2 sm:flex-row">
                            <p className="text-muted-foreground text-sm">
                                Menampilkan {logs.from ?? 0}–{logs.to ?? 0} dari {logs.total} log
                            </p>
                            <div className="flex flex-wrap gap-1.5">
                                {logs.links.map((link, index) =>
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
        </CaseLayout>
    );
}
