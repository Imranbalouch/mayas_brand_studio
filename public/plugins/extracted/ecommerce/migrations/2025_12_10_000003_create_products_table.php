<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumText('uuid')->nullable();
            $table->string('giftcard_product_id')->nullable();
            $table->string('name', 255);
            $table->mediumText('auth_id')->nullable();
            $table->mediumText('category_id')->nullable();
            $table->mediumText('brand_id')->nullable();
            $table->mediumText('warehouse_location_id')->nullable();
            $table->mediumText('thumbnail_img')->nullable();
            $table->longText('images')->nullable();
            $table->string('tags', 500)->nullable();
            $table->integer('compare_price')->nullable();
            $table->integer('cost_per_item')->nullable();
            $table->integer('profit_price')->nullable();
            $table->integer('margin_price')->nullable();
            $table->string('hs_code')->nullable();
            $table->string('sku')->nullable();
            $table->mediumText('country_id')->nullable();
            $table->timestamp('published_date_time')->nullable();
            $table->longText('description')->nullable();
            $table->mediumText('short_description')->nullable();
            $table->decimal('unit_price', 20, 2);
            $table->string('attributes', 1000)->default('[]');
            $table->longText('choice_options')->nullable();
            $table->integer('todays_deal')->default(0);
            $table->mediumText('vendor')->nullable();
            $table->mediumText('market_id')->nullable();
            $table->mediumText('sale_channel_id')->nullable();
            $table->integer('published')->default(1);
            $table->boolean('approved')->default(true);
            $table->string('stock_visibility_state', 10)->default('quantity');
            $table->boolean('cash_on_delivery')->default(false)->comment('1 = On, 0 = Off');
            $table->integer('featured')->default(0);
            $table->integer('current_stock')->default(0);
            $table->string('unit', 20)->nullable();
            $table->decimal('weight', 8, 2)->default(0.00);
            $table->integer('min_qty')->default(1);
            $table->decimal('discount', 20, 2)->nullable();
            $table->string('discount_type', 10)->nullable();
            $table->integer('discount_start_date')->nullable();
            $table->integer('discount_end_date')->nullable();
            $table->decimal('tax', 20, 2)->nullable();
            $table->string('tax_type', 10)->nullable();
            $table->string('shipping_type', 20)->default('flat_rate');
            $table->decimal('shipping_cost', 20, 2)->default(0.00);
            $table->longText('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->longText('og_title')->nullable();
            $table->longText('og_description')->nullable();
            $table->longText('og_image')->nullable();
            $table->longText('x_title')->nullable();
            $table->longText('x_description')->nullable();
            $table->longText('x_image')->nullable();
            $table->string('meta_img')->nullable();
            $table->string('pdf')->nullable();
            $table->longText('slug');
            $table->decimal('rating', 8, 2)->default(0.00);
            $table->string('barcode')->nullable();
            $table->integer('digital')->default(0);
            $table->mediumText('type')->nullable();
            $table->string('template_product')->nullable();
            $table->integer('auction_product')->default(0);
            $table->integer('wholesale_product')->default(0);
            $table->integer('product_top')->default(1)->comment('1 status on and 2 off');
            $table->integer('sort')->nullable()->default(0);
            $table->string('tax_enabled')->nullable();
            $table->string('inventory_track_enabled')->nullable();
            $table->string('selling_stock_enabled')->nullable();
            $table->string('sku_barcode_enabled')->nullable();
            $table->string('physical_product_enabled')->nullable();
            $table->string('varient_market_location')->nullable();
            $table->longText('varient_data')->nullable();
            $table->longText('varient_data_view')->nullable();
            $table->string('location_stock')->nullable();
            $table->string('product_type')->nullable();
            $table->string('vat_id')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            
            $table->index('name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};