<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreInstrumentRequest;
use App\Http\Requests\Admin\UpdateInstrumentRequest;
use App\Models\Instrument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class InstrumentController extends BaseMasterController
{
    protected function modelClass(): string
    {
        return Instrument::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.master.instruments';
    }

    protected function pageTitle(): string
    {
        return 'Master Alat & Instrumen';
    }

    protected function pageDescription(): string
    {
        return 'Kelola daftar set instrumen bedah dan peralatan kamar operasi.';
    }

    protected function itemLabel(): string
    {
        return 'Alat';
    }

    protected function searchColumns(): array
    {
        return ['name', 'category'];
    }

    protected function orderByColumn(): string
    {
        return 'name';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nama Alat / Instrumen'],
            ['key' => 'category', 'label' => 'Kategori'],
            ['key' => 'surgery_profile_label', 'label' => 'Jenis Operasi'],
            ['key' => 'description', 'label' => 'Keterangan / Fungsi'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama Alat / Instrumen', 'required' => true, 'placeholder' => 'contoh: Set instrumen kraniotomi', 'maxLength' => 255],
            ['name' => 'category', 'label' => 'Kategori (Opsional)', 'required' => false, 'placeholder' => 'contoh: Alat & Instrumen Bedah Saraf', 'maxLength' => 100],
            ['name' => 'surgery_profile', 'label' => 'Profil Jenis Operasi (semua / bedah_saraf / bedah_ortopedi)', 'required' => false, 'placeholder' => 'semua', 'maxLength' => 30],
            ['name' => 'description', 'label' => 'Keterangan / Fungsi (Opsional)', 'required' => false, 'placeholder' => 'contoh: Mengangkat periosteum', 'maxLength' => 255],
        ];
    }

    protected function transformItem(Model $item): array
    {
        /** @var Instrument $item */
        $profileLabel = match ($item->surgery_profile) {
            'bedah_saraf' => 'Bedah Saraf (EDH)',
            'bedah_ortopedi' => 'Bedah Ortopedi (Fraktur Femur)',
            default => 'Semua (Saraf & Ortopedi)',
        };

        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
            'surgery_profile' => $item->surgery_profile,
            'surgery_profile_label' => $profileLabel,
            'description' => $item->description,
            'is_active' => $item->is_active,
        ];
    }

    public function store(StoreInstrumentRequest $request): RedirectResponse
    {
        return $this->handleStore($request->validated());
    }

    public function update(UpdateInstrumentRequest $request, Instrument $instrument): RedirectResponse
    {
        return $this->handleUpdate($instrument, $request->validated());
    }

    public function toggleActive(Instrument $instrument): RedirectResponse
    {
        return $this->handleToggleActive($instrument);
    }

    public function destroy(Instrument $instrument): RedirectResponse
    {
        return $this->handleDestroy($instrument);
    }
}
