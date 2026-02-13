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
        Schema::create('attribute_translations', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique(); // Add unique UUID column
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            $table->string('attribute_name')->unique();
            $table->longText('description')->nullable();
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
        Schema::dropIfExists('attribute_translations');
    }
};
