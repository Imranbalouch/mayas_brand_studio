<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('transaction_id')->nullable();
            $table->string('order_id');
            $table->double('amount');
            $table->text('description');
            $table->text('response_data')->nullable();
            $table->text('transaction_url')->nullable();
            $table->string('status')->nullable();
            $table->string('response_message')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_payments');
    }
};