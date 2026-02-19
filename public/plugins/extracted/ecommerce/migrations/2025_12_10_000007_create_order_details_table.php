<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('auth_id')->nullable();
            $table->string('order_id');
            $table->string('product_name')->nullable();
            $table->double('product_price')->nullable();
            $table->integer('product_qty')->nullable();
            $table->double('vat')->nullable();
            $table->longText('image')->nullable();
            $table->text('product_id')->nullable();
            $table->string('variant_id')->nullable();
            $table->string('variant')->nullable();
            $table->string('fulfilled_status')->nullable();
            $table->text('custom_item_id')->nullable();
            $table->decimal('discount_amount', 20, 2)->nullable();
            $table->string('coupon_uuid')->nullable();
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
            $table->double('total_amount')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};