<?php

namespace App\Http\Requests\Admin;

class UpdateAnesthesiaTypeRequest extends StoreAnesthesiaTypeRequest
{
    protected function targetId(): mixed
    {
        return $this->route('anesthesiaType');
    }
}
