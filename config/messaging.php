<?php

return [
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'checkins.events'),
        'routing_key' => env('RABBITMQ_ROUTING_KEY', 'checkin.registered'),
        'queue' => env('RABBITMQ_QUEUE', 'engagement.checkin.registered'),
        'retry_queue' => env('RABBITMQ_RETRY_QUEUE', 'engagement.checkin.registered.retry'),
        'dlq' => env('RABBITMQ_DLQ', 'engagement.checkin.registered.dlq'),
        'retry_delay_ms' => (int) env('RABBITMQ_RETRY_DELAY_MS', 10000),
        'max_retries' => (int) env('RABBITMQ_MAX_RETRIES', 5),
    ],
];
