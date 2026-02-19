<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->text('uuid')->nullable();
            $table->string('name', 50)->nullable();
            $table->integer('order_level')->default(0);
            $table->longText('icon')->nullable();
            $table->integer('featured')->default(0);
            $table->text('auth_id')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->useCurrent()->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('channels');
    }
};