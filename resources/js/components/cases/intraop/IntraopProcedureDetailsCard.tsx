import { ConcurrencyAlert } from '@/components/concurrency-alert';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { Bone, Brain, Plus, Save, Trash2 } from 'lucide-react';
import { type FormEvent } from 'react';

export type IntraopProcedureDetails = {
    incision_location_side: string;
    craniotomy_location: string;
    fracture_location: string;
    bone_tissue_condition: string;
    hematoma_volume_condition: string;
    craniotomy_opening_process: string;
    hematoma_evacuation_process: string;
    hemostasis_action: string;
    brain_tissue_condition: string;
    bone_flap_management: string;
    reduction_type: string;
    reduction_process: string;
    fixation_method: string;
    implant_placement_notes: string;
    fluoroscopy_check: string;
    drain_placement: string;
    wound_closure: string;
    postop_wound_condition: string;
    blood_transfusion_given: boolean;
    blood_product_type_amount: string;
    cautery_usage: string;
    equipment_usage_notes: string;
};

export type IntraopImplantRow = {
    implant_type: string;
    size: string;
    planned_qty: string;
    brand: string;
    serial_lot: string;
    placement_site: string;
    used_qty: string;
    unused_doc: string;
};

interface IntraopProcedureDetailsCardProps {
    caseId: number;
    surgeryProfile: 'bedah_saraf' | 'bedah_ortopedi' | null;
    initialData: {
        updated_at: string | null;
        details: IntraopProcedureDetails;
        implants: IntraopImplantRow[];
    };
    canEdit: boolean;
}

const emptyImplantRow = (): IntraopImplantRow => ({
    implant_type: '',
    size: '',
    planned_qty: '1',
    brand: '',
    serial_lot: '',
    placement_site: '',
    used_qty: '1',
    unused_doc: '',
});

