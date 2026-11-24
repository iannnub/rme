<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreSurgeryTypeRequest;
use App\Http\Requests\Admin\UpdateSurgeryTypeRequest;
use App\Models\SurgeryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class SurgeryTypeController extends BaseMasterController
{
    protected function modelClass(): string
    {
        return SurgeryType::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.master.surgery-types';
    }

    protected function pageTitle(): string
    {
        return 'Master Jenis Operasi';
    }

    protected function pageDescription(): string
    {
        return 'Kelola daftar tindakan operasi standar di kamar operasi.';
    }

    protected function itemLabel(): string
    {
        return 'Jenis operasi';
    }

    protected function searchColumns(): array
    {
        return ['name', 'specialty'];
    }

    protected function orderByColumn(): string
    {
        return 'name';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Nama Jenis Operasi'],
            ['key' => 'specialty', 'label' => 'Spesialisasi'],
            ['key' => 'default_procedure_name', 'label' => 'Nama Tindakan Bawaan'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Nama Jenis Operasi', 'required' => true, 'placeholder' => 'contoh: Bedah Saraf', 'maxLength' => 255],
            ['name' => 'specialty', 'label' => 'Spesialisasi (Opsional)', 'required' => false, 'placeholder' => 'contoh: Bedah Saraf', 'maxLength' => 100],
            ['name' => 'default_procedure_name', 'label' => 'Nama Tindakan Bawaan (Opsional)', 'required' => false, 'placeholder' => 'contoh: Kraniotomi evakuasi hematoma', 'maxLength' => 255],
        ];
    }

    protected function transformItem(Model $item): array
    {
        /** @var SurgeryType $item */
        return [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'specialty' => $item->specialty,
            'default_procedure_name' => $item->default_procedure_name,
            'is_active' => $item->is_active,
        ];
    }

    public function store(StoreSurgeryTypeRequest $request): RedirectResponse
    {
        return $this->handleStore($request->validated());
    }

    public function update(UpdateSurgeryTypeRequest $request, SurgeryType $surgeryType): RedirectResponse
    {
        return $this->handleUpdate($surgeryType, $request->validated());
    }

    public function toggleActive(SurgeryType $surgeryType): RedirectResponse
    {
        return $this->handleToggleActive($surgeryType);
    }

    public function destroy(SurgeryType $surgeryType): RedirectResponse
    {
        return $this->handleDestroy($surgeryType);
    }
}
