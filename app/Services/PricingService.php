<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\PriceSlab;
use App\Models\Promotion;
use Illuminate\Support\Carbon;

class PricingService
{
    public function calculate(
        Customer $customer,
        Product $product,
        float $quantity
    ): array {
        // Ensure customer and product belong to the same company.
        if (
            $customer->company_id !== $product->company_id
        ) {
            throw new \InvalidArgumentException(
                'Customer and product must belong to the same company.'
            );
        }

        // Step 1: Get customer price or base price.
        $priceListPrice = null;

        if ($customer->price_list_id) {
            $priceListPrice = $customer->priceList()
                ->first()
                ?->items()
                ->where('product_id', $product->id)
                ->value('unit_price');
        }

        $unitPrice = (float) (
            $priceListPrice ?? $product->base_price
        );

        // Step 2: Find the best applicable quantity slab.
        $slab = PriceSlab::where('company_id', $product->company_id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->where('minimum_quantity', '<=', $quantity)
            ->orderByDesc('minimum_quantity')
            ->first();

        $slabDiscountPercent = $slab
            ? (float) $slab->discount_percent
            : 0;

        $grossAmount = round($unitPrice * $quantity, 2);

        $slabDiscountAmount = round(
            $grossAmount * $slabDiscountPercent / 100,
            2
        );

        $amountAfterSlab = round(
            $grossAmount - $slabDiscountAmount,
            2
        );

        // Step 3: Find an active percentage promotion.
        $today = Carbon::today();

        $promotion = Promotion::where('company_id', $product->company_id)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->where('promotion_type', 'PERCENTAGE')
            ->first();

        $promoDiscountPercent = $promotion
            ? (float) $promotion->discount_percent
            : 0;

        $promoDiscountAmount = round(
            $amountAfterSlab * $promoDiscountPercent / 100,
            2
        );

        $taxableAmount = round(
            $amountAfterSlab - $promoDiscountAmount,
            2
        );

        // Step 4: Calculate GST.
        $gstRate = (float) $product->gst_rate;

        if (
            strtolower(trim($customer->state)) ===
            strtolower(trim($customer->company->state))
        ) {
            $cgst = round($taxableAmount * $gstRate / 200, 2);
            $sgst = round($taxableAmount * $gstRate / 200, 2);
            $igst = 0;
        } else {
            $cgst = 0;
            $sgst = 0;
            $igst = round($taxableAmount * $gstRate / 100, 2);
        }

        // Step 5: Calculate Buy X Get Y free quantity.
        $freeQuantity = 0;

        $freePromotion = Promotion::where(
            'company_id',
            $product->company_id
        )
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->where('promotion_type', 'BUY_X_GET_Y')
            ->first();

        if (
            $freePromotion &&
            (float) $freePromotion->buy_quantity > 0
        ) {
            $groups = floor(
                $quantity / (float) $freePromotion->buy_quantity
            );

            $freeQuantity = $groups *
                (float) $freePromotion->free_quantity;
        }

        return [
            'unit_price' => round($unitPrice, 2),
            'gross_amount' => $grossAmount,
            'slab_discount_percent' => $slabDiscountPercent,
            'slab_discount_amount' => $slabDiscountAmount,
            'promo_discount_percent' => $promoDiscountPercent,
            'promo_discount_amount' => $promoDiscountAmount,
            'taxable_amount' => $taxableAmount,
            'gst_rate' => $gstRate,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => $igst,
            'line_total' => round(
                $taxableAmount + $cgst + $sgst + $igst,
                2
            ),
            'free_quantity' => $freeQuantity,
            'pricing_breakdown' => [
                'price_source' => $priceListPrice !== null
                    ? 'CUSTOMER_PRICE_LIST'
                    : 'BASE_PRICE',
                'unit_price' => $unitPrice,
                'slab_discount_percent' => $slabDiscountPercent,
                'promo_discount_percent' => $promoDiscountPercent,
                'promotion_code' => $promotion?->code,
                'free_promotion_code' => $freePromotion?->code,
            ],
        ];
    }
}
