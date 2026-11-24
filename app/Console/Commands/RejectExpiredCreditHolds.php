<?php

namespace App\Console\Commands;

use App\Models\SalesOrder;
use App\Models\SalesOrderCreditReview;
use App\Models\User;
use App\Notifications\CreditHoldNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RejectExpiredCreditHolds extends Command
{
    protected $signature = 'orders:reject-expired-credit-holds';

    protected $description = 'Automatically reject credit-held orders older than 48 hours';

    public function handle(): int
    {
        $rejectedCount = 0;

        SalesOrder::where('status', 'CREDIT_HOLD')
            ->whereNotNull('credit_hold_at')
            ->where('credit_hold_at', '<=', now()->subHours(48))
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$rejectedCount) {

                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$rejectedCount) {

                        $lockedOrder = SalesOrder::whereKey($order->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            !$lockedOrder ||
                            $lockedOrder->status !== 'CREDIT_HOLD' ||
                            !$lockedOrder->credit_hold_at ||
                            $lockedOrder->credit_hold_at->gt(now()->subHours(48))
                        ) {
                            return;
                        }

                        $customerName = $lockedOrder->customer->name
                            ?? 'Unknown Customer';

                        $lockedOrder->update([
                            'status' => 'REJECTED',
                            'credit_hold_comment' =>
                                'Automatically rejected after 48 hours without manager review.',
                        ]);

                        SalesOrderCreditReview::create([
                            'sales_order_id' => $lockedOrder->id,
                            'reviewed_by' => null,
                            'action' => 'AUTO_REJECTED',
                            'comment' =>
                                'System automatically rejected this order after 48 hours in credit hold.',
                            'reviewed_at' => now(),
                        ]);

                        $orderId = $lockedOrder->id;
                        $companyId = $lockedOrder->company_id;
                        $orderNumber = $lockedOrder->order_number;
                        $amount = (float) $lockedOrder->grand_total;

                        DB::afterCommit(function () use (
                            $orderId,
                            $companyId,
                            $orderNumber,
                            $customerName,
                            $amount
                        ) {
                            User::where('company_id', $companyId)
                                ->whereHas('roles', function ($query) {
                                    $query->where('name', 'Sales Executive');
                                })
                                ->get()
                                ->each(function ($salesUser) use (
                                    $orderId,
                                    $orderNumber,
                                    $customerName,
                                    $amount
                                ) {
                                    $salesUser->notify(
                                        new CreditHoldNotification(
                                            $orderId,
                                            $orderNumber,
                                            $customerName,
                                            $amount,
                                            'Your credit-held order was automatically rejected after 48 hours.'
                                        )
                                    );
                                });
                        });

                        $rejectedCount++;
                    });
                }
            });

        $this->info(
            "Automatically rejected {$rejectedCount} expired credit-held order(s)."
        );

        return self::SUCCESS;
    }
}
