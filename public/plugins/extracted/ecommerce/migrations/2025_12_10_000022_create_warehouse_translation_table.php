<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warehouse_translation', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('language_id')->index();
            $table->text('lang')->nullable();
            $table->string('warehouse_name');
            $table->string('prefix')->nullable();
            $table->longText('description')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouse_translation');
    }
};