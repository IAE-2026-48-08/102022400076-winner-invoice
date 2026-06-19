<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SsoService
{
    public function authenticate(string $email, string $password): array
    {
        try {
            $response = Http::baseUrl(rtrim((string) config('services.sso.base_url'), '/'))
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-API-Key' => (string) config('services.sso.api_key'),
                    'X-IAE-KEY' => (string) config('services.sso.api_key'),
                ])
                ->timeout((int) config('services.sso.timeout', 10))
                ->post('/api/v1/auth/token', [
                    'email' => $email,
                    'password' => $password,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Layanan SSO tidak dapat dihubungi.', 0, $exception);
        }

        if ($response->failed()) {
            $message = $response->json('message')
                ?? $response->json('error')
                ?? 'Email atau password SSO tidak valid.';

            throw new RuntimeException((string) $message, $response->status());
        }

        $payload = $response->json();
        $token = data_get($payload, 'access_token')
            ?? data_get($payload, 'token')
            ?? data_get($payload, 'data.access_token')
            ?? data_get($payload, 'data.token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Response SSO tidak memuat access token.');
        }

        $user = data_get($payload, 'profile')
            ?? data_get($payload, 'data.profile')
            ?? data_get($payload, 'user')
            ?? data_get($payload, 'data.user')
            ?? [];

        if (! is_array($user)) {
            $user = [];
        }

        $user['email'] = $user['email'] ?? $email;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}
