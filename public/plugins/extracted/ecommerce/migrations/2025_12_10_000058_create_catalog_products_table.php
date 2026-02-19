<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('catalog_id');
            $table->string('product_id');
            $table->string('variant_id')->nullable();
            $table->integer('price')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('catalog_products');
    }
};