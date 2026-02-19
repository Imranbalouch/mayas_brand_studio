<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->text('uuid')->nullable();
            $table->string('category_uuid');
            $table->string('language_id')->nullable();
            $table->string('name', 50)->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('level')->nullable();
            $table->integer('order_level')->nullable();
            $table->integer('featured')->nullable();
            $table->longText('banner')->nullable();
            $table->longText('icon')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('og_title')->nullable();
            $table->longText('og_description')->nullable();
            $table->longText('og_image')->nullable();
            $table->longText('x_title')->nullable();
            $table->longText('x_description')->nullable();
            $table->longText('x_image')->nullable();
            $table->string('lang', 100);
            $table->text('auth_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('category_translations');
    }
};