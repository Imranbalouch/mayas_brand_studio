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
        Schema::create('blogs', function (Blueprint $table) {
           
            $table->id();
            $table->uuid('uuid')->unique();
            $table->integer('category_id');
            $table->string('blog_name');
            $table->string('slug')->unique();
            $table->longText('tags')->nullable();
            $table->longText('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->longText('thumbnail_image')->nullable();
            $table->longText('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->longText('meta_keywords')->nullable();
            $table->string('author')->nullable();
            $table->longText('post_date')->nullable();
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
        Schema::dropIfExists('blogs');
    }
};
