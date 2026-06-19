<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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

        $driver = config('services.rabbitmq.driver', 'amqp');

        if ($driver === 'amqp') {
            return $this->publishViaAmqp($routingKey, $message);
        }

        return $this->publishViaHttp($routingKey, $message);
    }

    protected function publishViaAmqp(string $routingKey, array $message): bool
    {
        try {
            $host = config('services.rabbitmq.host', 'localhost');
            $port = (int) config('services.rabbitmq.port', 5672);
            $user = config('services.rabbitmq.user', 'guest');
            $password = config('services.rabbitmq.password', 'guest');
            $queue = config('services.rabbitmq.queue', 'winner_invoice_queue');

            Log::info("RabbitMQ AMQP: Attempting to connect to {$host}:{$port}");

            $connection = new AMQPStreamConnection($host, $port, $user, $password);
            $channel = $connection->channel();

            // Declare queue as durable
            $channel->queue_declare($queue, false, true, false, false);

            $msg = new AMQPMessage(
                json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                ]
            );

            // Publish message to default exchange with routing key matching the queue name
            $channel->basic_publish($msg, '', $queue);

            $channel->close();
            $connection->close();

            Log::info('RabbitMQ AMQP: Event published successfully.', [
                'queue' => $queue,
                'routing_key' => $routingKey,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('RabbitMQ AMQP: Publish failed.', [
                'routing_key' => $routingKey,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
    }

    protected function publishViaHttp(string $routingKey, array $message): bool
    {
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
                Log::error('RabbitMQ HTTP: Central publish failed.', [
                    'routing_key' => $routingKey,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }

            Log::info('RabbitMQ HTTP: Event published successfully via Central API.', [
                'routing_key' => $routingKey,
                'response' => $response->json(),
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('RabbitMQ HTTP: Central publish exception.', [
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
                    'nim' => config('services.sso.nim'),
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
