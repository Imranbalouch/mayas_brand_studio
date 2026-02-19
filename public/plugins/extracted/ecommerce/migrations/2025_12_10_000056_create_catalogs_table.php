<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->index();
            $table->string('catalog')->nullable();
            $table->string('slug')->index();
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
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->text('company_location')->nullable();
            $table->string('price_adjustment')->nullable();
            $table->text('percentage')->nullable();
            $table->string('currency')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('catalogs');
    }
};