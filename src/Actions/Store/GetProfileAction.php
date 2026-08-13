<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\Actions\BaseAction;
use Psr\Http\Message\ResponseInterface as Response;

class GetProfileAction extends BaseAction
{
    protected function action(): Response
    {
        return $this->successResponse([
            'store' => [
                'name' => $_ENV['STORE_NAME'] ?? 'Hanaka Cake',
                'address' => $_ENV['STORE_ADDRESS'] ?? 'Jl. DR. Sukono Rt 09 No 11, Karang Rejo, Balikpapan Kota, Kalimantan Timur. 76124',
                'hours' => $_ENV['STORE_HOURS'] ?? '07.00 AM - 11.00 PM',
                'whatsapp' => $_ENV['STORE_WHATSAPP'] ?? '6281299998888',
                'instagram' => $_ENV['STORE_INSTAGRAM'] ?? 'hanakacake.id',
                // Map origin: the store pin on the checkout picker and the
                // tracking map. Defaults to Karang Rejo, Balikpapan.
                'lat' => (float) ($_ENV['STORE_LAT'] ?? -1.2654),
                'lng' => (float) ($_ENV['STORE_LNG'] ?? 116.8312),
            ],
        ]);
    }
}
