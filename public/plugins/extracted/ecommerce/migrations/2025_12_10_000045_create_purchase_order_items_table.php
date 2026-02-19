<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('po_id');
            $table->string('product_id');
            $table->string('variant_id')->nullable();
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->double('unit_price', 20, 2);
            $table->double('tax', 20, 2);
            $table->double('total_amount', 20, 2);
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_order_items');
    }
};