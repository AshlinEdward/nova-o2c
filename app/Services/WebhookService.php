<?php

namespace App\Services;

use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Throwable;

class WebhookService
{
    private function validateDestination(string $url): void
    {
        $parts = parse_url($url);

        if (
            !$parts ||
            empty($parts['host']) ||
            !in_array($parts['scheme'] ?? '', ['https', 'http'], true) ||
            isset($parts['user']) ||
            isset($parts['pass'])
        ) {
            throw new InvalidArgumentException(
                'Invalid webhook destination URL.'
            );
        }

        $host = strtolower($parts['host']);

        $isLocal = in_array($host, [
            'localhost',
            '127.0.0.1',
            '::1',
        ], true);

        if (($parts['scheme'] === 'http') && !$isLocal) {
            throw new InvalidArgumentException(
                'HTTP is permitted only for local webhook testing. Use HTTPS.'
            );
        }
    }

    public function queue(
        int $companyId,
        string $eventName,
        string $destinationUrl,
        array $payload
    ): WebhookDelivery {
        $this->validateDestination($destinationUrl);

        return WebhookDelivery::create([
            'company_id' => $companyId,
            'event_name' => $eventName,
            'destination_url' => $destinationUrl,
            'payload' => $payload,
            'status' => 'PENDING',
            'attempts' => 0,
            'max_attempts' => 5,
            'next_retry_at' => now(),
        ]);
    }

    public function recoverStuckDeliveries(int $minutes = 5): int
    {
        return WebhookDelivery::where('status', 'PROCESSING')
            ->where('updated_at', '<=', now()->subMinutes($minutes))
            ->update([
                'status' => 'PENDING',
                'next_retry_at' => now(),
                'last_error' => 'Recovered stale processing delivery.',
            ]);
    }

    public function deliver(int $deliveryId): bool
    {
        $delivery = DB::transaction(function () use ($deliveryId) {
            $record = WebhookDelivery::whereKey($deliveryId)
                ->lockForUpdate()
                ->first();

            if (
                !$record ||
                in_array($record->status, ['SUCCESS', 'FAILED', 'PROCESSING'])
            ) {
                return null;
            }

            if (
                $record->next_retry_at &&
                $record->next_retry_at->isFuture()
            ) {
                return null;
            }

            if ($record->attempts >= $record->max_attempts) {
                $record->update(['status' => 'FAILED']);
                return null;
            }

            $record->update([
                'status' => 'PROCESSING',
                'attempts' => $record->attempts + 1,
                'next_retry_at' => null,
            ]);

            return $record;
        });

        if (!$delivery) {
            return false;
        }

        try {
            $this->validateDestination($delivery->destination_url);
        } catch (Throwable $exception) {
            return $this->recordFailure($delivery, $exception->getMessage());
        }

        $body = json_encode([
            'id' => $delivery->id,
            'event' => $delivery->event_name,
            'data' => $delivery->payload,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            return $this->recordFailure(
                $delivery,
                'Unable to encode webhook payload.'
            );
        }

        $secret = config('services.webhooks.secret');

        if (!$secret) {
            return $this->recordFailure(
                $delivery,
                'Webhook signing secret is not configured.'
            );
        }

        $signature = hash_hmac('sha256', $body, $secret);

        $delivery->update(['signature' => $signature]);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Nova-Event' => $delivery->event_name,
                    'X-Nova-Delivery' => (string) $delivery->id,
                    'X-Nova-Signature' => 'sha256=' . $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($delivery->destination_url);

            $success = $response->successful();
            $exhausted = $delivery->attempts >= $delivery->max_attempts;

            $delivery->update([
                'status' => $success
                    ? 'SUCCESS'
                    : ($exhausted ? 'FAILED' : 'PENDING'),
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 5000),
                'last_error' => $success
                    ? null
                    : 'HTTP ' . $response->status(),
                'delivered_at' => $success ? now() : null,
                'next_retry_at' => $success || $exhausted
                    ? null
                    : now()->addSeconds(
                        min(3600, 30 * (2 ** ($delivery->attempts - 1)))
                    ),
            ]);

            return $success;

        } catch (Throwable $exception) {
            return $this->recordFailure(
                $delivery,
                $exception->getMessage()
            );
        }
    }

    private function recordFailure(
        WebhookDelivery $delivery,
        string $error
    ): bool {
        $exhausted = $delivery->attempts >= $delivery->max_attempts;

        $delivery->update([
            'status' => $exhausted ? 'FAILED' : 'PENDING',
            'last_error' => substr($error, 0, 5000),
            'next_retry_at' => $exhausted
                ? null
                : now()->addSeconds(
                    min(3600, 30 * (2 ** max(0, $delivery->attempts - 1)))
                ),
        ]);

        return false;
    }
}
