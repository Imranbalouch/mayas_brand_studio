<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warehouse_location_translations', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->index();
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->text('lang')->nullable();
            $table->string('location_name');
            $table->string('location_address')->nullable();
            $table->longText('description')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->integer('featured')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_location_translations');
    }
};