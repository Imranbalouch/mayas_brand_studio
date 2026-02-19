<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('language_id');
            $table->string('location_name');
            $table->string('country')->nullable();
            $table->string('apartment')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->text('location_address')->nullable();
            $table->string('contact_number')->nullable();
            $table->integer('manager_id')->nullable();
            $table->integer('featured')->nullable()->default(0);
            $table->integer('is_default')->default(0);
            $table->string('capacity')->nullable();
            $table->integer('current_stock_level')->nullable();
            
            $table->index('uuid');
            $table->index('warehouse_id');
            $table->index('language_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};