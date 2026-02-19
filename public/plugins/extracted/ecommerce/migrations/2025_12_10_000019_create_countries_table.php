<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id(); 
            $table->char('uuid', 36); 
            $table->string('auth_id');
            $table->string('code', 2); 
            $table->string('name', 100); 
            $table->text('image')->nullable(); 
            $table->integer('status')->default(1); 
            $table->integer('is_default')->nullable(); 
            $table->timestamps(); 
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};