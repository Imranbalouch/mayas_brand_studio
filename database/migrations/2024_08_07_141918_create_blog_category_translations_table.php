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
        Schema::create('blog_category_translations', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique(); // Add unique UUID column
            $table->integer('blog_category_id');
            $table->integer('language_id');
            $table->string('category');
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Additional indexes
            $table->unique(['blog_category_id', 'language_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_category_translations');
    }
};
