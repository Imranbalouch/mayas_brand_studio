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
        Schema::create('whatsapp', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number');
            $table->text('message')->nullable();
            $table->string('auth_id')->default('1');
            $table->string('whatsapp_logo')->nullable();
            $table->longText('html_code')->nullable();
            $table->longText('custom_css')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp');
    }
};
