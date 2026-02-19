<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_markets', function (Blueprint $table) {
            $table->text('product_uuid')->nullable();
            $table->text('market_uuid')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_markets');
    }
};
