<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->string('market_name')->nullable();
            $table->string('slug');
            $table->string('country_names')->nullable();
            $table->text('country_images')->nullable();
            $table->text('language_id');
            $table->text('country_id');
            $table->text('currency_id')->nullable();
            $table->text('tax_id')->nullable();
            $table->string('price_adjustment');
            $table->integer('percentage');
            $table->string('logo')->nullable();
            $table->integer('order_level')->nullable();
            $table->longText('description')->nullable();
            $table->longText('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->longText('og_title')->nullable();
            $table->longText('og_description')->nullable();
            $table->longText('og_image')->nullable();
            $table->longText('x_title')->nullable();
            $table->longText('x_description')->nullable();
            $table->longText('x_image')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->integer('featured')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('uuid');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};