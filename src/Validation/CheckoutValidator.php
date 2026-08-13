<?php

declare(strict_types=1);

namespace App\Validation;

class CheckoutValidator extends Validator
{
    public function validateCheckout(array $data): array
    {
        $schema = [
            'customerName' => [
                ['type' => 'required', 'message' => 'Nama pemesan wajib diisi.'],
                ['type' => 'minLength', 'min' => 3, 'message' => 'Nama pemesan minimal 3 karakter.'],
                ['type' => 'maxLength', 'max' => 100, 'message' => 'Nama pemesan maksimal 100 karakter.'],
            ],
            'phone' => [
                ['type' => 'required', 'message' => 'Nomor telepon wajib diisi.'],
                ['type' => 'phoneId', 'message' => 'Masukkan nomor telepon Indonesia yang valid.'],
            ],
            'pickupMethod' => [
                ['type' => 'required', 'message' => 'Metode pengambilan wajib dipilih.'],
                ['type' => 'oneOf', 'options' => ['pickup', 'delivery'], 'message' => 'Metode pengambilan harus pickup atau delivery.'],
            ],
            'pickupDate' => [
                [
                    'type' => 'when',
                    'predicate' => fn(array $d) => ($d['pickupMethod'] ?? '') === 'pickup',
                    'rule' => ['type' => 'required', 'message' => 'Tanggal pengambilan wajib diisi untuk pickup.'],
                ],
            ],
            'pickupTime' => [
                [
                    'type' => 'when',
                    'predicate' => fn(array $d) => ($d['pickupMethod'] ?? '') === 'pickup',
                    'rule' => ['type' => 'required', 'message' => 'Jam pengambilan wajib diisi untuk pickup.'],
                ],
            ],
            'address' => [
                [
                    'type' => 'when',
                    'predicate' => fn(array $d) => ($d['pickupMethod'] ?? '') === 'delivery',
                    'rule' => ['type' => 'required', 'message' => 'Alamat pengiriman wajib diisi untuk delivery.'],
                ],
                ['type' => 'maxLength', 'max' => 220, 'message' => 'Alamat maksimal 220 karakter.'],
            ],
            'addressNote' => [
                ['type' => 'maxLength', 'max' => 120, 'message' => 'Catatan alamat maksimal 120 karakter.'],
            ],
            // Optional drop-off point picked on the checkout map. An order
            // without coordinates stays valid — it just does not appear on the
            // tracking/owner maps.
            'deliveryLat' => [
                ['type' => 'floatBetween', 'min' => -90, 'max' => 90, 'message' => 'Titik lokasi tidak valid.'],
            ],
            'deliveryLng' => [
                ['type' => 'floatBetween', 'min' => -180, 'max' => 180, 'message' => 'Titik lokasi tidak valid.'],
            ],
            'paymentMethod' => [
                ['type' => 'required', 'message' => 'Metode pembayaran wajib dipilih.'],
                ['type' => 'oneOf', 'options' => ['cash', 'qris'], 'message' => 'Metode pembayaran harus cash atau qris.'],
            ],
        ];

        return $this->validate($data, $schema);
    }

    public static function allowedFields(): array
    {
        return [
            'customerName',
            'phone',
            'pickupMethod',
            'pickupDate',
            'pickupTime',
            'address',
            'addressNote',
            'paymentMethod',
            'deliveryLat',
            'deliveryLng',
        ];
    }
}
