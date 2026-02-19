<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('collection_translation', function (Blueprint $table) {
            $table->id();
            $table->text('uuid')->nullable();
            $table->string('collection_uuid');
            $table->string('language_id');
            $table->string('lang');
            $table->string('name', 50)->nullable();
            $table->longText('description')->nullable();
            $table->longText('image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('auth_id')->nullable();
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->useCurrent()->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('collection_translation');
    }
};