<?php

declare(strict_types=1);

namespace App\Validation;

class AuthValidator extends Validator
{
    public function validateRegister(array $data): array
    {
        $schema = [
            'fullName' => [
                ['type' => 'required', 'message' => 'Nama lengkap wajib diisi.'],
                ['type' => 'minLength', 'min' => 3, 'message' => 'Nama lengkap minimal 3 karakter.'],
                ['type' => 'maxLength', 'max' => 100, 'message' => 'Nama lengkap maksimal 100 karakter.'],
            ],
            'email' => [
                ['type' => 'required', 'message' => 'Email wajib diisi.'],
                ['type' => 'email', 'message' => 'Masukkan format email yang valid.'],
                ['type' => 'maxLength', 'max' => 150, 'message' => 'Email maksimal 150 karakter.'],
            ],
            'phone' => [
                ['type' => 'required', 'message' => 'Nomor telepon wajib diisi.'],
                ['type' => 'phoneId', 'message' => 'Masukkan nomor telepon Indonesia yang valid.'],
            ],
            'password' => [
                ['type' => 'required', 'message' => 'Password wajib diisi.'],
                ['type' => 'minLength', 'min' => 8, 'message' => 'Password minimal 8 karakter.'],
                ['type' => 'strongPassword', 'message' => 'Password harus mengandung huruf dan angka.'],
            ],
            'confirmPassword' => [
                ['type' => 'required', 'message' => 'Konfirmasi password wajib diisi.'],
                ['type' => 'sameAs', 'field' => 'password', 'message' => 'Password dan konfirmasi password harus sama.'],
            ],
        ];

        return $this->validate($data, $schema);
    }

    public function validateLogin(array $data): array
    {
        $schema = [
            'email' => [
                ['type' => 'required', 'message' => 'Email wajib diisi.'],
                ['type' => 'email', 'message' => 'Masukkan format email yang valid.'],
            ],
            'password' => [
                ['type' => 'required', 'message' => 'Password wajib diisi.'],
            ],
        ];

        return $this->validate($data, $schema);
    }

    public function validateProfile(array $data): array
    {
        $schema = [
            'fullName' => [
                ['type' => 'required', 'message' => 'Nama lengkap wajib diisi.'],
                ['type' => 'minLength', 'min' => 3, 'message' => 'Nama lengkap minimal 3 karakter.'],
                ['type' => 'maxLength', 'max' => 100, 'message' => 'Nama lengkap maksimal 100 karakter.'],
            ],
            'phone' => [
                ['type' => 'required', 'message' => 'Nomor telepon wajib diisi.'],
                ['type' => 'phoneId', 'message' => 'Masukkan nomor telepon Indonesia yang valid.'],
            ],
        ];

        return $this->validate($data, $schema);
    }

    public static function allowedRegisterFields(): array
    {
        return ['fullName', 'email', 'phone', 'password', 'confirmPassword'];
    }

    public static function allowedProfileFields(): array
    {
        return ['fullName', 'phone'];
    }

    public static function allowedLoginFields(): array
    {
        return ['email', 'password'];
    }
}
