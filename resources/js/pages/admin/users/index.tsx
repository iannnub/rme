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
import { type BreadcrumbItem, type NurseType, type PaginatedData, type SharedData, type UserRole } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Power, Search, Users } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type AdminUserItem = {
    id: number;
    name: string;
    username: string;
    email: string | null;
    role: UserRole;
    role_label: string;
    nurse_type: NurseType | null;
    nurse_type_label: string | null;
    nip: string | null;
    sip: string | null;
    is_active: boolean;
    last_login_at: string | null;
};

type RoleOption = {
    value: UserRole;
    label: string;
};

type NurseTypeOption = {
    value: NurseType;
    label: string;
};

type UserFormData = {
    name: string;
    username: string;
    email: string;
    role: UserRole;
    nurse_type: string;
    nip: string;
    sip: string;
    password: string;
    password_confirmation: string;
};

type ResetPasswordFormData = {
    password: string;
    password_confirmation: string;
};

interface AdminUsersProps {
    users: PaginatedData<AdminUserItem>;
    filters: {
        q: string;
        role: string;
        status: string;
    };
    roles: RoleOption[];
    nurseTypes: NurseTypeOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Manajemen Pengguna', href: '/admin/pengguna' },
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

export default function AdminUsersIndex({ users, filters, roles, nurseTypes }: AdminUsersProps) {
    const { auth } = usePage<SharedData>().props;
    const currentUserId = auth.user?.id;

    const [search, setSearch] = useState(filters.q);
    const [roleFilter, setRoleFilter] = useState(filters.role || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');

    const [formOpen, setFormOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<AdminUserItem | null>(null);
    const [resetTarget, setResetTarget] = useState<AdminUserItem | null>(null);
    const [toggleTarget, setToggleTarget] = useState<AdminUserItem | null>(null);

    const userForm = useForm<UserFormData>({
        name: '',
        username: '',
        email: '',
        role: 'perawat',
        nurse_type: 'sirkuler',
        nip: '',
        sip: '',
        password: '',
        password_confirmation: '',
    });

    const resetForm = useForm<ResetPasswordFormData>({
        password: '',
        password_confirmation: '',
    });

    const isNurse = userForm.data.role === 'perawat';
    const isDoctor = userForm.data.role === 'dokter_operator' || userForm.data.role === 'dokter_anestesi';
    const requiresNip = userForm.data.role === 'perawat' || userForm.data.role === 'kepala_ruangan';

    const applyFilters = (nextQ: string, nextRole: string, nextStatus: string) => {
        router.get(
            route('admin.users.index'),
            {
                q: nextQ || undefined,
                role: nextRole !== 'all' ? nextRole : undefined,
                status: nextStatus !== 'all' ? nextStatus : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, roleFilter, statusFilter);
    };

    const openCreateModal = () => {
        setEditingUser(null);
        userForm.clearErrors();
        userForm.setData({
            name: '',
            username: '',
            email: '',
            role: 'perawat',
            nurse_type: 'sirkuler',
            nip: '',
            sip: '',
            password: '',
            password_confirmation: '',
        });
        setFormOpen(true);
    };

    const openEditModal = (user: AdminUserItem) => {
        setEditingUser(user);
        userForm.clearErrors();
        userForm.setData({
            name: user.name,
            username: user.username,
            email: user.email ?? '',
            role: user.role,
            nurse_type: user.nurse_type ?? '',
            nip: user.nip ?? '',
            sip: user.sip ?? '',
            password: '',
            password_confirmation: '',
        });
        setFormOpen(true);
    };

    const submitUserForm: FormEventHandler = (e) => {
        e.preventDefault();

        if (editingUser) {
            userForm.put(route('admin.users.update', editingUser.id), {
                preserveScroll: true,
                onSuccess: () => setFormOpen(false),
            });
            return;
        }

        userForm.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setFormOpen(false);
                userForm.reset();
            },
        });
    };

    const openResetModal = (user: AdminUserItem) => {
        setResetTarget(user);
        resetForm.clearErrors();
        resetForm.reset();
    };

    const submitResetPassword: FormEventHandler = (e) => {
        e.preventDefault();
        if (!resetTarget) {
            return;
        }

        resetForm.put(route('admin.users.reset-password', resetTarget.id), {
            preserveScroll: true,
            onSuccess: () => {
                setResetTarget(null);
                resetForm.reset();
            },
        });
    };

    const confirmToggleActive = () => {
        if (!toggleTarget) {
            return;
        }

        router.patch(
            route('admin.users.toggle-active', toggleTarget.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setToggleTarget(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen Pengguna" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-xl">
                                <Users className="text-primary h-5 w-5" />
                                Manajemen Pengguna
                            </CardTitle>
                            <CardDescription>
                                Kelola akun perawat, dokter, kepala ruangan, dan admin kamar operasi. Akun nonaktif tidak dapat masuk.
                            </CardDescription>
                        </div>
                        <Button type="button" onClick={openCreateModal}>
                            <Plus className="h-4 w-4" />
                            Tambah Pengguna
                        </Button>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <form onSubmit={handleSearchSubmit} className="grid gap-3 md:grid-cols-[1fr_220px_180px_auto]">
                            <div className="relative">
                                <Search className="text-muted-foreground pointer-events-none absolute top-3.5 left-3 h-4 w-4" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nama, username, email, NIP, atau SIP..."
                                    className="pl-9"
                                />
                            </div>

                            <Select
                                value={roleFilter}
                                onValueChange={(value) => {
                                    setRoleFilter(value);
                                    applyFilters(search, value, statusFilter);
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Peran" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Peran</SelectItem>
                                    {roles.map((role) => (
                                        <SelectItem key={role.value} value={role.value}>
                                            {role.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={statusFilter}
                                onValueChange={(value) => {
                                    setStatusFilter(value);
                                    applyFilters(search, roleFilter, value);
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="aktif">Aktif</SelectItem>
                                    <SelectItem value="nonaktif">Nonaktif</SelectItem>
                                </SelectContent>
                            </Select>

                            <div className="flex gap-2">
                                <Button type="submit" variant="secondary">
                                    Cari
                                </Button>
                                {(filters.q || filters.role || filters.status) && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setSearch('');
                                            setRoleFilter('all');
                                            setStatusFilter('all');
                                            applyFilters('', 'all', 'all');
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
                                        <TableHead>Petugas</TableHead>
                                        <TableHead>Peran</TableHead>
                                        <TableHead>NIP / SIP</TableHead>
                                        <TableHead>Login Terakhir</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground py-10 text-center">
                                                Tidak ada pengguna yang sesuai dengan pencarian atau filter.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        users.data.map((item) => {
                                            const isSelf = item.id === currentUserId;

                                            return (
                                                <TableRow key={item.id}>
                                                    <TableCell>
                                                        <div className="font-medium">{item.name}</div>
                                                        <div className="text-muted-foreground text-xs">
                                                            @{item.username}
                                                            {item.email ? ` • ${item.email}` : ''}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex flex-wrap gap-1.5">
                                                            <Badge variant="secondary">{item.role_label}</Badge>
                                                            {item.nurse_type_label && <Badge variant="outline">{item.nurse_type_label}</Badge>}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {item.sip ? `SIP: ${item.sip}` : item.nip ? `NIP: ${item.nip}` : '-'}
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground text-xs">
                                                        {item.last_login_at ?? 'Belum pernah'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={item.is_active ? 'default' : 'outline'}>
                                                            {item.is_active ? 'Aktif' : 'Nonaktif'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex justify-end gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                className="min-h-10"
                                                                onClick={() => openEditModal(item)}
                                                            >
                                                                <Pencil className="h-3.5 w-3.5" />
                                                                Edit
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                className="min-h-10"
                                                                onClick={() => openResetModal(item)}
                                                            >
                                                                <KeyRound className="h-3.5 w-3.5" />
                                                                Reset Sandi
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant={item.is_active ? 'destructive' : 'secondary'}
                                                                size="sm"
                                                                className="min-h-10"
                                                                disabled={isSelf}
                                                                title={isSelf ? 'Tidak dapat menonaktifkan akun sendiri' : undefined}
                                                                onClick={() => setToggleTarget(item)}
                                                            >
                                                                <Power className="h-3.5 w-3.5" />
                                                                {item.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {users.last_page > 1 && (
                            <div className="flex flex-col items-center justify-between gap-3 pt-2 sm:flex-row">
                                <p className="text-muted-foreground text-sm">
                                    Menampilkan {users.from ?? 0}–{users.to ?? 0} dari {users.total} pengguna
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                    {users.links.map((link, index) =>
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

            {/* Modal Tambah / Edit Pengguna */}
            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>{editingUser ? 'Edit Akun Pengguna' : 'Tambah Akun Pengguna'}</DialogTitle>
                        <DialogDescription>
                            Lengkapi identitas, peran, dan nomor registrasi petugas sesuai kewenangan kamar operasi.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitUserForm} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="user_name">Nama Lengkap & Gelar</Label>
                            <Input
                                id="user_name"
                                value={userForm.data.name}
                                onChange={(e) => userForm.setData('name', e.target.value)}
                                placeholder="contoh: Ns. Sari Rahmawati, S.Kep"
                                required
                            />
                            <InputError message={userForm.errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="user_username">Username</Label>
                                <Input
                                    id="user_username"
                                    value={userForm.data.username}
                                    onChange={(e) => userForm.setData('username', e.target.value)}
                                    placeholder="contoh: ns.sirkuler1"
                                    required
                                />
                                <InputError message={userForm.errors.username} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="user_email">Email (Opsional)</Label>
                                <Input
                                    id="user_email"
                                    type="email"
                                    value={userForm.data.email}
                                    onChange={(e) => userForm.setData('email', e.target.value)}
                                    placeholder="nama@rs.id"
                                />
                                <InputError message={userForm.errors.email} />
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="user_role">Peran</Label>
                                <Select
                                    value={userForm.data.role}
                                    onValueChange={(value) => {
                                        const nextRole = value as UserRole;
                                        userForm.setData((prev) => ({
                                            ...prev,
                                            role: nextRole,
                                            nurse_type: nextRole === 'perawat' ? prev.nurse_type || 'sirkuler' : '',
                                        }));
                                    }}
                                >
                                    <SelectTrigger id="user_role">
                                        <SelectValue placeholder="Pilih peran" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role.value} value={role.value}>
                                                {role.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={userForm.errors.role} />
                            </div>

                            {isNurse && (
                                <div className="grid gap-2">
                                    <Label htmlFor="user_nurse_type">Sub-Tipe Perawat (Wajib)</Label>
                                    <Select
                                        value={userForm.data.nurse_type || 'sirkuler'}
                                        onValueChange={(value) => userForm.setData('nurse_type', value)}
                                    >
                                        <SelectTrigger id="user_nurse_type">
                                            <SelectValue placeholder="Pilih sub-tipe" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {nurseTypes.map((type) => (
                                                <SelectItem key={type.value} value={type.value}>
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={userForm.errors.nurse_type} />
                                </div>
                            )}
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="user_nip">NIP {requiresNip ? '(Wajib)' : '(Opsional)'}</Label>
                                <Input
                                    id="user_nip"
                                    value={userForm.data.nip}
                                    onChange={(e) => userForm.setData('nip', e.target.value)}
                                    placeholder="Nomor Induk Pegawai"
                                    required={requiresNip}
                                />
                                <InputError message={userForm.errors.nip} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="user_sip">SIP {isDoctor ? '(Wajib)' : '(Opsional)'}</Label>
                                <Input
                                    id="user_sip"
                                    value={userForm.data.sip}
                                    onChange={(e) => userForm.setData('sip', e.target.value)}
                                    placeholder="Surat Izin Praktik"
                                    required={isDoctor}
                                />
                                <InputError message={userForm.errors.sip} />
                            </div>
                        </div>

                        {!editingUser && (
                            <div className="grid gap-4 border-t pt-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="user_password">Kata Sandi</Label>
                                    <Input
                                        id="user_password"
                                        type="password"
                                        value={userForm.data.password}
                                        onChange={(e) => userForm.setData('password', e.target.value)}
                                        placeholder="Min. 10 karakter huruf & angka"
                                        required
                                    />
                                    <InputError message={userForm.errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="user_password_confirmation">Konfirmasi Kata Sandi</Label>
                                    <Input
                                        id="user_password_confirmation"
                                        type="password"
                                        value={userForm.data.password_confirmation}
                                        onChange={(e) => userForm.setData('password_confirmation', e.target.value)}
                                        placeholder="Ulangi kata sandi"
                                        required
                                    />
                                    <InputError message={userForm.errors.password_confirmation} />
                                </div>
                            </div>
                        )}

                        <DialogFooter className="gap-2 pt-2">
                            <Button type="button" variant="outline" onClick={() => setFormOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={userForm.processing}>
                                {editingUser ? 'Simpan Perubahan' : 'Buat Akun'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal Reset Kata Sandi */}
            <Dialog open={Boolean(resetTarget)} onOpenChange={(open) => !open && setResetTarget(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Reset Kata Sandi</DialogTitle>
                        <DialogDescription>
                            Atur ulang kata sandi baru untuk <span className="text-foreground font-semibold">{resetTarget?.name}</span> (minimal 10
                            karakter kombinasi huruf dan angka).
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitResetPassword} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="reset_password">Kata Sandi Baru</Label>
                            <Input
                                id="reset_password"
                                type="password"
                                value={resetForm.data.password}
                                onChange={(e) => resetForm.setData('password', e.target.value)}
                                placeholder="Minimal 10 karakter huruf & angka"
                                required
                            />
                            <InputError message={resetForm.errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="reset_password_confirmation">Konfirmasi Kata Sandi Baru</Label>
                            <Input
                                id="reset_password_confirmation"
                                type="password"
                                value={resetForm.data.password_confirmation}
                                onChange={(e) => resetForm.setData('password_confirmation', e.target.value)}
                                placeholder="Ulangi kata sandi baru"
                                required
                            />
                            <InputError message={resetForm.errors.password_confirmation} />
                        </div>

                        <DialogFooter className="gap-2">
                            <Button type="button" variant="outline" onClick={() => setResetTarget(null)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={resetForm.processing}>
                                Simpan Kata Sandi
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Dialog Konfirmasi Aktif / Nonaktif */}
            <Dialog open={Boolean(toggleTarget)} onOpenChange={(open) => !open && setToggleTarget(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{toggleTarget?.is_active ? 'Nonaktifkan Akun Pengguna?' : 'Aktifkan Akun Pengguna?'}</DialogTitle>
                        <DialogDescription>
                            {toggleTarget?.is_active
                                ? `Akun ${toggleTarget?.name} (@${toggleTarget?.username}) tidak akan dapat masuk ke sistem, namun seluruh riwayat dokumentasi dan audit tetap tersimpan.`
                                : `Akun ${toggleTarget?.name} (@${toggleTarget?.username}) akan kembali diizinkan masuk ke sistem.`}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={() => setToggleTarget(null)}>
                            Batal
                        </Button>
                        <Button type="button" variant={toggleTarget?.is_active ? 'destructive' : 'default'} onClick={confirmToggleActive}>
                            {toggleTarget?.is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
