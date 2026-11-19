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
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama Alat / Instrumen', 'required' => true, 'placeholder' => 'contoh: Set Dasar Bedah', 'maxLength' => 255],
            ['name' => 'category', 'label' => 'Kategori (Opsional)', 'required' => false, 'placeholder' => 'contoh: Instrumen Bedah', 'maxLength' => 100],
        ];
    }

    protected function transformItem(Model $item): array
    {
        /** @var Instrument $item */
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category,
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
