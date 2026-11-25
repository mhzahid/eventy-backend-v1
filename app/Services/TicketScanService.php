<?php

namespace App\Services;

class TicketScanService
{
    public function decryptQrData(string $encryptedData): ?array
    {
        $decrypted = openssl_decrypt(
            $encryptedData,
            config('services.ticket_qr.cipher_algorithm'),
            config('app.key'),
            0,
            hex2bin(config('services.ticket_qr.encryption_iv'))
        );

        if ($decrypted === false) {
            return null;
        }

        $parts = explode('#', $decrypted, 2);
        if (count($parts) !== 2) {
            return null;
        }

        return [
            'ticket_code' => $parts[0],
            'event_id'    => $parts[1],
        ];
    }
}
