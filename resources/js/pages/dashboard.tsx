import { CaseStatusBadge } from '@/components/cases/case-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type AdminStats, type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    Building2,
    CheckCircle2,
    ClipboardCheck,
    DoorOpen,
    FileSearch,
    Scissors,
    ShieldCheck,
    Stethoscope,
    Syringe,
    Users,
    Wrench,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

type SimpleCaseRow = {
    id: number;
    case_number: string;
    patient_name: string;
    status: string;
    status_label: string;
    scheduled_at: string;
    room_code: string;
};

type NurseDashboardData = {
    stats: {
        today_total: number;
        my_today_count: number;
        my_active_count: number;
        pending_tasks_count: number;
    };
    my_today_cases: SimpleCaseRow[];
    pending_tasks: Array<SimpleCaseRow & { pending_items: string[] }>;
};

type DoctorDashboardData = {
    stats: {
        my_cases_total: number;
        my_active_cases: number;
        waiting_verification_count: number;
        completed_cases: number;
    };
    waiting_cases: Array<SimpleCaseRow & { needs: string[] }>;
};

type HeadNurseDashboardData = {
    stats: {
        total_cases: number;
        average_completeness: number;
        ssc_incomplete: number;
        pending_doctor_verification: number;
    };
    status_counts: Array<{ status: string; label: string; count: number }>;
    recent_cases: Array<
        SimpleCaseRow & {
            completeness_pct: number;
            ssc_incomplete: boolean;
            pending_verification: boolean;
        }
    >;
};

interface DashboardProps {
    adminStats?: AdminStats | null;
    nurseDashboard?: NurseDashboardData | null;
    doctorDashboard?: DoctorDashboardData | null;
    headNurseDashboard?: HeadNurseDashboardData | null;
}

