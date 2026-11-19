<?php

namespace App\Http\Requests\Admin;

class UpdateInstrumentRequest extends StoreInstrumentRequest
{
    protected function targetId(): mixed
    {
        return $this->route('instrument');
    }
}
