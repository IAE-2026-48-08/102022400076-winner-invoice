<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQPublisher
{
    protected string $host;
    protected int $port;
    protected string $user;
    protected string $password;
    protected string $queue;

    public function __construct()
    {
        $this->host = env('RABBITMQ_HOST', 'localhost');
        $this->port = (int) env('RABBITMQ_PORT', 5672);
        $this->user = env('RABBITMQ_USER', 'guest');
        $this->password = env('RABBITMQ_PASSWORD', 'guest');
        $this->queue = env('RABBITMQ_QUEUE', 'winner_invoice_queue');
    }

    /**
     * Publish JSON event message to RabbitMQ.
     *
     * @param string $eventType
     * @param array $data
     * @return void
     */
    public function publishEvent(string $eventType, array $data): void
    {
        $payload = [
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        $jsonPayload = json_encode($payload);

        Log::info("RabbitMQ: Publishing event {$eventType}", [
            'payload' => $jsonPayload,
            'host' => $this->host,
            'port' => $this->port,
            'queue' => $this->queue
        ]);

        try {
            // Establish Connection
            $connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                '/' // vhost
            );

            $channel = $connection->channel();

            // Declare Queue (passive=false, durable=true, exclusive=false, auto_delete=false)
            $channel->queue_declare($this->queue, false, true, false, false);

            // Create Message
            $msg = new AMQPMessage($jsonPayload, [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            // Publish Message (using default exchange with routing key as queue name)
            $channel->basic_publish($msg, '', $this->queue);

            // Clean up
            $channel->close();
            $connection->close();

            Log::info("RabbitMQ: Event successfully published to {$this->queue}");
        } catch (\Exception $e) {
            Log::error("RabbitMQ: Failed to publish event", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Graceful fallback: we do not throw exceptions to avoid interrupting the main business flow
        }
    }
}
