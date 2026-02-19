<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_filters', function (Blueprint $table) {
            $table->id();
            $table->text('uuid');
            $table->text('auth_id');
            $table->text('search_type')->nullable();
            $table->text('name')->nullable();
            $table->timestamp('updated_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_filters');
    }
};