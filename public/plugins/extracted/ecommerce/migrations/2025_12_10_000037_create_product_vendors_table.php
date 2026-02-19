<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_vendors', function (Blueprint $table) {
            $table->id();
            $table->text('vendor_uuid');
            $table->text('product_uuid')->nullable();
            $table->text('name')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_vendors');
    }
};