export default function Dashboard({ adminStats, nurseDashboard, doctorDashboard, headNurseDashboard }: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Kartu Profil Ringkas */}
                <Card>
                    <CardHeader className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle className="text-xl">Selamat Datang, {user?.name}</CardTitle>
                            <CardDescription>Sistem Dokumentasi Keperawatan Kamar Operasi (RME OKA)</CardDescription>
                        </div>
                        {user && (
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="secondary">{user.role_label}</Badge>
                                {user.nurse_type_label && <Badge variant="outline">{user.nurse_type_label}</Badge>}
                                {user.role === 'perawat' && (
                                    <Button asChild size="sm">
                                        <Link href="/kasus/buat">+ Kasus Operasi Baru</Link>
                                    </Button>
                                )}
                                {user.role === 'kepala_ruangan' && (
                                    <>
                                        <Button asChild size="sm" variant="outline">
                                            <Link href="/laporan">
                                                <BarChart3 className="mr-1.5 h-4 w-4" />
                                                Rekap Bulanan
                                            </Link>
                                        </Button>
                                        <Button asChild size="sm" variant="outline">
                                            <Link href="/audit">
                                                <FileSearch className="mr-1.5 h-4 w-4" />
                                                Audit Log
                                            </Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        )}
                    </CardHeader>
                </Card>

                {/* DASHBOARD ADMIN */}
                {user?.role === 'admin' && adminStats && (
                    <div className="space-y-4">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Card>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardDescription>Pengguna Sistem</CardDescription>
                                        <Users className="text-primary h-4 w-4" />
                                    </div>
                                    <CardTitle className="text-2xl">{adminStats.users_active} Aktif</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between text-xs">
                                    <span>
                                        Total {adminStats.users_total} akun ({adminStats.users_inactive} nonaktif)
                                    </span>
                                    <Button variant="link" size="sm" asChild className="h-auto p-0">
                                        <Link href="/admin/pengguna">Kelola</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardDescription>Ruang Operasi Aktif</CardDescription>
                                        <DoorOpen className="text-primary h-4 w-4" />
                                    </div>
                                    <CardTitle className="text-2xl">{adminStats.rooms_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between text-xs">
                                    <span>Kamar operasi terdaftar</span>
                                    <Button variant="link" size="sm" asChild className="h-auto p-0">
                                        <Link href="/admin/master/ruang">Kelola</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardDescription>Alat & Instrumen Aktif</CardDescription>
                                        <Wrench className="text-primary h-4 w-4" />
                                    </div>
                                    <CardTitle className="text-2xl">{adminStats.instruments_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between text-xs">
                                    <span>Master cek kesiapan alat</span>
                                    <Button variant="link" size="sm" asChild className="h-auto p-0">
                                        <Link href="/admin/master/alat">Kelola</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardDescription>Jenis Operasi Aktif</CardDescription>
                                        <Scissors className="text-primary h-4 w-4" />
                                    </div>
                                    <CardTitle className="text-2xl">{adminStats.surgery_types_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between text-xs">
                                    <span>Daftar tindakan operasi</span>
                                    <Button variant="link" size="sm" asChild className="h-auto p-0">
                                        <Link href="/admin/master/jenis-operasi">Kelola</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardDescription>Jenis Anestesi Aktif</CardDescription>
                                        <Syringe className="text-primary h-4 w-4" />
                                    </div>
                                    <CardTitle className="text-2xl">{adminStats.anesthesia_types_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between text-xs">
                                    <span>Pilihan anestesi intra-op</span>
                                    <Button variant="link" size="sm" asChild className="h-auto p-0">
                                        <Link href="/admin/master/jenis-anestesi">Kelola</Link>
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardDescription>Diagnosis Medis Aktif</CardDescription>
                                        <Stethoscope className="text-primary h-4 w-4" />
                                    </div>
                                    <CardTitle className="text-2xl">{adminStats.medical_diagnoses_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between text-xs">
                                    <span>Master diagnosis pra-op</span>
                                    <Button variant="link" size="sm" asChild className="h-auto p-0">
                                        <Link href="/admin/master/diagnosis-medis">Kelola</Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardContent className="flex flex-col items-start justify-between gap-3 py-4 sm:flex-row sm:items-center">
                                <div className="flex items-center gap-3">
                                    <Building2 className="text-primary h-5 w-5" />
                                    <div>
                                        <p className="text-sm font-medium">Identitas Rumah Sakit</p>
                                        <p className="text-muted-foreground text-xs">
                                            Atur nama, alamat, dan kontak rumah sakit untuk kop PDF rekam medis.
                                        </p>
                                    </div>
                                </div>
                                <Button variant="outline" asChild>
                                    <Link href="/pengaturan/rumah-sakit">Buka Data Rumah Sakit</Link>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* DASHBOARD PERAWAT */}
                {user?.role === 'perawat' && nurseDashboard && (
                    <div className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Total Kasus Hari Ini</CardDescription>
                                    <CardTitle className="text-2xl">{nurseDashboard.stats.today_total}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Saya Hari Ini</CardDescription>
                                    <CardTitle className="text-2xl">{nurseDashboard.stats.my_today_count}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Aktif Saya</CardDescription>
                                    <CardTitle className="text-2xl">{nurseDashboard.stats.my_active_count}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Tugas Belum Lengkap</CardDescription>
                                    <CardTitle className="text-2xl text-amber-600">{nurseDashboard.stats.pending_tasks_count}</CardTitle>
                                </CardHeader>
                            </Card>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <ClipboardCheck className="text-primary h-5 w-5" />
                                        Kasus Saya Hari Ini
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {nurseDashboard.my_today_cases.length === 0 ? (
                                        <p className="text-muted-foreground py-6 text-center text-sm">Tidak ada jadwal kasus Anda hari ini.</p>
                                    ) : (
                                        <div className="space-y-2">
                                            {nurseDashboard.my_today_cases.map((c) => (
                                                <Link
                                                    key={c.id}
                                                    href={route('cases.show', c.id)}
                                                    className="hover:bg-muted/50 flex items-center justify-between rounded-lg border p-3 text-sm transition-colors"
                                                >
                                                    <div>
                                                        <div className="font-semibold">
                                                            {c.patient_name}{' '}
                                                            <span className="text-muted-foreground font-mono text-xs">({c.case_number})</span>
                                                        </div>
                                                        <div className="text-muted-foreground text-xs">
                                                            {c.scheduled_at} WIB • Ruang {c.room_code}
                                                        </div>
                                                    </div>
                                                    <CaseStatusBadge status={c.status} label={c.status_label} />
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <AlertTriangle className="h-5 w-5 text-amber-600" />
                                        Tugas Belum Lengkap (SSC & Pengesahan)
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {nurseDashboard.pending_tasks.length === 0 ? (
                                        <p className="text-muted-foreground py-6 text-center text-sm">Semua tugas aktif Anda sudah lengkap.</p>
                                    ) : (
                                        <div className="space-y-2">
                                            {nurseDashboard.pending_tasks.map((c) => (
                                                <Link
                                                    key={c.id}
                                                    href={route('cases.show', c.id)}
                                                    className="hover:bg-muted/50 flex items-center justify-between gap-2 rounded-lg border p-3 text-sm transition-colors"
                                                >
                                                    <div>
                                                        <div className="font-semibold">
                                                            {c.patient_name} ({c.case_number})
                                                        </div>
                                                        <div className="text-xs text-amber-700">{c.pending_items.join(' • ')}</div>
                                                    </div>
                                                    <CaseStatusBadge status={c.status} label={c.status_label} />
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                )}

                {/* DASHBOARD DOKTER */}
                {(user?.role === 'dokter_operator' || user?.role === 'dokter_anestesi') && doctorDashboard && (
                    <div className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Total Kasus Saya</CardDescription>
                                    <CardTitle className="text-2xl">{doctorDashboard.stats.my_cases_total}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Aktif</CardDescription>
                                    <CardTitle className="text-2xl">{doctorDashboard.stats.my_active_cases}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Menunggu Verifikasi / Pengesahan</CardDescription>
                                    <CardTitle className="text-2xl text-amber-600">{doctorDashboard.stats.waiting_verification_count}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Selesai</CardDescription>
                                    <CardTitle className="text-2xl text-emerald-600">{doctorDashboard.stats.completed_cases}</CardTitle>
                                </CardHeader>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <ShieldCheck className="text-primary h-5 w-5" />
                                    Kasus Menunggu Verifikasi / Pengesahan Anda
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {doctorDashboard.waiting_cases.length === 0 ? (
                                    <p className="text-muted-foreground py-6 text-center text-sm">
                                        Tidak ada kasus yang menunggu verifikasi atau pengesahan Anda saat ini.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {doctorDashboard.waiting_cases.map((c) => (
                                            <Link
                                                key={c.id}
                                                href={route('cases.show', c.id)}
                                                className="hover:bg-muted/50 flex items-center justify-between rounded-lg border p-3 text-sm transition-colors"
                                            >
                                                <div>
                                                    <div className="font-semibold">
                                                        {c.patient_name}{' '}
                                                        <span className="text-muted-foreground font-mono text-xs">({c.case_number})</span>
                                                    </div>
                                                    <div className="text-xs text-amber-700">{c.needs.join(' • ')}</div>
                                                </div>
                                                <CaseStatusBadge status={c.status} label={c.status_label} />
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* DASHBOARD KEPALA RUANGAN (KF-40) */}
                {user?.role === 'kepala_ruangan' && headNurseDashboard && (
                    <div className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Total Kasus Operasi</CardDescription>
                                    <CardTitle className="text-2xl">{headNurseDashboard.stats.total_cases}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Rata-rata Kelengkapan</CardDescription>
                                    <CardTitle className="text-2xl text-emerald-600">{headNurseDashboard.stats.average_completeness}%</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>SSC Belum Selesai</CardDescription>
                                    <CardTitle className="text-2xl text-amber-600">{headNurseDashboard.stats.ssc_incomplete}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Belum Diverifikasi Dokter</CardDescription>
                                    <CardTitle className="text-2xl text-rose-600">{headNurseDashboard.stats.pending_doctor_verification}</CardTitle>
                                </CardHeader>
                            </Card>
                        </div>

                        {/* Jumlah Kasus per Status */}
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            {headNurseDashboard.status_counts.map((sc) => (
                                <Card key={sc.status}>
                                    <CardContent className="p-4 text-center">
                                        <div className="text-muted-foreground text-xs">{sc.label}</div>
                                        <div className="mt-1 text-2xl font-bold">{sc.count}</div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {/* Tabel Kelengkapan Kasus */}
                        <Card>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <CheckCircle2 className="text-primary h-5 w-5" />
                                            Monitoring Kelengkapan Dokumentasi Kasus
                                        </CardTitle>
                                        <CardDescription>Klik kasus untuk meninjau rincian rekam keperawatan atau mengunduh PDF.</CardDescription>
                                    </div>
                                    <Button asChild variant="outline" size="sm">
                                        <Link href="/laporan">Lihat Rekap Bulanan Lengkap</Link>
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>No. Kasus & Pasien</TableHead>
                                                <TableHead>Jadwal & Ruang</TableHead>
                                                <TableHead>Status</TableHead>
                                                <TableHead>Kelengkapan</TableHead>
                                                <TableHead>Kepatuhan SSC & Verifikasi</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {headNurseDashboard.recent_cases.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={5} className="text-muted-foreground py-6 text-center text-sm">
                                                        Belum ada kasus operasi tercatat.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                headNurseDashboard.recent_cases.map((c) => (
                                                    <TableRow key={c.id}>
                                                        <TableCell>
                                                            <Link
                                                                href={route('cases.show', c.id)}
                                                                className="text-primary font-semibold hover:underline"
                                                            >
                                                                {c.case_number}
                                                            </Link>
                                                            <div className="text-muted-foreground text-xs">{c.patient_name}</div>
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            <div>{c.scheduled_at}</div>
                                                            <div className="text-muted-foreground">Ruang {c.room_code}</div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <CaseStatusBadge status={c.status} label={c.status_label} />
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex items-center gap-2">
                                                                <div className="bg-muted h-2 w-20 overflow-hidden rounded-full">
                                                                    <div className="bg-primary h-full" style={{ width: `${c.completeness_pct}%` }} />
                                                                </div>
                                                                <span className="text-xs font-semibold">{c.completeness_pct}%</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="space-x-1">
                                                            {c.ssc_incomplete ? (
                                                                <Badge variant="outline" className="border-amber-400 text-[11px] text-amber-700">
                                                                    SSC Belum Selesai
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="secondary" className="text-[11px]">
                                                                    SSC Lengkap
                                                                </Badge>
                                                            )}
                                                            {c.pending_verification && (
                                                                <Badge variant="outline" className="border-rose-300 text-[11px] text-rose-700">
                                                                    Belum Verifikasi Dokter
                                                                </Badge>
                                                            )}
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
                )}
            </div>
        </AppLayout>
    );
}
