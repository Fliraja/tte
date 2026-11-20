<?php

namespace App\Services;

use App\Models\QrLink;
use App\Models\QrLinkEvent;
use App\Models\User;
use App\Support\QrLinkUrlGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QrLinkService
{
    /** Tanpa karakter yang mudah tertukar: i, l, o, 0, 1 (spec K1). */
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const LABEL_MAX = 255;
    private const REASON_MIN = 5;

    public function __construct(private QrLinkUrlGuard $guard)
    {
    }

    public function create(User $user, string $targetUrl, string $label): QrLink
    {
        $targetUrl = $this->validTarget($targetUrl);
        $label = $this->validLabel($label);

        return DB::transaction(function () use ($user, $targetUrl, $label) {
            $link = QrLink::create([
                'code' => $this->generateCode(),
                'target_url' => $targetUrl,
                'label' => $label,
                'status' => QrLink::STATUS_ACTIVE,
                'scan_count' => 0,
                'created_by' => $user->id,
            ]);

            $this->recordEvent($link, $user, 'created');

            return $link;
        });
    }

    /**
     * Ganti tujuan dan/atau keterangan. Kode tidak pernah berubah (spec K7).
     * Event hanya dicatat bila tujuan benar-benar berubah.
     */
    public function updateTarget(QrLink $link, User $user, string $targetUrl, string $label): QrLink
    {
        $targetUrl = $this->validTarget($targetUrl);
        $label = $this->validLabel($label);

        return DB::transaction(function () use ($link, $user, $targetUrl, $label) {
            $oldUrl = $link->target_url;

            $link->update([
                'target_url' => $targetUrl,
                'label' => $label,
            ]);

            if ($oldUrl !== $targetUrl) {
                $this->recordEvent($link, $user, 'target_changed', $oldUrl . ' → ' . $targetUrl);
            }

            return $link;
        });
    }

    public function disable(QrLink $link, User $user, string $reason): QrLink
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < self::REASON_MIN) {
            throw ValidationException::withMessages([
                'disable_reason' => 'Alasan penonaktifan minimal ' . self::REASON_MIN . ' karakter.',
            ]);
        }

        if ($link->isDisabled()) {
            throw ValidationException::withMessages([
                'status' => 'QR Link ini sudah nonaktif.',
            ]);
        }

        return DB::transaction(function () use ($link, $user, $reason) {
            $link->update([
                'status' => QrLink::STATUS_DISABLED,
                'disabled_at' => now(),
                'disabled_by' => $user->id,
                'disable_reason' => $reason,
            ]);

            $this->recordEvent($link, $user, 'disabled', $reason);

            return $link;
        });
    }

    public function enable(QrLink $link, User $user): QrLink
    {
        if ($link->isActive()) {
            throw ValidationException::withMessages([
                'status' => 'QR Link ini sudah aktif.',
            ]);
        }

        return DB::transaction(function () use ($link, $user) {
            $link->update([
                'status' => QrLink::STATUS_ACTIVE,
                'disabled_at' => null,
                'disabled_by' => null,
                'disable_reason' => null,
            ]);

            $this->recordEvent($link, $user, 'enabled');

            return $link;
        });
    }

    /**
     * Kode acak unik. Unique index di DB tetap jadi jaring pengaman terakhir
     * bila dua request kebetulan menghasilkan kode yang sama.
     */
    public function generateCode(): string
    {
        $length = (int) config('tte.qr_link_code_length', 10);
        $max = strlen(self::ALPHABET) - 1;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= self::ALPHABET[random_int(0, $max)];
            }

            if (! QrLink::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Gagal membuat kode QR Link yang unik.');
    }

    private function validTarget(string $targetUrl): string
    {
        $targetUrl = $this->guard->normalize($targetUrl);

        $reason = $this->guard->reason($targetUrl);
        if ($reason !== null) {
            throw ValidationException::withMessages(['target_url' => $reason]);
        }

        return $targetUrl;
    }

    private function validLabel(string $label): string
    {
        $label = trim($label);

        if ($label === '') {
            throw ValidationException::withMessages(['label' => 'Keterangan wajib diisi.']);
        }

        if (mb_strlen($label) > self::LABEL_MAX) {
            throw ValidationException::withMessages(['label' => 'Keterangan maksimal ' . self::LABEL_MAX . ' karakter.']);
        }

        return $label;
    }

    private function recordEvent(QrLink $link, User $user, string $event, ?string $note = null): void
    {
        QrLinkEvent::create([
            'qr_link_id' => $link->id,
            'user_id' => $user->id,
            'event' => $event,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