export function IntraopProcedureDetailsCard({ caseId, surgeryProfile, initialData, canEdit }: IntraopProcedureDetailsCardProps) {
    const isNeuro = surgeryProfile === 'bedah_saraf';

    const form = useForm({
        procedure_details: initialData.details,
        implants: initialData.implants.length > 0 ? initialData.implants : [],
    });

    const updateField = <K extends keyof IntraopProcedureDetails>(field: K, value: IntraopProcedureDetails[K]) => {
        form.setData('procedure_details', {
            ...form.data.procedure_details,
            [field]: value,
        });
    };

    const addImplantRow = () => {
        form.setData('implants', [...form.data.implants, emptyImplantRow()]);
    };

    const removeImplantRow = (index: number) => {
        form.setData(
            'implants',
            form.data.implants.filter((_, idx) => idx !== index),
        );
    };

    const updateImplantRow = (index: number, field: keyof IntraopImplantRow, value: string) => {
        const next = [...form.data.implants];
        next[index] = { ...next[index], [field]: value };
        form.setData('implants', next);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            expected_updated_at: initialData.updated_at ?? '',
            procedure_details: data.procedure_details,
            implants: data.implants,
        }));
        form.put(route('cases.intraop.procedure-details', caseId), {
            preserveScroll: true,
        });
    };

    const d = form.data.procedure_details;
    const concurrencyErr = (form.errors as Record<string, string | undefined>).concurrency;

    return (
        <Card className="border-primary/25 border">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    {isNeuro ? <Brain className="text-primary h-5 w-5" /> : <Bone className="text-primary h-5 w-5" />}
                    {isNeuro
                        ? '8B. Detail Tindakan Kraniotomi & Evakuasi EDH (Bedah Saraf)'
                        : '8B. Detail Tindakan ORIF, Fiksasi & Implan (Bedah Ortopedi)'}
                </CardTitle>
                <CardDescription>
                    {isNeuro
                        ? 'Dokumentasikan lokasi kraniotomi, evakuasi hematoma, hemostasis, kondisi parenkim otak, dan manajemen bone flap.'
                        : 'Dokumentasikan proses reduksi fraktur femur, fiksasi internal, evaluasi C-Arm/fluoroskopi, serta nomor seri implan yang terpasang.'}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <ConcurrencyAlert message={concurrencyErr} />

                    {isNeuro ? (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_incision">Lokasi &amp; Sisi Insisi</Label>
                                <Input
                                    id="proc_incision"
                                    value={d.incision_location_side}
                                    onChange={(e) => updateField('incision_location_side', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Insisi trauma flap frontotemporoparietal kanan"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_craniotomy_loc">Lokasi Kraniotomi</Label>
                                <Input
                                    id="proc_craniotomy_loc"
                                    value={d.craniotomy_location}
                                    onChange={(e) => updateField('craniotomy_location', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Regio temporoparietal dekstra"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_hematoma_vol">Volume &amp; Kondisi Hematoma</Label>
                                <Input
                                    id="proc_hematoma_vol"
                                    value={d.hematoma_volume_condition}
                                    onChange={(e) => updateField('hematoma_volume_condition', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Bekuan darah epidural +/- 45 mL terorganisir"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_opening_proc">Proses Pembukaan Tulang Tengkorak</Label>
                                <Input
                                    id="proc_opening_proc"
                                    value={d.craniotomy_opening_process}
                                    onChange={(e) => updateField('craniotomy_opening_process', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Burr hole 4 titik, dihubungkan craniotome"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_evacuation">Proses Pengangkatan / Evakuasi Hematoma</Label>
                                <Input
                                    id="proc_evacuation"
                                    value={d.hematoma_evacuation_process}
                                    onChange={(e) => updateField('hematoma_evacuation_process', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Evakuasi bekuan bertahap dengan suction & irigasi NaCl 0.9%"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_hemostasis">Tindakan Penghentian Perdarahan (Hemostasis)</Label>
                                <Input
                                    id="proc_hemostasis"
                                    value={d.hemostasis_action}
                                    onChange={(e) => updateField('hemostasis_action', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Koagulasi a. meningea media dgn bipolar, bone wax, Spongostan"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_brain_cond">Kondisi Jaringan Otak setelah Evakuasi</Label>
                                <Input
                                    id="proc_brain_cond"
                                    value={d.brain_tissue_condition}
                                    onChange={(e) => updateField('brain_tissue_condition', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Dura utuh, pulsasi baik, tidak tampak edema masif"
                                />
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="proc_bone_flap">Pengelolaan Potongan Tulang Tengkorak (Bone Flap)</Label>
                                <Input
                                    id="proc_bone_flap"
                                    value={d.bone_flap_management}
                                    onChange={(e) => updateField('bone_flap_management', e.target.value)}
                                    disabled={!canEdit}
                                    placeholder="Bone flap dikembalikan & difiksasi miniplate/silk"
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_ortho_incision">Lokasi &amp; Sisi Insisi</Label>
                                    <Input
                                        id="proc_ortho_incision"
                                        value={d.incision_location_side}
                                        onChange={(e) => updateField('incision_location_side', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="Insisi lateral femur dekstra +/- 18 cm"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_fracture_loc">Lokasi Fraktur</Label>
                                    <Input
                                        id="proc_fracture_loc"
                                        value={d.fracture_location}
                                        onChange={(e) => updateField('fracture_location', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="Diafisis 1/3 tengah femur kanan"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_bone_cond">Kondisi Tulang &amp; Jaringan Sekitar</Label>
                                    <Input
                                        id="proc_bone_cond"
                                        value={d.bone_tissue_condition}
                                        onChange={(e) => updateField('bone_tissue_condition', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="Fragmen transversal, periosteum robek lokal, otot baik"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_reduction_type">Jenis Reduksi (Terbuka / Tertutup)</Label>
                                    <Input
                                        id="proc_reduction_type"
                                        value={d.reduction_type}
                                        onChange={(e) => updateField('reduction_type', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="Open Reduction (ORIF)"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_reduction_proc">Proses Reduksi Tulang</Label>
                                    <Input
                                        id="proc_reduction_proc"
                                        value={d.reduction_process}
                                        onChange={(e) => updateField('reduction_process', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="Pembersihan kalus/hematoma, reposisi anatomis dgn bone clamp"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_fixation">Metode Fiksasi Tulang</Label>
                                    <Input
                                        id="proc_fixation"
                                        value={d.fixation_method}
                                        onChange={(e) => updateField('fixation_method', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="Broad LCP / DCP Plate & cortical/locking screw"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_implant_notes">Catatan Pemasangan Plate, Screw / Nail</Label>
                                    <Input
                                        id="proc_implant_notes"
                                        value={d.implant_placement_notes}
                                        onChange={(e) => updateField('implant_placement_notes', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="Terpasang rigid, 4 screw proksimal & 4 screw distal"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="proc_fluoro">Pemeriksaan Fluoroskopi / C-Arm selama Operasi</Label>
                                    <Input
                                        id="proc_fluoro"
                                        value={d.fluoroscopy_check}
                                        onChange={(e) => updateField('fluoroscopy_check', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="AP & Lateral baik, posisi plate & panjang screw tepat"
                                    />
                                </div>
                            </div>

                            <div className="space-y-3 rounded-xl border p-4">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <h4 className="text-foreground text-sm font-semibold">Data Implan Ortopedi Terpasang</h4>
                                        <p className="text-muted-foreground text-xs">
                                            Catat jenis, ukuran, merk, nomor seri/lot, dan jumlah implan yang digunakan atau dikembalikan.
                                        </p>
                                    </div>
                                    {canEdit && (
                                        <Button type="button" variant="outline" size="sm" className="min-h-10" onClick={addImplantRow}>
                                            <Plus className="mr-1.5 h-4 w-4" />
                                            Tambah Implan
                                        </Button>
                                    )}
                                </div>

                                {form.data.implants.length === 0 ? (
                                    <p className="text-muted-foreground py-3 text-center text-xs">
                                        Belum ada data implan ditambahkan. Klik Tambah Implan untuk mencatat implan terpasang.
                                    </p>
                                ) : (
                                    <div className="space-y-3">
                                        {form.data.implants.map((imp, idx) => (
                                            <div key={idx} className="bg-muted/30 space-y-3 rounded-lg border p-3">
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="text-xs font-semibold">Implan #{idx + 1}</span>
                                                    {canEdit && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive hover:text-destructive min-h-11"
                                                            onClick={() => removeImplantRow(idx)}
                                                        >
                                                            <Trash2 className="mr-1 h-3.5 w-3.5" />
                                                            Hapus
                                                        </Button>
                                                    )}
                                                </div>
                                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_type_${idx}`}>
                                                            Jenis Implan
                                                        </Label>
                                                        <Input
                                                            id={`imp_type_${idx}`}
                                                            value={imp.implant_type}
                                                            onChange={(e) => updateImplantRow(idx, 'implant_type', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="Broad LCP / Cortical Screw"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_size_${idx}`}>
                                                            Ukuran
                                                        </Label>
                                                        <Input
                                                            id={`imp_size_${idx}`}
                                                            value={imp.size}
                                                            onChange={(e) => updateImplantRow(idx, 'size', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="10 Hole / 4.5 x 38 mm"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_brand_${idx}`}>
                                                            Merk / Pabrikan
                                                        </Label>
                                                        <Input
                                                            id={`imp_brand_${idx}`}
                                                            value={imp.brand}
                                                            onChange={(e) => updateImplantRow(idx, 'brand', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="contoh: Synthes / Stryker"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_serial_${idx}`}>
                                                            Nomor Seri / Lot
                                                        </Label>
                                                        <Input
                                                            id={`imp_serial_${idx}`}
                                                            value={imp.serial_lot}
                                                            onChange={(e) => updateImplantRow(idx, 'serial_lot', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="LOT-2026-8841"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_site_${idx}`}>
                                                            Lokasi Pemasangan
                                                        </Label>
                                                        <Input
                                                            id={`imp_site_${idx}`}
                                                            value={imp.placement_site}
                                                            onChange={(e) => updateImplantRow(idx, 'placement_site', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="Lateral femur dekstra"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_planned_${idx}`}>
                                                            Jumlah Disiapkan
                                                        </Label>
                                                        <Input
                                                            id={`imp_planned_${idx}`}
                                                            value={imp.planned_qty}
                                                            onChange={(e) => updateImplantRow(idx, 'planned_qty', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="1"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_used_${idx}`}>
                                                            Jumlah Terpakai
                                                        </Label>
                                                        <Input
                                                            id={`imp_used_${idx}`}
                                                            value={imp.used_qty}
                                                            onChange={(e) => updateImplantRow(idx, 'used_qty', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="1"
                                                        />
                                                    </div>
                                                    <div className="space-y-1">
                                                        <Label className="text-xs" htmlFor={`imp_unused_${idx}`}>
                                                            Implan Tidak Terpakai
                                                        </Label>
                                                        <Input
                                                            id={`imp_unused_${idx}`}
                                                            value={imp.unused_doc}
                                                            onChange={(e) => updateImplantRow(idx, 'unused_doc', e.target.value)}
                                                            disabled={!canEdit}
                                                            placeholder="Dikembalikan ke CSSD"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Kolom Bersama Penutupan Luka, Drain, Transfusi, Kauter & Alat */}
                    <div className="grid grid-cols-1 gap-4 border-t pt-4 md:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="proc_drain">Pemasangan Drain</Label>
                            <Input
                                id="proc_drain"
                                value={d.drain_placement}
                                onChange={(e) => updateField('drain_placement', e.target.value)}
                                disabled={!canEdit}
                                placeholder="Subgaleal/Redon drain vakum 1 buah"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="proc_closure">Penutupan Luka</Label>
                            <Input
                                id="proc_closure"
                                value={d.wound_closure}
                                onChange={(e) => updateField('wound_closure', e.target.value)}
                                disabled={!canEdit}
                                placeholder="Jahit lapis demi lapis dgn Vicryl & Nylon"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="proc_wound_cond">Kondisi Luka setelah Operasi</Label>
                            <Input
                                id="proc_wound_cond"
                                value={d.postop_wound_condition}
                                onChange={(e) => updateField('postop_wound_condition', e.target.value)}
                                disabled={!canEdit}
                                placeholder="Tertutup rapat, kasa kering, tidak rembes"
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="proc_cautery">Penggunaan Mesin Cautery / Monopolar-Bipolar</Label>
                            <Input
                                id="proc_cautery"
                                value={d.cautery_usage}
                                onChange={(e) => updateField('cautery_usage', e.target.value)}
                                disabled={!canEdit}
                                placeholder="Bipolar 25 W / Monopolar 35 W, grounding utuh"
                            />
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <label className="flex cursor-pointer items-center gap-2.5 text-sm font-medium">
                                <Checkbox
                                    checked={d.blood_transfusion_given}
                                    disabled={!canEdit}
                                    onCheckedChange={(c) => updateField('blood_transfusion_given', c === true)}
                                />
                                <span>Diberikan Transfusi Darah Intra-Operasi</span>
                            </label>
                            {d.blood_transfusion_given && (
                                <div className="space-y-1.5 pt-1">
                                    <Label htmlFor="proc_blood_amount">Jenis Produk &amp; Jumlah Transfusi Darah</Label>
                                    <Input
                                        id="proc_blood_amount"
                                        value={d.blood_product_type_amount}
                                        onChange={(e) => updateField('blood_product_type_amount', e.target.value)}
                                        disabled={!canEdit}
                                        placeholder="contoh: PRC 1 kolf (250 mL)"
                                    />
                                    <InputError
                                        message={(form.errors as Record<string, string | undefined>)['procedure_details.blood_product_type_amount']}
                                    />
                                </div>
                            )}
                        </div>
                        <div className="space-y-1.5 md:col-span-2">
                            <Label htmlFor="proc_equipment_notes">Catatan Penggunaan Alat Khusus &amp; BHP Selama Tindakan</Label>
                            <Input
                                id="proc_equipment_notes"
                                value={d.equipment_usage_notes}
                                onChange={(e) => updateField('equipment_usage_notes', e.target.value)}
                                disabled={!canEdit}
                                placeholder="Semua instrumen, bor/gergaji, lampu, dan C-Arm berfungsi baik"
                            />
                        </div>
                    </div>

                    {canEdit && (
                        <div className="flex justify-end">
                            <Button type="submit" className="min-h-11" disabled={form.processing}>
                                <Save className="mr-2 h-4 w-4" />
                                {form.processing ? 'Menyimpan...' : 'Simpan Detail Tindakan & Implan'}
                            </Button>
                        </div>
                    )}
                </form>
            </CardContent>
        </Card>
    );
}
