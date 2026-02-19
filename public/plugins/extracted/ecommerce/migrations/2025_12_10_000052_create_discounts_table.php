<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name')->nullable();
            $table->string('method')->nullable();
            $table->string('code')->nullable();
            $table->string('discount_type')->nullable();
            $table->string('type')->nullable();
            $table->string('value')->nullable();
            $table->string('applies_to')->nullable();
            $table->string('applies_to_value')->nullable();
            $table->string('requirement_type')->nullable();
            $table->string('requirement_value')->nullable();
            $table->string('eligibility')->nullable();
            $table->string('eligibility_value')->nullable();
            $table->integer('minimum_shopping')->nullable();
            $table->integer('maximum_discount_amount')->nullable();
            $table->string('uses_customer_limit')->nullable();
            $table->string('apply_on_pos')->nullable();
            $table->string('uses_limit')->nullable();
            $table->string('combination_type')->nullable();
            $table->date('start_date')->nullable();
            $table->time('start_time')->nullable();
            $table->date('end_date')->nullable();
            $table->time('end_time')->nullable();
            $table->text('specific_customer')->nullable();
            $table->text('customer_segments')->nullable();
            $table->integer('shipping_rate')->nullable();
            $table->double('exclude_shipping_rates')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('auth_id')->nullable();
            $table->timestamp('created_at')->useCurrent()->nullable();
            $table->timestamp('updated_at')->useCurrent();
            $table->string('customer_buys')->nullable();
            $table->integer('customer_buys_quantity')->nullable();
            $table->integer('customer_buys_amount')->nullable();
            $table->integer('customer_get_quantity')->nullable();
            $table->integer('customer_get_percentage')->nullable();
            $table->integer('customer_get_amount_off_each')->nullable();
            $table->integer('customer_get_free')->nullable();
            $table->integer('maximum_number_per_order')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('discounts');
    }
};