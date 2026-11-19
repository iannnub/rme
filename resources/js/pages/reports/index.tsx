import { CaseStatusBadge } from '@/components/cases/case-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { BarChart3, Download, FileSearch } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan & Rekap Bulanan', href: '/laporan' },
];

interface ReportPageProps {
    filters: {
        month: number;
        year: number;
        period_label: string;
    };
    summary: {
        total_cases: number;
        completed_cases: number;
        average_completeness: number;
        ssc_incomplete: number;
        pending_doctor_verification: number;
    };
    statusCounts: Array<{ status: string; label: string; count: number }>;
    byRoom: Array<{ label: string; count: number }>;
    byAnesthesia: Array<{ label: string; count: number }>;
    sixMonthTrend: Array<{ label: string; total: number; completed: number }>;
    cases: Array<{
        id: number;
        case_number: string;
        patient_name: string;
        rm_number: string;
        scheduled_at: string;
        room_code: string;
        surgery_type: string;
        surgeon_name: string;
        status: string;
        status_label: string;
        completeness_pct: number;
        ssc_incomplete: boolean;
        pending_verification: boolean;
    }>;
}

const MONTHS = [
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

export default function ReportsIndexPage({ filters, summary, statusCounts, byRoom, byAnesthesia, sixMonthTrend, cases }: ReportPageProps) {
    const [month, setMonth] = useState(String(filters.month));
    const [year, setYear] = useState(String(filters.year));

    const years = [filters.year - 1, filters.year, filters.year + 1].map(String);

    const applyFilter = () => {
        router.get('/laporan', { month, year }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Laporan Bulanan - ${filters.period_label}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-bold">
                            <BarChart3 className="text-primary h-5 w-5" />
                            Laporan & Rekap Bulanan Kamar Operasi
                        </h1>
                        <p className="text-muted-foreground text-sm">Periode: {filters.period_label}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Select value={month} onValueChange={setMonth}>
                            <SelectTrigger className="min-h-11 w-[150px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {MONTHS.map((m) => (
                                    <SelectItem key={m.value} value={m.value}>
                                        {m.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={year} onValueChange={setYear}>
                            <SelectTrigger className="min-h-11 w-[110px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {years.map((y) => (
                                    <SelectItem key={y} value={y}>
                                        {y}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button type="button" className="min-h-11" onClick={applyFilter}>
                            Terapkan
                        </Button>
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href="/audit">
                                <FileSearch className="mr-1.5 h-4 w-4" />
                                Audit Global
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Ringkasan Utama */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total Kasus</CardDescription>
                            <CardTitle className="text-2xl">{summary.total_cases}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Kasus Selesai</CardDescription>
                            <CardTitle className="text-2xl text-emerald-600">{summary.completed_cases}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Rata-rata Kelengkapan</CardDescription>
                            <CardTitle className="text-2xl">{summary.average_completeness}%</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>SSC Belum Selesai</CardDescription>
                            <CardTitle className="text-2xl text-amber-600">{summary.ssc_incomplete}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Belum Diverifikasi Dokter</CardDescription>
                            <CardTitle className="text-2xl text-rose-600">{summary.pending_doctor_verification}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Distribusi Status, Ruang, Anestesi, dan Tren 6 Bulan */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Distribusi per Status Kasus</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {statusCounts.map((item) => (
                                <div key={item.status} className="flex items-center justify-between border-b pb-1.5 last:border-none">
                                    <span>{item.label}</span>
                                    <Badge variant="secondary">{item.count}</Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Distribusi per Ruang & Jenis Anestesi</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div>
                                <div className="text-muted-foreground mb-1 text-xs font-semibold">Kamar Operasi</div>
                                {byRoom.length === 0 ? (
                                    <p className="text-muted-foreground text-xs">Tidak ada data.</p>
                                ) : (
                                    byRoom.map((r) => (
                                        <div key={r.label} className="flex justify-between py-0.5 text-xs">
                                            <span>{r.label}</span>
                                            <strong>{r.count}</strong>
                                        </div>
                                    ))
                                )}
                            </div>
                            <div className="border-t pt-2">
                                <div className="text-muted-foreground mb-1 text-xs font-semibold">Jenis Anestesi</div>
                                {byAnesthesia.length === 0 ? (
                                    <p className="text-muted-foreground text-xs">Tidak ada data.</p>
                                ) : (
                                    byAnesthesia.map((a) => (
                                        <div key={a.label} className="flex justify-between py-0.5 text-xs">
                                            <span>{a.label}</span>
                                            <strong>{a.count}</strong>
                                        </div>
                                    ))
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Tren 6 Bulan Terakhir</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {sixMonthTrend.map((m) => (
                                <div key={m.label} className="flex items-center justify-between border-b pb-1.5 text-xs last:border-none">
                                    <span className="font-medium">{m.label}</span>
                                    <span>
                                        Total <strong>{m.total}</strong> • Selesai <strong className="text-emerald-700">{m.completed}</strong>
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                </div>

                {/* Tabel Rincian Kasus Bulanan */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Rincian Kasus Periode {filters.period_label}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>No. Kasus & Pasien</TableHead>
                                        <TableHead>Jadwal & Ruang</TableHead>
                                        <TableHead>Operasi & Operator</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Kelengkapan</TableHead>
                                        <TableHead className="text-right">Ekspor</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {cases.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground py-8 text-center text-sm">
                                                Tidak ada kasus operasi pada periode {filters.period_label}.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        cases.map((c) => (
                                            <TableRow key={c.id}>
                                                <TableCell>
                                                    <Link href={route('cases.show', c.id)} className="text-primary font-semibold hover:underline">
                                                        {c.case_number}
                                                    </Link>
                                                    <div className="text-muted-foreground text-xs">
                                                        {c.patient_name} (RM: {c.rm_number})
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <div>{c.scheduled_at}</div>
                                                    <div className="text-muted-foreground">Ruang {c.room_code}</div>
                                                </TableCell>
                                                <TableCell className="text-xs">
                                                    <div className="font-medium">{c.surgery_type}</div>
                                                    <div className="text-muted-foreground">{c.surgeon_name}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <CaseStatusBadge status={c.status} label={c.status_label} />
                                                </TableCell>
                                                <TableCell className="text-xs font-semibold">{c.completeness_pct}%</TableCell>
                                                <TableCell className="text-right">
                                                    <Button asChild size="sm" variant="outline">
                                                        <a href={route('cases.pdf.full', c.id)}>
                                                            <Download className="mr-1 h-3.5 w-3.5" />
                                                            PDF
                                                        </a>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
