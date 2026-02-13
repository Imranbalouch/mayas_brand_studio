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
        Schema::create('user_special_permissions', function (Blueprint $table) {
            
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique(); 
            $table->integer('user_id');
            $table->integer('permission_id');
            $table->integer('menu_id');
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
        Schema::dropIfExists('user_special_permissions');
    }
};