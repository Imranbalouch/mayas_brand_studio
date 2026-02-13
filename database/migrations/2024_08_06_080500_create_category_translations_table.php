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
        Schema::create('category_translations', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('category_id');
            $table->foreignId('language_id');
            $table->string('category');
            $table->string('auth_id')->default('1');
            $table->integer('status')->default('1');
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_translations');
    }
};
