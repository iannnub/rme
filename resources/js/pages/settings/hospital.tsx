import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type HospitalProfile = {
    hospital_name: string;
    hospital_address: string;
    hospital_phone: string;
    hospital_email: string;
};

interface HospitalSettingProps {
    hospital: HospitalProfile;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Rumah Sakit',
        href: '/pengaturan/rumah-sakit',
    },
];

export default function HospitalSettings({ hospital }: HospitalSettingProps) {
    const { data, setData, put, errors, processing, recentlySuccessful } = useForm<HospitalProfile>({
        hospital_name: hospital.hospital_name ?? '',
        hospital_address: hospital.hospital_address ?? '',
        hospital_phone: hospital.hospital_phone ?? '',
        hospital_email: hospital.hospital_email ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('hospital-settings.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Rumah Sakit" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Identitas Rumah Sakit"
                        description="Informasi ini ditampilkan pada kop dokumen ekspor PDF rekam keperawatan dan lembar serah terima."
                    />

                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="hospital_name">Nama Rumah Sakit</Label>
                            <Input
                                id="hospital_name"
                                value={data.hospital_name}
                                onChange={(e) => setData('hospital_name', e.target.value)}
                                placeholder="contoh: RS Simulasi RME OKA"
                                required
                            />
                            <InputError message={errors.hospital_name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="hospital_address">Alamat Rumah Sakit</Label>
                            <Input
                                id="hospital_address"
                                value={data.hospital_address}
                                onChange={(e) => setData('hospital_address', e.target.value)}
                                placeholder="Jl. Kesehatan No. 1"
                                required
                            />
                            <InputError message={errors.hospital_address} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="hospital_phone">Nomor Telepon</Label>
                                <Input
                                    id="hospital_phone"
                                    value={data.hospital_phone}
                                    onChange={(e) => setData('hospital_phone', e.target.value)}
                                    placeholder="(0331) 500123"
                                    required
                                />
                                <InputError message={errors.hospital_phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="hospital_email">Email Rumah Sakit (Opsional)</Label>
                                <Input
                                    id="hospital_email"
                                    type="email"
                                    value={data.hospital_email}
                                    onChange={(e) => setData('hospital_email', e.target.value)}
                                    placeholder="oka@rumahsakit.id"
                                />
                                <InputError message={errors.hospital_email} />
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Simpan Data Rumah Sakit</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-muted-foreground text-sm">Tersimpan</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
