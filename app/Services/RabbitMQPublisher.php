<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RabbitMQPublisher
{
    public function publishEvent(string $routingKey, array $data): bool
    {
        $message = [
            'event_name' => $this->eventName($routingKey),
            'service_name' => 'Winner-Invoice-Service',
            'api_version' => 'v1',
            'occurred_at' => now()->toIso8601String(),
            'data' => $data,
        ];

        try {
            $token = $this->getMachineToken();

            $response = Http::baseUrl(rtrim((string) config('services.sso.base_url'), '/'))
                ->withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.sso.timeout', 10))
                ->post('/api/v1/messages/publish', [
                    'routing_key' => $routingKey,
                    'message' => $message,
                ]);

            if ($response->failed()) {
                Log::error('RabbitMQ central publish failed.', [
                    'routing_key' => $routingKey,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('RabbitMQ central event published.', [
                'routing_key' => $routingKey,
                'response' => $response->json(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('RabbitMQ central publish exception.', [
                'routing_key' => $routingKey,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function getMachineToken(): string
    {
        try {
            $response = Http::baseUrl(rtrim((string) config('services.sso.base_url'), '/'))
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-API-Key' => (string) config('services.sso.api_key'),
                ])
                ->timeout((int) config('services.sso.timeout', 10))
                ->post('/api/v1/auth/token', [
                    'api_key' => config('services.sso.api_key'),
                ]);
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('Layanan token M2M IAE tidak dapat dihubungi.', 0, $exception);
        }

        $response->throw();

        $token = $response->json('token')
            ?? $response->json('access_token')
            ?? $response->json('data.token')
            ?? $response->json('data.access_token');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('Response token M2M tidak memuat token.');
        }

        return $token;
    }

    private function eventName(string $routingKey): string
    {
        return collect(explode('.', $routingKey))
            ->map(fn (string $part): string => ucfirst($part))
            ->implode('');
    }
}
