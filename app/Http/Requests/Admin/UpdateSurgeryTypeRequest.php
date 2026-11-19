<?php

namespace App\Http\Requests\Admin;

class UpdateSurgeryTypeRequest extends StoreSurgeryTypeRequest
{
    protected function targetId(): mixed
    {
        return $this->route('surgeryType');
    }
}
