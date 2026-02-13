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
        Schema::create('categories', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique(); // Add unique UUID column
            $table->string('category');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();
            $table->longText('meta_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->string('parent_id')->default('0');
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
        Schema::dropIfExists('categories');
    }
};
