<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu_translation;
use Carbon\Carbon;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_translations', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->longText('icon')->nullable();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('language_id');
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
        Schema::dropIfExists('menu_translations');
    }
};
