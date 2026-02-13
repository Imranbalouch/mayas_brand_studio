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
        Schema::create('smtp', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('host');
            $table->integer('port');
            $table->string('encryption')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('auth_id')->default('1');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp');
    }
};
