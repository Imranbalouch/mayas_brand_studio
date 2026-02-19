<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_temps', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('name');
            $table->string('slug');
            $table->string('warehouse_location');
            $table->double('unit_price', 20, 2)->nullable();
            $table->integer('compare_price')->nullable();
            $table->integer('cost_price')->nullable();
            $table->integer('current_stock')->nullable();
            $table->longText('description')->nullable();
            $table->longText('categories')->nullable();
            $table->double('weight', 8, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->text('vendor_2')->nullable();
            $table->text('country_name')->nullable();
            $table->text('product_type')->nullable();
            $table->text('physical_product')->nullable();
            $table->text('tags')->nullable();
            $table->string('channels')->nullable();
            $table->string('markets')->nullable();
            $table->string('hscode')->nullable();
            $table->string('collections');
            $table->text('thumbnail_img')->nullable();
            $table->text('variant_image')->nullable();
            $table->string('variant_price')->nullable();
            $table->text('option1_name')->nullable();
            $table->text('option1_value')->nullable();
            $table->text('option2_name')->nullable();
            $table->text('option2_value')->nullable();
            $table->text('option3_name')->nullable();
            $table->text('option3_value')->nullable();
            $table->text('master_import_uuid')->nullable();
            $table->string('status')->nullable()->default('Pending');
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_temps');
    }
};
