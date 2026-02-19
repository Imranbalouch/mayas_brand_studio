<?php

namespace App\Utility;

use App\Models\Ecommerce\Cart;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductStock;

class CartUtility
{
    public static function get_price($product, $productStock, $quantity, $discount_applicable = true)
    {
        $price = $productStock->price ?? $product->unit_price;

        // Apply discount if applicable
        if ($discount_applicable) {
            $price = self::discount_calculation($product, $price);
        }

        return $price;
    }

    public static function discount_calculation($product, $price)
    {
        $discount = $product->discounts->first();
        
        if ($discount) {
            if ($discount->type === 'percentage') {
                $price -= ($price * $discount->value / 100);
            } elseif ($discount->type === 'amount') {
                $price -= $discount->value;
            }
        }

        return max(0, $price);
    }

    public static function tax_calculation($product, $price)
    {
        $tax = 0;
        
        // Calculate VAT from product's vat relationship
        if ($product->vat) {
            // dd($price);
            $tax = ($price * $product->vat->rate) / 100;
        }

        return $tax;
    }

    public static function calculateOrder($cart)
    {
        $product = Product::where('uuid', $cart->product_id)->first();
        if (!$product) {
            return $cart;
        }

        $productStock = ProductStock::where('uuid', $cart->variant_id)->first();
        $rate = $cart->product_price;
        $qty = $cart->product_qty;

        // Calculate VAT percentage (sum of all tax percentages)
        $vatPercent = 0;
        if ($product->vat) {
            $vatPercent = $product->vat->rate;
        }

        // Calculate discounts
        $percentDiscount = 0;
        $flatDiscount = 0;
        $eachDiscount = 0;

        $discount = $product->discounts->first();
        if ($discount) {
            if ($discount->type === 'percentage') {
                $percentDiscount = $rate * ($discount->value / 100);
                $eachDiscount = $percentDiscount;
                $flatDiscount = 0;
            } elseif ($discount->type === 'amount') {
                $percentDiscount = 0;
                $flatDiscount = $discount->value;
                $eachDiscount = $discount->value;
            }
        }

        // Step 1: Amount
        $amount = $rate * $qty;

        // Step 2: Discount Amount
        $eachDiscount = $eachDiscount;
        $discountAmount = ($eachDiscount * $qty);

        // Step 3: Gross Amount
        $grossAmount = $amount - $discountAmount;

        // Step 4: Net Amount
        $netAmount = $grossAmount;

        // Step 5: Coupon Apply
        if ($cart->coupon_applied) {
            $coupon_amount = $cart->coupon_amount;
            $netAmount = $netAmount - $coupon_amount;
            $coupon_percentage = $cart->coupon_percentage;
        } else {
            $coupon_percentage = 0;
            $coupon_amount = 0;
        }

        // Step 6: VAT
        $vatAmount = ($netAmount * $vatPercent) / 100;

        // Step 7: Order Amount
        $orderAmount = $netAmount + $vatAmount;

        // Update cart with calculated values
        $data = [
            'rate' => $rate,
            'total_rate_amount' => $amount,
            'flat_discount' => $flatDiscount,
            'percentage_discount' => $percentDiscount,
            'each_discount' => $eachDiscount,
            'product_discount_amount' => $discountAmount,
            'gross_amount' => $grossAmount,
            'coupon_percentage' => $coupon_percentage,
            'coupon_amount' => $coupon_amount,
            'net_amount' => $netAmount,
            'vat_percentage' => $vatPercent,
            'vat_amount' => $vatAmount,
            'total_amount' => $orderAmount,
        ];

        $cart->update($data);
        return $cart;
    }

    public static function save_cart_data($cart, $product, $productStock, $quantity)
    {
        $price = self::get_price($product, $productStock, $quantity, false);
        $tax = self::tax_calculation($product, $price);
        $cart->update([
            'product_qty' => $quantity,
            'product_price' => $price,
            'rate' => $price, // Base rate without tax
        ]);

        // Calculate order details
        self::calculateOrder($cart);

        return $cart;
    }
}