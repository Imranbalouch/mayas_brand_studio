<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id();
            $table->text('uuid')->nullable();
            $table->string('auth_id');
            $table->string('di_id');
            $table->string('variant_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('collection_id')->nullable();
            $table->string('countries_id')->nullable();
            $table->double('value', 20, 2)->nullable()->default(0.00);
            $table->string('method')->nullable();
            $table->string('type')->nullable();
            $table->string('customer_buy_product_id')->nullable();
            $table->string('customer_buy_variant_id')->nullable();
            $table->string('customer_get_product_id')->nullable();
            $table->string('customer_get_variant_id')->nullable();
            $table->string('customer_buy_collection_id')->nullable();
            $table->string('customer_get_collection_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_discounts');
    }
};