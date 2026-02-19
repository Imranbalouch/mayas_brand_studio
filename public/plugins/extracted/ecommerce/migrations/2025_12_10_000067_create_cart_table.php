<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('auth_id');
            $table->string('order_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('varaint_name')->nullable();
            $table->double('product_price')->nullable();
            $table->integer('product_qty')->nullable();
            $table->text('product_id')->nullable();
            $table->string('variant_id')->nullable();
            $table->text('custom_item_id')->nullable();
            $table->text('product_img')->nullable();
            $table->string('product_sku')->nullable();
            $table->double('discount_amount', 20, 2)->nullable();
            $table->string('coupon_uuid')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->double('rate')->nullable();
            $table->double('total_rate_amount')->nullable();
            $table->double('flat_discount')->nullable();
            $table->double('percentage_discount')->nullable();
            $table->double('each_discount')->nullable();
            $table->double('product_discount_amount')->nullable();
            $table->double('gross_amount')->nullable();
            $table->double('coupon_percentage')->nullable();
            $table->double('coupon_amount')->nullable();
            $table->double('net_amount')->nullable();
            $table->double('vat_percentage')->nullable();
            $table->double('vat_amount')->nullable();
            $table->integer('shipping_vat_percent')->nullable();
            $table->decimal('shipping_vat_amount', 10, 0)->nullable();
            $table->double('total_amount')->nullable();
            $table->double('discount')->nullable();
            $table->string('coupon_code')->nullable();
            $table->integer('coupon_applied')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart');
    }
};