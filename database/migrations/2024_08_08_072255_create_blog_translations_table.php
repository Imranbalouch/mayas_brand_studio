<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_translations', function (Blueprint $table) {
           
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('blog_id');
            $table->integer('language_id');
            $table->string('blog_name');
            $table->longText('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('thumbnail_image')->nullable();
            $table->string('author')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_translations');
    }
};
