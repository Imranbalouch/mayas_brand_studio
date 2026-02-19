<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ti_receiving', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('ti_id');
            $table->string('ti_item_id');
            $table->string('product_id');
            $table->string('variant_id');
            $table->string('sku')->nullable();
            $table->integer('accept_qty')->default(0);
            $table->integer('reject_qty')->default(0);
            $table->string('received_date');
            $table->string('auth_id');
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ti_receiving');
    }
};