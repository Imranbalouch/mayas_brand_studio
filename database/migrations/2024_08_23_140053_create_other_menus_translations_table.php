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
        Schema::create('other_menus_translations', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->longText('icon')->nullable();
            $table->longText('menudetail')->nullable();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('language_id');
            $table->integer('status')->default('1');
            $table->string('auth_id')->default('1');
            $table->timestamps();
            $table->softDeletes();

        });

    }

    /**
     * Reverse the migrations.
    */

    public function down(): void
    {
        Schema::dropIfExists('other_menus_translations');
    }
};
