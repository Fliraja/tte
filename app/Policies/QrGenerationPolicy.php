<?php

namespace App\Policies;

use App\Models\QrGeneration;
use App\Models\User;

class QrGenerationPolicy
{
    /**
     * Determine whether the user can view the QR generation details and PNG.
     */
    public function view(User $user, QrGeneration $generation): bool
    {
        return $user->role === 'admin' || $generation->generated_by === $user->id;
    }

    /**
     * Determine whether the user can void the QR generation.
     * Rule K5: Creator can void while status is 'issued'; Admin can void anytime.
     * Cannot void if already void.
     */
    public function void(User $user, QrGeneration $generation): bool
    {
        if ($generation->isVoid()) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $generation->generated_by === $user->id && $generation->isIssued();
    }
}

