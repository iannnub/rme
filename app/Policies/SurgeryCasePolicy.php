<?php

namespace App\Policies;

use App\Enums\CaseStatus;
use App\Models\SurgeryCase;
use App\Models\User;

class SurgeryCasePolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function view(User $user, SurgeryCase $surgeryCase): bool
    {
        return ! $user->isAdmin();
    }

    public function viewHistory(User $user, SurgeryCase $surgeryCase): bool
    {
        return ! $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isPerawat();
    }

    public function update(User $user, SurgeryCase $surgeryCase): bool
    {
        return $user->isPerawat() && $surgeryCase->status !== CaseStatus::Selesai;
    }

    public function advanceStatus(User $user, SurgeryCase $surgeryCase): bool
    {
        return $user->isPerawat() && $surgeryCase->status !== CaseStatus::Selesai;
    }

    public function managePreparation(User $user, SurgeryCase $surgeryCase): bool
    {
        return $user->isPerawat() && $surgeryCase->status !== CaseStatus::Selesai;
    }

    public function updateSignIn(User $user, SurgeryCase $surgeryCase): bool
    {
        if ($surgeryCase->status === CaseStatus::Selesai) {
            return false;
        }

        if ($user->isPerawat()) {
            return true;
        }

        return $user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id;
    }

    public function verifySignIn(User $user, SurgeryCase $surgeryCase): bool
    {
        return $surgeryCase->status !== CaseStatus::Selesai
            && $user->isDokterAnestesi()
            && $surgeryCase->anesthesiologist_id === $user->id;
    }

    public function updateTimeOut(User $user, SurgeryCase $surgeryCase): bool
    {
        if ($surgeryCase->status === CaseStatus::Selesai) {
            return false;
        }

        if ($user->isPerawat()) {
            return true;
        }

        if ($user->isDokterOperator() && $surgeryCase->surgeon_id === $user->id) {
            return true;
        }

        return $user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id;
    }

    public function updateSignOut(User $user, SurgeryCase $surgeryCase): bool
    {
        return $user->isPerawat() && $surgeryCase->status !== CaseStatus::Selesai;
    }

    public function verifySignOut(User $user, SurgeryCase $surgeryCase): bool
    {
        if ($surgeryCase->status === CaseStatus::Selesai) {
            return false;
        }

        return ($user->isDokterOperator() && $surgeryCase->surgeon_id === $user->id)
            || ($user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id);
    }

    public function updateIntraop(User $user, SurgeryCase $surgeryCase): bool
    {
        return $user->isPerawat() && $surgeryCase->status !== CaseStatus::Selesai;
    }

    public function updateIntraopAnesthesia(User $user, SurgeryCase $surgeryCase): bool
    {
        if ($surgeryCase->status === CaseStatus::Selesai) {
            return false;
        }

        if ($user->isPerawat()) {
            return true;
        }

        return $user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id;
    }

    public function updateIntraopComplication(User $user, SurgeryCase $surgeryCase): bool
    {
        if ($surgeryCase->status === CaseStatus::Selesai) {
            return false;
        }

        if ($user->isPerawat()) {
            return true;
        }

        return ($user->isDokterOperator() && $surgeryCase->surgeon_id === $user->id)
            || ($user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id);
    }

    public function signIntraop(User $user, SurgeryCase $surgeryCase): bool
    {
        if ($surgeryCase->status === CaseStatus::Selesai) {
            return false;
        }

        return $surgeryCase->circulating_nurse_id === $user->id
            || $surgeryCase->scrub_nurse_id === $user->id
            || $surgeryCase->anesthesiologist_id === $user->id
            || $surgeryCase->surgeon_id === $user->id;
    }

    public function updatePostop(User $user, SurgeryCase $surgeryCase): bool
    {
        return $user->isPerawat() && $surgeryCase->status !== CaseStatus::Selesai;
    }

    public function verifyPostop(User $user, SurgeryCase $surgeryCase): bool
    {
        if ($surgeryCase->status === CaseStatus::Selesai) {
            return false;
        }

        return ($user->isDokterOperator() && $surgeryCase->surgeon_id === $user->id)
            || ($user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id);
    }

    public function correctMedical(User $user, SurgeryCase $surgeryCase): bool
    {
        return ($user->isDokterOperator() && $surgeryCase->surgeon_id === $user->id)
            || ($user->isDokterAnestesi() && $surgeryCase->anesthesiologist_id === $user->id);
    }

    public function updateHandover(User $user, SurgeryCase $surgeryCase): bool
    {
        return $user->isPerawat() && $surgeryCase->status !== CaseStatus::Selesai;
    }

    public function delete(User $user, SurgeryCase $surgeryCase): bool
    {
        return false;
    }
}
