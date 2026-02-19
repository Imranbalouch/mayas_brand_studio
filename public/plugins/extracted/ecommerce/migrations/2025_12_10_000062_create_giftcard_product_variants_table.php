<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('giftcard_product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('auth_id')->nullable();
            $table->string('giftcard_product_id');
            $table->string('variant');
            $table->string('sku')->nullable();
            $table->float('price');
            $table->integer('qty')->nullable();
            $table->longText('image')->nullable();
            $table->text('location_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giftcard_product_variants');
    }
};