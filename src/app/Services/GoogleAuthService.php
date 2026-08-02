<?php

namespace App\Services;

use Google_Client;
use RuntimeException;

class GoogleAuthService
{
    public function verifyIdToken(string $idToken): array|false
    {
        $clientId = config('services.google.client_id');

        if (empty($clientId)) {
            throw new RuntimeException('GOOGLE_CLIENT_ID is not configured.');
        }

        $client = new Google_Client([
            'client_id' => $clientId,
        ]);

        return $client->verifyIdToken($idToken);
    }
}
