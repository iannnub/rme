<?php

namespace App\Http\Requests\Admin;

class UpdateRoomRequest extends StoreRoomRequest
{
    protected function targetId(): mixed
    {
        return $this->route('room');
    }
}
