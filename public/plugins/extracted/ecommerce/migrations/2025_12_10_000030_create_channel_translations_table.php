<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('channel_translations', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->index();
            $table->unsignedBigInteger('channel_id');
            $table->unsignedBigInteger('language_id')->nullable();
            $table->text('lang')->nullable();
            $table->string('channel');
            $table->string('logo')->nullable();
            $table->longText('description')->nullable();
            $table->longText('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('channel_translations');
    }
};