<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->increments('id');
            $table->text('uuid')->nullable();
            $table->string('name', 50)->nullable();
            $table->longText('description')->nullable();
            $table->integer('featured')->default(0);
            $table->integer('top')->default(0);
            $table->string('slug')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('og_title')->nullable();
            $table->longText('og_description')->nullable();
            $table->text('channel_uuid')->nullable();
            $table->string('condition_status')->nullable();
            $table->text('conditions')->nullable();
            $table->integer('smart')->nullable();
            $table->longText('image')->nullable();
            $table->longText('og_image')->nullable();
            $table->longText('x_title')->nullable();
            $table->longText('x_description')->nullable();
            $table->longText('x_image')->nullable();
            $table->text('auth_id')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('published_datetime')->nullable();
            
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};