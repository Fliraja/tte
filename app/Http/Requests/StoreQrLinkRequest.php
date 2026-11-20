<?php

namespace App\Http\Requests;

use App\Support\QrLinkUrlGuard;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreQrLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_url' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    $reason = app(QrLinkUrlGuard::class)->reason((string) $value);

                    if ($reason !== null) {
                        $fail($reason);
                    }
                },
            ],
            'label' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_url.required' => 'Tautan tujuan wajib diisi.',
            'label.required' => 'Keterangan wajib diisi.',
            'label.max' => 'Keterangan maksimal 255 karakter.',
        ];
    }
}
