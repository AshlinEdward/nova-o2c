<?php

namespace App\Console\Commands;

use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Console\Command;

class RetryWebhookDeliveries extends Command
{
    protected $signature = 'webhooks:retry';

    protected $description = 'Retry pending webhook deliveries';

    public function handle(WebhookService $webhookService): int
    {
        $recovered = $webhookService->recoverStuckDeliveries();

        $this->info("Recovered {$recovered} stuck webhook delivery(s).");

        $processed = 0;

        WebhookDelivery::where('status', 'PENDING')
            ->where(function ($query) {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('id')
            ->chunkById(100, function ($deliveries) use (
                $webhookService,
                &$processed
            ) {
                foreach ($deliveries as $delivery) {
                    $webhookService->deliver($delivery->id);
                    $processed++;
                }
            });

        $this->info("Processed {$processed} webhook delivery attempt(s).");

        return self::SUCCESS;
    }
}
