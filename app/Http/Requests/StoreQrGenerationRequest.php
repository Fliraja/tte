<?php

namespace App\Http\Requests;

use App\Models\LetterType;
use App\Models\Signer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQrGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'letter_type_id' => ['required', 'exists:letter_types,id'],
            'signer_id' => ['required', 'exists:signers,id'],
            'perihal' => ['required', 'string', 'max:255'],
            'destination_id' => ['nullable', 'exists:letter_destinations,id'],
            'unit_id' => ['nullable', 'exists:letter_units,id'],
            'manual_number' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $typeId = $this->input('letter_type_id');
            if (! $typeId) {
                return;
            }

            $type = LetterType::find($typeId);
            if (! $type) {
                return;
            }

            if (! $type->is_active) {
                $validator->errors()->add('letter_type_id', "Jenis surat '{$type->name}' sedang nonaktif.");
                return;
            }

            if ($type->numbering === 'none') {
                $validator->errors()->add('letter_type_id', 'Jenis surat tanpa nomor harus diproses melalui menu TTE Tanpa Nomor.');
                return;
            }

            if ($type->numbering === 'manual') {
                if (trim((string) $this->input('manual_number')) === '') {
                    $validator->errors()->add('manual_number', 'Nomor surat manual wajib diisi.');
                }
            } elseif ($type->numbering === 'auto') {
                if ($type->uses_unit) {
                    if (! $this->input('unit_id')) {
                        $validator->errors()->add('unit_id', 'Unit wajib dipilih untuk jenis surat ini.');
                    }
                } else {
                    if (! $this->input('destination_id')) {
                        $validator->errors()->add('destination_id', 'Peruntukan wajib dipilih.');
                    }
                }
            }

            if ($type->reference_label && $type->reference_required) {
                if (trim((string) $this->input('reference')) === '') {
                    $validator->errors()->add('reference', "{$type->reference_label} wajib diisi.");
                }
            }

            $signerId = $this->input('signer_id');
            if ($signerId) {
                $signer = Signer::find($signerId);
                if ($signer && ! $signer->is_active) {
                    $validator->errors()->add('signer_id', "Penandatangan '{$signer->name}' sedang nonaktif.");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'letter_type_id.required' => 'Jenis surat wajib dipilih.',
            'signer_id.required' => 'Penandatangan wajib dipilih.',
            'perihal.required' => 'Perihal surat wajib diisi.',
            'destination_id.exists' => 'Peruntukan yang dipilih tidak valid.',
            'unit_id.exists' => 'Unit yang dipilih tidak valid.',
        ];
    }
}
