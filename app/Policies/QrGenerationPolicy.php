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

    /**
     * Determine whether the user can upload a final document.
     * Creator or admin can upload while status is 'issued' and not void.
     */
    public function uploadDocument(User $user, QrGeneration $generation): bool
    {
        if ($generation->isVoid() || $generation->status === 'final') {
            return false;
        }

        return $user->role === 'admin' || $generation->generated_by === $user->id;
    }

    /**
     * Determine whether the user can replace an existing final document.
     * Admin only, when status is 'final' and not void.
     */
    public function replaceDocument(User $user, QrGeneration $generation): bool
    {
        if ($generation->isVoid() || $generation->status !== 'final') {
            return false;
        }

        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view/stream the stored private document.
     */
    public function viewDocument(User $user, QrGeneration $generation): bool
    {
        return $this->view($user, $generation);
    }
}

