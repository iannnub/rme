<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreAnesthesiaTypeRequest;
use App\Http\Requests\Admin\UpdateAnesthesiaTypeRequest;
use App\Models\AnesthesiaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class AnesthesiaTypeController extends BaseMasterController
{
    protected function modelClass(): string
    {
        return AnesthesiaType::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.master.anesthesia-types';
    }

    protected function pageTitle(): string
    {
        return 'Master Jenis Anestesi';
    }

    protected function pageDescription(): string
    {
        return 'Kelola pilihan jenis anestesi yang digunakan selama operasi.';
    }

    protected function itemLabel(): string
    {
        return 'Jenis anestesi';
    }

    protected function searchColumns(): array
    {
        return ['code', 'name'];
    }

    protected function orderByColumn(): string
    {
        return 'code';
    }

    protected function columns(): array
    {
        return [
            ['key' => 'code', 'label' => 'Kode'],
            ['key' => 'name', 'label' => 'Nama Jenis Anestesi'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'code', 'label' => 'Kode Anestesi', 'required' => true, 'placeholder' => 'contoh: GA', 'maxLength' => 50],
            ['name' => 'name', 'label' => 'Nama Jenis Anestesi', 'required' => true, 'placeholder' => 'contoh: General Anesthesia (Umum)', 'maxLength' => 255],
        ];
    }

    protected function transformItem(Model $item): array
    {
        /** @var AnesthesiaType $item */
        return [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'is_active' => $item->is_active,
        ];
    }

    public function store(StoreAnesthesiaTypeRequest $request): RedirectResponse
    {
        return $this->handleStore($request->validated());
    }

    public function update(UpdateAnesthesiaTypeRequest $request, AnesthesiaType $anesthesiaType): RedirectResponse
    {
        return $this->handleUpdate($anesthesiaType, $request->validated());
    }

    public function toggleActive(AnesthesiaType $anesthesiaType): RedirectResponse
    {
        return $this->handleToggleActive($anesthesiaType);
    }

    public function destroy(AnesthesiaType $anesthesiaType): RedirectResponse
    {
        return $this->handleDestroy($anesthesiaType);
    }
}
