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
        Schema::create('attribute_categories', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('attributes')->onDelete('cascade');
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
        Schema::table('attribute_categories', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['category_id']);
            $table->dropForeign(['attribute_id']);
        });

        Schema::dropIfExists('attribute_categories');
    }
};
