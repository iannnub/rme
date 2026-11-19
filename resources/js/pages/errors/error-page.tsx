import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, RefreshCw, ShieldAlert } from 'lucide-react';

interface ErrorPageProps {
    status: number;
}

const errorContent: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Akses Ditolak',
        description: 'Anda tidak memiliki izin untuk membuka halaman atau melakukan tindakan ini.',
    },
    404: {
        title: 'Halaman Tidak Ditemukan',
        description: 'Halaman atau data yang Anda cari tidak tersedia atau telah dipindahkan.',
    },
    419: {
        title: 'Sesi Kedaluwarsa',
        description: 'Halaman terlalu lama tidak aktif atau token keamanan kedaluwarsa. Muat ulang halaman lalu coba kembali.',
    },
    500: {
        title: 'Terjadi Kesalahan Server',
        description: 'Sistem mengalami kendala teknis saat memproses permintaan Anda. Silakan coba beberapa saat lagi.',
    },
    503: {
        title: 'Layanan Sedang Dalam Pemeliharaan',
        description: 'Aplikasi sedang menjalani pemeliharaan rutin. Silakan akses kembali beberapa saat lagi.',
    },
};

export default function ErrorPage({ status }: ErrorPageProps) {
    const { auth } = usePage<SharedData>().props;
    const isAuthenticated = Boolean(auth?.user);

    const content = errorContent[status] ?? {
        title: 'Terjadi Kesalahan',
        description: 'Permintaan Anda tidak dapat diproses saat ini.',
    };

    const Icon = status === 403 ? ShieldAlert : AlertTriangle;

    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center p-6">
            <Head title={`${status} - ${content.title}`} />

            <div className="bg-card border-border w-full max-w-lg rounded-xl border p-8 text-center shadow-sm">
                <div className="bg-destructive/10 text-destructive mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                    <Icon className="h-7 w-7" />
                </div>

                <p className="text-primary text-sm font-semibold tracking-wider uppercase">Kode {status}</p>
                <h1 className="text-foreground mt-1 text-2xl font-bold">{content.title}</h1>
                <p className="text-muted-foreground mt-3 text-sm leading-relaxed">{content.description}</p>

                <div className="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <Button asChild>
                        <Link href={isAuthenticated ? '/dashboard' : '/login'}>
                            <ArrowLeft className="h-4 w-4" />
                            {isAuthenticated ? 'Kembali ke Dashboard' : 'Ke Halaman Masuk'}
                        </Link>
                    </Button>
                    <Button type="button" variant="outline" onClick={() => window.location.reload()}>
                        <RefreshCw className="h-4 w-4" />
                        Muat Ulang
                    </Button>
                </div>
            </div>
        </div>
    );
}
