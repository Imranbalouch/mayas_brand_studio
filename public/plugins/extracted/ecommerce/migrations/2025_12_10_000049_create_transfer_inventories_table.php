<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transfer_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('ti_number');
            $table->string('origin_location_id')->nullable();
            $table->string('destination_location_id');
            $table->string('payment_term_id')->nullable();
            $table->string('supplier_currency_id');
            $table->text('estimated_date')->nullable();
            $table->string('ship_carrier_id')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('reference_number')->nullable();
            $table->text('note_to_supplier')->nullable();
            $table->text('tags')->nullable();
            $table->string('status')->nullable();
            $table->string('total_tax');
            $table->string('total_amount');
            $table->string('auth_id');
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transfer_inventories');
    }
};