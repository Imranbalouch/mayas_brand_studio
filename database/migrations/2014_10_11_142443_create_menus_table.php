<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu;
use Carbon\Carbon;


return new class extends Migration
{
    
    /**
     * Run the migrations.
    */

    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            
            $table->id(); 
            $table->uuid('uuid')->unique();
            $table->string('name')->unique();
            $table->longText('description')->nullable(); 
            $table->integer('sort_id')->default('1');
            $table->longText('icon')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default('1');
            $table->integer('parent_id')->default('1');
            $table->longText('url')->nullable();
            $table->integer('is_plugin')->default('0');
            $table->integer('is_plugin_active')->default('1');
            $table->integer('is_visible_menu')->default('1');
            $table->timestamps(); 
            $table->softDeletes();
            
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};

