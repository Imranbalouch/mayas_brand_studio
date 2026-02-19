<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_commited', function (Blueprint $table) {
            $table->id();
            $table->text('uuid');
            $table->integer('inventory_id');
            $table->integer('product_id');
            $table->string('sku');
            $table->text('qty');
            $table->integer('order_id');
            $table->text('order_code');
            $table->text('status')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_commited');
    }
};