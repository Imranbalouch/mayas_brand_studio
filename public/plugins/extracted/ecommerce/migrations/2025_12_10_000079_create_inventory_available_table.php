<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_available', function (Blueprint $table) {
            $table->id();
            $table->text('uuid')->nullable();
            $table->integer('inventory_id');
            $table->text('status');
            $table->text('reason');
            $table->text('qty');
            $table->text('auth_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_available');
    }
};