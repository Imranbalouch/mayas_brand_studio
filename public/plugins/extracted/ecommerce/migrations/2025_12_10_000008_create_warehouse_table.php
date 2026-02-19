<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('uuid', 36)->unique();
            $table->string('warehouse_name');
            $table->string('prefix')->nullable();
            $table->integer('featured')->nullable()->default(0);
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};