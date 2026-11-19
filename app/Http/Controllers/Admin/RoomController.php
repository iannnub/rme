<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreRoomRequest;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Room;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class RoomController extends BaseMasterController
{
    protected function modelClass(): string
    {
        return Room::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.master.rooms';
    }

    protected function pageTitle(): string
    {
        return 'Master Ruang Operasi';
    }

    protected function pageDescription(): string
    {
        return 'Kelola daftar kamar operasi (OK) yang tersedia di unit.';
    }

    protected function itemLabel(): string
    {
        return 'Ruang operasi';
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
            ['key' => 'code', 'label' => 'Kode Ruang'],
            ['key' => 'name', 'label' => 'Nama Ruang Operasi'],
        ];
    }

    protected function fields(): array
    {
        return [
            ['name' => 'code', 'label' => 'Kode Ruang', 'required' => true, 'placeholder' => 'contoh: OK 1', 'maxLength' => 50],
            ['name' => 'name', 'label' => 'Nama Ruang Operasi', 'required' => true, 'placeholder' => 'contoh: Kamar Operasi 1', 'maxLength' => 255],
        ];
    }

    protected function transformItem(Model $item): array
    {
        /** @var Room $item */
        return [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'is_active' => $item->is_active,
        ];
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        return $this->handleStore($request->validated());
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        return $this->handleUpdate($room, $request->validated());
    }

    public function toggleActive(Room $room): RedirectResponse
    {
        return $this->handleToggleActive($room);
    }

    public function destroy(Room $room): RedirectResponse
    {
        return $this->handleDestroy($room);
    }
}
