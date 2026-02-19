<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_timeline', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('auth_id')->nullable();
            $table->string('order_id');
            $table->longText('message');
            $table->string('status')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_timeline');
    }
};