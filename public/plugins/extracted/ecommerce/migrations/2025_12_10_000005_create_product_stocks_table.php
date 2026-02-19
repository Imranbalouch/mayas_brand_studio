<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->text('uuid')->nullable();
            $table->text('product_id');
            $table->string('variant');
            $table->string('sku')->nullable();
            $table->decimal('price', 20, 2)->default(0.00);
            $table->integer('qty')->default(1);
            $table->longText('image')->nullable();
            $table->string('variant_sku')->nullable();
            $table->decimal('cost_per_item', 20, 2)->default(0.00);
            $table->decimal('compare_price', 20, 2)->default(0.00);
            $table->string('barcode')->nullable();
            $table->string('hs_code')->nullable();
            $table->text('location_id')->nullable();
            $table->text('auth_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};