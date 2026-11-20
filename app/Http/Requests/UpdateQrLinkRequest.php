<?php

namespace App\Http\Requests;

/**
 * Aturan isian sama dengan pembuatan. Bedanya hanya otorisasi:
 * pemilik atau admin (QrLinkPolicy::update).
 */
class UpdateQrLinkRequest extends StoreQrLinkRequest
{
    public function authorize(): bool
    {
        $link = $this->route('link');

        return $link !== null && $this->user()?->can('update', $link) === true;
    }
}
