<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_collections', function (Blueprint $table) {
            $table->integer('product_id');
            $table->text('product_uuid')->nullable();
            $table->integer('collection_id');
            $table->text('collection_uuid')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_collections');
    }
};