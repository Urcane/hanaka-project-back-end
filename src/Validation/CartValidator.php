<?php

declare(strict_types=1);

namespace App\Validation;

class CartValidator extends Validator
{
    public function validateAddItem(array $data): array
    {
        $schema = [
            'productId' => [
                ['type' => 'required', 'message' => 'Produk wajib dipilih.'],
            ],
            'sizeId' => [
                ['type' => 'required', 'message' => 'Ukuran cake wajib dipilih.'],
            ],
            'colorText' => [
                ['type' => 'required', 'message' => 'Warna kue wajib diisi.'],
                ['type' => 'maxLength', 'max' => 40, 'message' => 'Warna kue maksimal 40 karakter.'],
            ],
            'theme' => [
                ['type' => 'maxLength', 'max' => 40, 'message' => 'Tema kue maksimal 40 karakter.'],
            ],
            'message' => [
                ['type' => 'maxLength', 'max' => 60, 'message' => 'Pesan pada kue maksimal 60 karakter.'],
            ],
            'quantity' => [
                ['type' => 'required', 'message' => 'Jumlah wajib diisi.'],
                ['type' => 'numeric', 'message' => 'Jumlah harus berupa angka.'],
                ['type' => 'minNumber', 'min' => 1, 'message' => 'Jumlah minimal 1.'],
                ['type' => 'maxNumber', 'max' => 5, 'message' => 'Jumlah maksimal 5.'],
            ],
        ];

        return $this->validate($data, $schema);
    }

    public function validateUpdateItem(array $data): array
    {
        $schema = [
            'sizeId' => [
                ['type' => 'required', 'message' => 'Ukuran cake wajib dipilih.'],
            ],
            'colorText' => [
                ['type' => 'required', 'message' => 'Warna kue wajib diisi.'],
                ['type' => 'maxLength', 'max' => 40, 'message' => 'Warna kue maksimal 40 karakter.'],
            ],
            'theme' => [
                ['type' => 'maxLength', 'max' => 40, 'message' => 'Tema kue maksimal 40 karakter.'],
            ],
            'message' => [
                ['type' => 'maxLength', 'max' => 60, 'message' => 'Pesan pada kue maksimal 60 karakter.'],
            ],
            'quantity' => [
                ['type' => 'required', 'message' => 'Jumlah wajib diisi.'],
                ['type' => 'numeric', 'message' => 'Jumlah harus berupa angka.'],
                ['type' => 'minNumber', 'min' => 1, 'message' => 'Jumlah minimal 1.'],
                ['type' => 'maxNumber', 'max' => 5, 'message' => 'Jumlah maksimal 5.'],
            ],
        ];

        return $this->validate($data, $schema);
    }

    public function validateUpdateQuantity(array $data): array
    {
        $schema = [
            'quantity' => [
                ['type' => 'required', 'message' => 'Jumlah wajib diisi.'],
                ['type' => 'numeric', 'message' => 'Jumlah harus berupa angka.'],
                ['type' => 'minNumber', 'min' => 1, 'message' => 'Jumlah minimal 1.'],
                ['type' => 'maxNumber', 'max' => 5, 'message' => 'Jumlah maksimal 5.'],
            ],
        ];

        return $this->validate($data, $schema);
    }

    public static function allowedAddFields(): array
    {
        return ['productId', 'sizeId', 'colorText', 'theme', 'message', 'quantity'];
    }

    public static function allowedUpdateFields(): array
    {
        return ['sizeId', 'colorText', 'theme', 'message', 'quantity'];
    }

    public static function allowedQuantityFields(): array
    {
        return ['quantity'];
    }
}
