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
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Database, Pencil, Plus, Power, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type MasterColumn = {
    key: string;
    label: string;
};

type MasterField = {
    name: string;
    label: string;
    required: boolean;
    placeholder: string;
    maxLength: number;
};

type MasterItem = {
    id: number;
    is_active: boolean;
    [key: string]: string | number | boolean | null;
};

interface MasterPageProps {
    title: string;
    description: string;
    itemLabel: string;
    routePrefix: string;
    columns: MasterColumn[];
    fields: MasterField[];
    items: PaginatedData<MasterItem>;
    filters: {
        q: string;
        status: string;
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

export default function AdminMasterIndex({ title, description, itemLabel, routePrefix, columns, fields, items, filters }: MasterPageProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title, href: route(`${routePrefix}.index`) },
    ];

    const buildInitialData = (source?: MasterItem | null): Record<string, string> => {
        const record: Record<string, string> = {};
        fields.forEach((field) => {
            const value = source?.[field.name];
            record[field.name] = typeof value === 'string' ? value : typeof value === 'number' ? String(value) : '';
        });
        return record;
    };

    const [search, setSearch] = useState(filters.q);
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [formOpen, setFormOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<MasterItem | null>(null);
    const [toggleTarget, setToggleTarget] = useState<MasterItem | null>(null);

    const form = useForm<Record<string, string>>(buildInitialData());

    const applyFilters = (nextQ: string, nextStatus: string) => {
        router.get(
            route(`${routePrefix}.index`),
            {
                q: nextQ || undefined,
                status: nextStatus !== 'all' ? nextStatus : undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, statusFilter);
    };

    const openCreateModal = () => {
        setEditingItem(null);
        form.clearErrors();
        form.setData(buildInitialData(null));
        setFormOpen(true);
    };

    const openEditModal = (item: MasterItem) => {
        setEditingItem(item);
        form.clearErrors();
        form.setData(buildInitialData(item));
        setFormOpen(true);
    };

    const submitForm: FormEventHandler = (e) => {
        e.preventDefault();

        if (editingItem) {
            form.put(route(`${routePrefix}.update`, editingItem.id), {
                preserveScroll: true,
                onSuccess: () => setFormOpen(false),
            });
            return;
        }

        form.post(route(`${routePrefix}.store`), {
            preserveScroll: true,
            onSuccess: () => {
                setFormOpen(false);
                form.setData(buildInitialData(null));
            },
        });
    };

    const confirmToggleActive = () => {
        if (!toggleTarget) {
            return;
        }

        router.patch(
            route(`${routePrefix}.toggle-active`, toggleTarget.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => setToggleTarget(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Card>
                    <CardHeader className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-xl">
                                <Database className="text-primary h-5 w-5" />
                                {title}
                            </CardTitle>
                            <CardDescription>{description}</CardDescription>
                        </div>
                        <Button type="button" onClick={openCreateModal}>
                            <Plus className="h-4 w-4" />
                            Tambah {itemLabel}
                        </Button>
                    </CardHeader>

                    <CardContent className="space-y-4">
                        <form onSubmit={handleSearchSubmit} className="grid gap-3 md:grid-cols-[1fr_200px_auto]">
                            <div className="relative">
                                <Search className="text-muted-foreground pointer-events-none absolute top-3.5 left-3 h-4 w-4" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder={`Cari ${itemLabel.toLowerCase()}...`}
                                    className="pl-9"
                                />
                            </div>

                            <Select
                                value={statusFilter}
                                onValueChange={(value) => {
                                    setStatusFilter(value);
                                    applyFilters(search, value);
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
                                {(filters.q || filters.status) && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setSearch('');
                                            setStatusFilter('all');
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
                                        {columns.map((column) => (
                                            <TableHead key={column.key}>{column.label}</TableHead>
                                        ))}
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {items.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={columns.length + 2} className="text-muted-foreground py-10 text-center">
                                                Belum ada data {itemLabel.toLowerCase()} yang sesuai.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        items.data.map((item) => (
                                            <TableRow key={item.id}>
                                                {columns.map((column, colIdx) => (
                                                    <TableCell key={column.key} className={colIdx === 0 ? 'font-medium' : ''}>
                                                        {item[column.key] ? String(item[column.key]) : '-'}
                                                    </TableCell>
                                                ))}
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
                                                            variant={item.is_active ? 'destructive' : 'secondary'}
                                                            size="sm"
                                                            className="min-h-10"
                                                            onClick={() => setToggleTarget(item)}
                                                        >
                                                            <Power className="h-3.5 w-3.5" />
                                                            {item.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {items.last_page > 1 && (
                            <div className="flex flex-col items-center justify-between gap-3 pt-2 sm:flex-row">
                                <p className="text-muted-foreground text-sm">
                                    Menampilkan {items.from ?? 0}–{items.to ?? 0} dari {items.total} data
                                </p>
                                <div className="flex flex-wrap gap-1.5">
                                    {items.links.map((link, index) =>
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

            {/* Modal Tambah / Edit Master */}
            <Dialog open={formOpen} onOpenChange={setFormOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{editingItem ? `Edit ${itemLabel}` : `Tambah ${itemLabel}`}</DialogTitle>
                        <DialogDescription>
                            Data master yang dinonaktifkan tidak dapat dipilih pada kasus baru namun riwayat kasus lama tetap utuh.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitForm} className="space-y-4">
                        {fields.map((field) => (
                            <div key={field.name} className="grid gap-2">
                                <Label htmlFor={`master_${field.name}`}>{field.label}</Label>
                                <Input
                                    id={`master_${field.name}`}
                                    value={form.data[field.name] ?? ''}
                                    onChange={(e) => form.setData(field.name, e.target.value)}
                                    placeholder={field.placeholder}
                                    maxLength={field.maxLength}
                                    required={field.required}
                                />
                                <InputError message={form.errors[field.name]} />
                            </div>
                        ))}

                        <DialogFooter className="gap-2 pt-2">
                            <Button type="button" variant="outline" onClick={() => setFormOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={form.processing}>
                                {editingItem ? 'Simpan Perubahan' : 'Simpan'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Dialog Konfirmasi Aktif / Nonaktif */}
            <Dialog open={Boolean(toggleTarget)} onOpenChange={(open) => !open && setToggleTarget(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{toggleTarget?.is_active ? `Nonaktifkan ${itemLabel}?` : `Aktifkan ${itemLabel}?`}</DialogTitle>
                        <DialogDescription>
                            {toggleTarget?.is_active
                                ? `${itemLabel} "${String(toggleTarget?.name ?? '')}" tidak akan muncul pada pilihan kasus baru, tetapi data historis tetap aman.`
                                : `${itemLabel} "${String(toggleTarget?.name ?? '')}" akan diaktifkan kembali.`}
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
