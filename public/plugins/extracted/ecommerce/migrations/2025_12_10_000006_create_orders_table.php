<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('auth_id')->nullable();
            $table->double('grand_total');
            $table->text('notes')->nullable();
            $table->text('tags')->nullable();
            $table->text('payment_due_later')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('billing_first_name')->nullable();
            $table->string('billing_last_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->longText('billing_address')->nullable();
            $table->longText('billing_address2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_cities_id')->nullable();
            $table->string('billing_countries_id')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('shipping_first_name')->nullable();
            $table->string('shipping_last_name')->nullable();
            $table->string('shipping_email')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->longText('shipping_address')->nullable();
            $table->longText('shipping_address2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_cities_id')->nullable();
            $table->string('shipping_countries_id')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('market_id');
            $table->string('location_id')->nullable();
            $table->dateTime('reserve_item')->nullable();
            $table->text('code');
            $table->string('discount_code')->nullable();
            $table->string('discount_type')->nullable();
            $table->double('discount_value')->nullable();
            $table->text('reason_for_discount')->nullable();
            $table->integer('auto_discount')->nullable();
            $table->double('discount_amount')->nullable();
            $table->double('total_coupon_amount')->nullable();
            $table->string('coupon_uuid')->nullable();
            $table->string('shipping_type')->nullable();
            $table->double('shipping_price')->nullable();
            $table->double('total_vat')->nullable();
            $table->integer('estimated_tax')->nullable();
            $table->integer('mark_as_paid')->default(0);
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('fulfilled_status')->nullable();
            $table->integer('store_pickup')->nullable();
            $table->string('delivery_status')->nullable();
            $table->integer('tracking_status')->nullable();
            $table->decimal('shipping_vat_percent', 20, 2)->nullable();
            $table->decimal('shipping_vat_amount', 20, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};