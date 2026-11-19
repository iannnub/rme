import { type NavGroup, type UserRole } from '@/types';
import {
    Activity,
    BarChart3,
    Building2,
    ClipboardList,
    DoorOpen,
    FileClock,
    LayoutGrid,
    Scissors,
    Settings,
    Stethoscope,
    Syringe,
    UserRound,
    Users,
    Wrench,
} from 'lucide-react';

export function getNavGroups(role?: UserRole): NavGroup[] {
    if (role === 'admin') {
        return [
            {
                title: 'Menu Utama',
                items: [
                    {
                        title: 'Dashboard',
                        url: '/dashboard',
                        icon: LayoutGrid,
                    },
                ],
            },
            {
                title: 'Administrasi',
                items: [
                    {
                        title: 'Pengguna',
                        url: '/admin/pengguna',
                        icon: Users,
                    },
                    {
                        title: 'Master Ruang',
                        url: '/admin/master/ruang',
                        icon: DoorOpen,
                    },
                    {
                        title: 'Master Alat',
                        url: '/admin/master/alat',
                        icon: Wrench,
                    },
                    {
                        title: 'Jenis Operasi',
                        url: '/admin/master/jenis-operasi',
                        icon: Scissors,
                    },
                    {
                        title: 'Jenis Anestesi',
                        url: '/admin/master/jenis-anestesi',
                        icon: Syringe,
                    },
                    {
                        title: 'Diagnosis Medis',
                        url: '/admin/master/diagnosis-medis',
                        icon: Stethoscope,
                    },
                    {
                        title: 'Data Rumah Sakit',
                        url: '/pengaturan/rumah-sakit',
                        icon: Building2,
                    },
                ],
            },
            {
                title: 'Akun',
                items: [
                    {
                        title: 'Pengaturan',
                        url: '/pengaturan',
                        icon: Settings,
                    },
                ],
            },
        ];
    }

    if (role === 'kepala_ruangan') {
        return [
            {
                title: 'Menu Utama',
                items: [
                    {
                        title: 'Dashboard',
                        url: '/dashboard',
                        icon: LayoutGrid,
                    },
                    {
                        title: 'Kasus Operasi',
                        url: '/kasus',
                        icon: ClipboardList,
                    },
                    {
                        title: 'Pasien',
                        url: '/pasien',
                        icon: UserRound,
                    },
                    {
                        title: 'Laporan & Rekap',
                        url: '/laporan',
                        icon: BarChart3,
                    },
                    {
                        title: 'Audit Log',
                        url: '/audit',
                        icon: FileClock,
                    },
                ],
            },
            {
                title: 'Akun',
                items: [
                    {
                        title: 'Pengaturan',
                        url: '/pengaturan',
                        icon: Settings,
                    },
                ],
            },
        ];
    }

    if (role === 'perawat' || role === 'dokter_operator' || role === 'dokter_anestesi') {
        return [
            {
                title: 'Menu Utama',
                items: [
                    {
                        title: 'Dashboard',
                        url: '/dashboard',
                        icon: LayoutGrid,
                    },
                    {
                        title: 'Kasus Operasi',
                        url: '/kasus',
                        icon: Activity,
                    },
                    {
                        title: 'Pasien',
                        url: '/pasien',
                        icon: UserRound,
                    },
                ],
            },
            {
                title: 'Akun',
                items: [
                    {
                        title: 'Pengaturan',
                        url: '/pengaturan',
                        icon: Settings,
                    },
                ],
            },
        ];
    }

    return [
        {
            title: 'Menu Utama',
            items: [
                {
                    title: 'Dashboard',
                    url: '/dashboard',
                    icon: LayoutGrid,
                },
            ],
        },
    ];
}
