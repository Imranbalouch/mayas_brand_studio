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
        Schema::create('filemanagers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('file_original_name', 800)->nullable();
            $table->string('file_name', 800)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('extension', 30)->nullable();
            $table->string('type', 30)->nullable();
            $table->string('external_link', 800)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filemanager');
    }
};
