<?php

namespace App\Policies;

use App\Models\AuditActivity;
use App\Models\User;

class AuditActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isKepalaRuangan();
    }

    public function view(User $user, AuditActivity $auditActivity): bool
    {
        return $user->isKepalaRuangan();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditActivity $auditActivity): bool
    {
        return false;
    }

    public function delete(User $user, AuditActivity $auditActivity): bool
    {
        return false;
    }
}
