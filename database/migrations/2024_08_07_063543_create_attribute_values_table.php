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
        Schema::create('attribute_values', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('language_id');
            $table->string('value');
            $table->string('color_code')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
