<?php

namespace App\Enums;

enum SignoffSection: string
{
    case PreOp = 'preop';
    case IntraOp = 'intraop';
    case PostOp = 'postop';
    case SignIn = 'sign_in';
    case TimeOut = 'time_out';
    case SignOut = 'sign_out';
    case Handover = 'handover';

    public function label(): string
    {
        return __('enums.signoff_section.'.$this->value);
    }
}
