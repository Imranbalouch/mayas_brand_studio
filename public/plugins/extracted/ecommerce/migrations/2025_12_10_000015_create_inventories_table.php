<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->increments('id');
            $table->text('stock_id');
            $table->text('location_id');
            $table->text('product_id');
            $table->text('uuid')->nullable();
            $table->text('sku')->nullable();
            $table->decimal('price', 20, 2)->default(0.00);
            $table->integer('qty')->default(0);
            $table->text('status')->comment('available adjust and unavailable');
            $table->text('reason')->comment('Reason:correction,count,received,return restock,damaged,theft loss,promotion or donation');
            $table->text('auth_id')->nullable();
            $table->string('order_id')->nullable();
            $table->string('order_code')->nullable();
            $table->string('po_id')->nullable();
            $table->string('po_item_id')->nullable();
            $table->string('ti_id')->nullable();
            $table->string('ti_item_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};