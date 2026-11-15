<?php

namespace App\Services\Odeme;

/**
 * Kart ödeme sağlayıcı seçimi:
 * iyzico anahtarları tanımlıysa gerçek 3DS akışı, değilse tam akışlı sandbox simülasyonu.
 */
class OdemeGatewayFactory
{
    public static function olustur(): OdemeGateway
    {
        if (config('services.iyzico.enabled')) {
            return new IyzicoGateway();
        }

        return new MockGateway();
    }
}
