<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->string('plugin_id');
            $table->text('url')->nullable();
            $table->text('publishable_api_key')->nullable();
            $table->text('secret_api_key')->nullable();
            $table->integer('stc')->nullable();
            $table->string('auth_id')->default('1');
            $table->timestamps();
            
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway');
    }
};