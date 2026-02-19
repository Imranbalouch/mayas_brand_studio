<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('custom_item', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('auth_id');
            $table->string('order_id');
            $table->string('item_name');
            $table->double('price');
            $table->integer('qty');
            $table->integer('item_taxable')->nullable();
            $table->integer('item_physical_product')->nullable();
            $table->double('item_weight')->nullable();
            $table->string('unit')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_item');
    }
};