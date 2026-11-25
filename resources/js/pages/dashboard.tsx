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
    CalendarClock,
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

type UpcomingSurgeryItem = {
    id: number;
    case_number: string;
    patient_name: string;
    rm_number: string;
    surgery_type: string;
    diagnosis: string;
    surgeon_name: string;
    room_code: string;
    scheduled_at: string;
    scheduled_date: string;
    scheduled_time: string;
    day_badge: string;
    is_today: boolean;
    urgency_status: string | null;
    status: string;
    status_label: string;
    is_assigned_to_me: boolean;
};

type UpcomingSurgeriesData = {
    total_count: number;
    today_remaining_count: number;
    future_days_count: number;
    items: UpcomingSurgeryItem[];
};

interface DashboardProps {
    adminStats?: AdminStats | null;
    nurseDashboard?: NurseDashboardData | null;
    doctorDashboard?: DoctorDashboardData | null;
    headNurseDashboard?: HeadNurseDashboardData | null;
    upcomingSurgeries?: UpcomingSurgeriesData | null;
}

function UpcomingSurgeriesCard({ upcoming, userRole }: { upcoming: UpcomingSurgeriesData; userRole: string }) {
    return (
        <Card>
            <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="space-y-1">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <CalendarClock className="text-primary h-5 w-5 shrink-0" />
                        <span>Jadwal Operasi yang Akan Mendatang</span>
                    </CardTitle>
                    <CardDescription>
                        {upcoming.total_count === 0
                            ? 'Daftar antrean kasus Pre-Operasi dan Siap Operasi setelah waktu saat ini.'
                            : `${upcoming.total_count} jadwal aktif terdata (${upcoming.today_remaining_count} sisa hari ini, ${upcoming.future_days_count} hari berikutnya).`}
                    </CardDescription>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Button asChild variant="outline" className="min-h-11">
                        <Link href="/kasus">Lihat Semua Kasus</Link>
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                {upcoming.items.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed px-4 py-8 text-center">
                        <p className="text-muted-foreground text-sm">Belum ada jadwal operasi mendatang yang terdaftar di unit kamar operasi.</p>
                        {userRole === 'perawat' ? (
                            <Button asChild variant="outline" className="min-h-11">
                                <Link href="/kasus/buat">+ Buat Kasus Operasi</Link>
                            </Button>
                        ) : (
                            <Button asChild variant="outline" className="min-h-11">
                                <Link href="/kasus">Buka Daftar Kasus</Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <>
                        {/* Tampilan Kartu Terstruktur untuk Mobile & Tablet (< lg) */}
                        <div className="space-y-3 lg:hidden">
                            {upcoming.items.map((item) => (
                                <Link
                                    key={item.id}
                                    href={route('cases.show', item.id)}
                                    className="hover:bg-secondary/60 focus-visible:ring-ring block min-h-11 rounded-xl border p-3.5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div className="flex flex-wrap items-center gap-1.5">
                                            <Badge variant={item.is_today ? 'default' : 'secondary'} className="rounded-lg">
                                                {item.day_badge}
                                            </Badge>
                                            <span className="text-foreground text-xs font-semibold tabular-nums">
                                                {item.scheduled_date} • {item.scheduled_time} WIB
                                            </span>
                                        </div>
                                        <CaseStatusBadge status={item.status} label={item.status_label} />
                                    </div>

                                    <div className="mt-2.5">
                                        <div className="text-foreground text-sm font-semibold">
                                            {item.patient_name}{' '}
                                            <span className="text-muted-foreground font-mono text-xs font-normal tabular-nums">
                                                ({item.rm_number} • {item.case_number})
                                            </span>
                                        </div>
                                        <div className="text-muted-foreground mt-0.5 text-xs">
                                            {item.surgery_type} • {item.diagnosis}
                                        </div>
                                    </div>

                                    <div className="mt-2.5 flex flex-wrap items-center justify-between gap-2 border-t pt-2.5 text-xs">
                                        <div className="text-muted-foreground">
                                            <span className="text-foreground font-medium">Ruang {item.room_code}</span> • {item.surgeon_name}
                                        </div>
                                        <div className="flex flex-wrap items-center gap-1.5">
                                            {item.urgency_status === 'emergency' && (
                                                <Badge
                                                    variant="outline"
                                                    className="rounded-lg border-rose-300 text-[11px] text-rose-700 dark:border-rose-700 dark:text-rose-300"
                                                >
                                                    Darurat
                                                </Badge>
                                            )}
                                            {item.is_assigned_to_me && (
                                                <Badge variant="secondary" className="rounded-lg text-[11px]">
                                                    Tim Anda
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>

                        {/* Tampilan Tabel Padat untuk Desktop (lg+) */}
                        <div className="hidden overflow-x-auto rounded-xl border lg:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Jadwal Operasi</TableHead>
                                        <TableHead>Pasien & No. Kasus</TableHead>
                                        <TableHead>Tindakan & Diagnosis</TableHead>
                                        <TableHead>Ruang & Operator</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {upcoming.items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="whitespace-nowrap">
                                                <div className="flex items-center gap-2">
                                                    <Badge variant={item.is_today ? 'default' : 'secondary'} className="rounded-lg">
                                                        {item.day_badge}
                                                    </Badge>
                                                    <span className="text-sm font-semibold tabular-nums">{item.scheduled_time} WIB</span>
                                                </div>
                                                <div className="text-muted-foreground mt-0.5 text-xs tabular-nums">{item.scheduled_date}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap items-center gap-1.5">
                                                    <span className="text-sm font-semibold">{item.patient_name}</span>
                                                    {item.is_assigned_to_me && (
                                                        <Badge variant="secondary" className="rounded-lg text-[11px]">
                                                            Tim Anda
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="text-muted-foreground font-mono text-xs tabular-nums">
                                                    {item.rm_number} • {item.case_number}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap items-center gap-1.5">
                                                    <span className="text-sm font-medium">{item.surgery_type}</span>
                                                    {item.urgency_status === 'emergency' && (
                                                        <Badge
                                                            variant="outline"
                                                            className="rounded-lg border-rose-300 text-[11px] text-rose-700 dark:border-rose-700 dark:text-rose-300"
                                                        >
                                                            Darurat
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="text-muted-foreground text-xs">{item.diagnosis}</div>
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                <div className="text-foreground font-medium">Ruang {item.room_code}</div>
                                                <div className="text-muted-foreground">{item.surgeon_name}</div>
                                            </TableCell>
                                            <TableCell>
                                                <CaseStatusBadge status={item.status} label={item.status_label} />
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button asChild variant="outline" className="min-h-11 px-3.5">
                                                    <Link href={route('cases.show', item.id)}>Buka Kasus</Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </>
                )}
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ adminStats, nurseDashboard, doctorDashboard, headNurseDashboard, upcomingSurgeries }: DashboardProps) {
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
                                    <Button asChild className="min-h-11">
                                        <Link href="/kasus/buat">+ Kasus Operasi Baru</Link>
                                    </Button>
                                )}
                                {user.role === 'kepala_ruangan' && (
                                    <>
                                        <Button asChild variant="outline" className="min-h-11">
                                            <Link href="/laporan">
                                                <BarChart3 className="mr-1.5 h-4 w-4" />
                                                Rekap Bulanan
                                            </Link>
                                        </Button>
                                        <Button asChild variant="outline" className="min-h-11">
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
                                    <CardTitle className="text-2xl tabular-nums">{adminStats.users_active} Aktif</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between gap-2 text-xs">
                                    <span>
                                        Total {adminStats.users_total} akun ({adminStats.users_inactive} nonaktif)
                                    </span>
                                    <Button variant="outline" asChild className="min-h-11 px-3">
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
                                    <CardTitle className="text-2xl tabular-nums">{adminStats.rooms_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between gap-2 text-xs">
                                    <span>Kamar operasi terdaftar</span>
                                    <Button variant="outline" asChild className="min-h-11 px-3">
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
                                    <CardTitle className="text-2xl tabular-nums">{adminStats.instruments_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between gap-2 text-xs">
                                    <span>Master cek kesiapan alat</span>
                                    <Button variant="outline" asChild className="min-h-11 px-3">
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
                                    <CardTitle className="text-2xl tabular-nums">{adminStats.surgery_types_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between gap-2 text-xs">
                                    <span>Daftar tindakan operasi</span>
                                    <Button variant="outline" asChild className="min-h-11 px-3">
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
                                    <CardTitle className="text-2xl tabular-nums">{adminStats.anesthesia_types_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between gap-2 text-xs">
                                    <span>Pilihan anestesi intra-op</span>
                                    <Button variant="outline" asChild className="min-h-11 px-3">
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
                                    <CardTitle className="text-2xl tabular-nums">{adminStats.medical_diagnoses_active}</CardTitle>
                                </CardHeader>
                                <CardContent className="text-muted-foreground flex items-center justify-between gap-2 text-xs">
                                    <span>Master diagnosis pra-op</span>
                                    <Button variant="outline" asChild className="min-h-11 px-3">
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
                                <Button variant="outline" asChild className="min-h-11">
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
                                    <CardTitle className="text-2xl tabular-nums">{nurseDashboard.stats.today_total}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Saya Hari Ini</CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">{nurseDashboard.stats.my_today_count}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Aktif Saya</CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">{nurseDashboard.stats.my_active_count}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Tugas Belum Lengkap</CardDescription>
                                    <CardTitle className="text-2xl text-amber-700 tabular-nums dark:text-amber-300">
                                        {nurseDashboard.stats.pending_tasks_count}
                                    </CardTitle>
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
                                        <div className="space-y-2.5">
                                            {nurseDashboard.my_today_cases.map((c) => (
                                                <Link
                                                    key={c.id}
                                                    href={route('cases.show', c.id)}
                                                    className="hover:bg-secondary/60 flex min-h-11 items-center justify-between gap-2 rounded-xl border p-3 text-sm transition-colors"
                                                >
                                                    <div>
                                                        <div className="font-semibold">
                                                            {c.patient_name}{' '}
                                                            <span className="text-muted-foreground font-mono text-xs tabular-nums">
                                                                ({c.case_number})
                                                            </span>
                                                        </div>
                                                        <div className="text-muted-foreground text-xs tabular-nums">
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
                                        <AlertTriangle className="h-5 w-5 text-amber-700 dark:text-amber-300" />
                                        Tugas Belum Lengkap (SSC & Pengesahan)
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {nurseDashboard.pending_tasks.length === 0 ? (
                                        <p className="text-muted-foreground py-6 text-center text-sm">Semua tugas aktif Anda sudah lengkap.</p>
                                    ) : (
                                        <div className="space-y-2.5">
                                            {nurseDashboard.pending_tasks.map((c) => (
                                                <Link
                                                    key={c.id}
                                                    href={route('cases.show', c.id)}
                                                    className="hover:bg-secondary/60 flex min-h-11 items-center justify-between gap-2 rounded-xl border p-3 text-sm transition-colors"
                                                >
                                                    <div>
                                                        <div className="font-semibold">
                                                            {c.patient_name}{' '}
                                                            <span className="text-muted-foreground font-mono text-xs tabular-nums">
                                                                ({c.case_number})
                                                            </span>
                                                        </div>
                                                        <div className="text-xs text-amber-700 dark:text-amber-300">
                                                            {c.pending_items.join(' • ')}
                                                        </div>
                                                    </div>
                                                    <CaseStatusBadge status={c.status} label={c.status_label} />
                                                </Link>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>

                        {upcomingSurgeries && <UpcomingSurgeriesCard upcoming={upcomingSurgeries} userRole={user.role} />}
                    </div>
                )}

                {/* DASHBOARD DOKTER */}
                {(user?.role === 'dokter_operator' || user?.role === 'dokter_anestesi') && doctorDashboard && (
                    <div className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Total Kasus Saya</CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">{doctorDashboard.stats.my_cases_total}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Aktif</CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">{doctorDashboard.stats.my_active_cases}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Menunggu Verifikasi / Pengesahan</CardDescription>
                                    <CardTitle className="text-2xl text-amber-700 tabular-nums dark:text-amber-300">
                                        {doctorDashboard.stats.waiting_verification_count}
                                    </CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Kasus Selesai</CardDescription>
                                    <CardTitle className="text-2xl text-emerald-700 tabular-nums dark:text-emerald-300">
                                        {doctorDashboard.stats.completed_cases}
                                    </CardTitle>
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
                                    <div className="space-y-2.5">
                                        {doctorDashboard.waiting_cases.map((c) => (
                                            <Link
                                                key={c.id}
                                                href={route('cases.show', c.id)}
                                                className="hover:bg-secondary/60 flex min-h-11 items-center justify-between gap-2 rounded-xl border p-3 text-sm transition-colors"
                                            >
                                                <div>
                                                    <div className="font-semibold">
                                                        {c.patient_name}{' '}
                                                        <span className="text-muted-foreground font-mono text-xs tabular-nums">
                                                            ({c.case_number})
                                                        </span>
                                                    </div>
                                                    <div className="text-xs text-amber-700 dark:text-amber-300">{c.needs.join(' • ')}</div>
                                                </div>
                                                <CaseStatusBadge status={c.status} label={c.status_label} />
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {upcomingSurgeries && <UpcomingSurgeriesCard upcoming={upcomingSurgeries} userRole={user.role} />}
                    </div>
                )}

                {/* DASHBOARD KEPALA RUANGAN (KF-40) */}
                {user?.role === 'kepala_ruangan' && headNurseDashboard && (
                    <div className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Total Kasus Operasi</CardDescription>
                                    <CardTitle className="text-2xl tabular-nums">{headNurseDashboard.stats.total_cases}</CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Rata-rata Kelengkapan</CardDescription>
                                    <CardTitle className="text-2xl text-emerald-700 tabular-nums dark:text-emerald-300">
                                        {headNurseDashboard.stats.average_completeness}%
                                    </CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>SSC Belum Selesai</CardDescription>
                                    <CardTitle className="text-2xl text-amber-700 tabular-nums dark:text-amber-300">
                                        {headNurseDashboard.stats.ssc_incomplete}
                                    </CardTitle>
                                </CardHeader>
                            </Card>
                            <Card>
                                <CardHeader className="pb-2">
                                    <CardDescription>Belum Diverifikasi Dokter</CardDescription>
                                    <CardTitle className="text-2xl text-rose-700 tabular-nums dark:text-rose-300">
                                        {headNurseDashboard.stats.pending_doctor_verification}
                                    </CardTitle>
                                </CardHeader>
                            </Card>
                        </div>

                        {/* Jumlah Kasus per Status */}
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            {headNurseDashboard.status_counts.map((sc) => (
                                <Card key={sc.status}>
                                    <CardContent className="p-4 text-center">
                                        <div className="text-muted-foreground text-xs">{sc.label}</div>
                                        <div className="mt-1 text-2xl font-bold tabular-nums">{sc.count}</div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>

                        {upcomingSurgeries && <UpcomingSurgeriesCard upcoming={upcomingSurgeries} userRole={user.role} />}

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
                                    <Button asChild variant="outline" className="min-h-11">
                                        <Link href="/laporan">Lihat Rekap Bulanan Lengkap</Link>
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto rounded-xl border">
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
                                                                className="text-primary inline-flex min-h-11 flex-col justify-center font-semibold hover:underline"
                                                            >
                                                                <span className="tabular-nums">{c.case_number}</span>
                                                                <span className="text-muted-foreground text-xs font-normal">{c.patient_name}</span>
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell className="text-xs tabular-nums">
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
                                                                <span className="text-xs font-semibold tabular-nums">{c.completeness_pct}%</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="space-x-1">
                                                            {c.ssc_incomplete ? (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="border-amber-400 text-[11px] text-amber-700 dark:border-amber-600 dark:text-amber-300"
                                                                >
                                                                    SSC Belum Selesai
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="secondary" className="text-[11px]">
                                                                    SSC Lengkap
                                                                </Badge>
                                                            )}
                                                            {c.pending_verification && (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="border-rose-300 text-[11px] text-rose-700 dark:border-rose-700 dark:text-rose-300"
                                                                >
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
