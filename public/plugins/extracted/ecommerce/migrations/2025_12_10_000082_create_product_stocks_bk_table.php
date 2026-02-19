<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_stocks_bk', function (Blueprint $table) {
            $table->id();
            $table->text('uuid')->nullable();
            $table->text('product_id');
            $table->string('variant');
            $table->string('sku')->nullable();
            $table->double('price', 20, 2)->default(0.00);
            $table->integer('qty')->default(1);
            $table->longText('image')->nullable();
            $table->text('location_id')->nullable();
            $table->text('auth_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_stocks_bk');
    }
};