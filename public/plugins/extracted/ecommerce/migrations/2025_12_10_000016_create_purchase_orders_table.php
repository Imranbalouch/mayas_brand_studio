<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('po_number');
            $table->string('supplier_id')->nullable();
            $table->string('warehouse_id');
            $table->string('payment_term_id')->nullable();
            $table->string('supplier_currency_id');
            $table->text('ship_date')->nullable();
            $table->string('ship_carrier_id')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('reference_number')->nullable();
            $table->text('note_to_supplier')->nullable();
            $table->text('tags')->nullable();
            $table->string('status')->nullable();
            $table->text('cost_summary')->nullable();
            $table->string('total_tax');
            $table->string('total_shipping')->default('0');
            $table->string('total_amount');
            $table->string('auth_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};