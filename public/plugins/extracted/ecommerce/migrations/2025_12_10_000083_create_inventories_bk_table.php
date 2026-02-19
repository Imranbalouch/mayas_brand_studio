<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventories_bk', function (Blueprint $table) {
            $table->id();
            $table->text('stock_id');
            $table->text('location_id');
            $table->text('product_id');
            $table->text('uuid')->nullable();
            $table->text('sku')->nullable();
            $table->double('price', 20, 2)->default(0.00);
            $table->integer('qty')->default(0);
            $table->text('status');
            $table->text('reason');
            $table->text('auth_id')->nullable();
            $table->string('po_id')->nullable();
            $table->string('po_item_id')->nullable();
            $table->string('ti_id')->nullable();
            $table->string('ti_item_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventories_bk');
    }
};