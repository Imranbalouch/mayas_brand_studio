<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->text('uuid')->nullable();
            $table->bigInteger('product_id');
            $table->string('name', 200)->nullable();
            $table->string('unit', 20)->nullable();
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->integer('language_id')->nullable();
            $table->string('lang', 100);
            $table->integer('status')->default(1);
            $table->text('auth_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_translations');
    }
};