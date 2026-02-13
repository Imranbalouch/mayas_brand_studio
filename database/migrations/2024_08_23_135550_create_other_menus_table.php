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
        Schema::create('other_menus', function (Blueprint $table) {
           
            $table->id(); 
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->longText('icon')->nullable();
            $table->longText('url')->nullable();
            $table->integer('status')->default('1');
            $table->string('auth_id')->default('1');
            $table->integer('sort_id')->default('1');
            $table->integer('parent_id')->default('0');
            $table->longText('menu_detail')->nullable();
            $table->longText('parent_array')->nullable();
            $table->longText('child_array')->nullable();
            $table->string('shortcode')->nullable();
            $table->longText('html')->nullable();
            $table->timestamps(); 
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_menus');
    }
};
