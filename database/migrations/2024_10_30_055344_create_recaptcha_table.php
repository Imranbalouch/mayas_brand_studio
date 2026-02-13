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
        Schema::create('recaptcha', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('site_key')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('version')->nullable();
            $table->string('auth_id')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recaptcha');
    }
};
