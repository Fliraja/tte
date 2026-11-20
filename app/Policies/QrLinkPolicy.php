<?php

namespace App\Policies;

use App\Models\QrLink;
use App\Models\User;

class QrLinkPolicy
{
    /**
     * Lihat detail dan unduh PNG: pemilik atau admin (spec §7).
     */
    public function view(User $user, QrLink $link): bool
    {
        return $this->ownsOrAdmin($user, $link);
    }

    /**
     * Ganti tujuan / keterangan: pemilik atau admin.
     */
    public function update(User $user, QrLink $link): bool
    {
        return $this->ownsOrAdmin($user, $link);
    }

    /**
     * Nonaktifkan / aktifkan kembali: pemilik atau admin.
     */
    public function toggle(User $user, QrLink $link): bool
    {
        return $this->ownsOrAdmin($user, $link);
    }

    private function ownsOrAdmin(User $user, QrLink $link): bool
    {
        return $user->role === 'admin' || $link->created_by === $user->id;
    }
}
