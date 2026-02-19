<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('auth_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('company_id')->nullable();
            $table->string('country_id')->nullable();
            $table->string('type');
            $table->text('country')->nullable();
            $table->string('country_uuid')->nullable();
            $table->string('city_uuid')->nullable();
            $table->text('address_first_name')->nullable();
            $table->text('address_last_name')->nullable();
            $table->string('address_email')->nullable();
            $table->text('company')->nullable();
            $table->text('address')->nullable();
            $table->text('apartment')->nullable();
            $table->text('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('address_phone')->nullable();
            $table->integer('is_default')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address');
    }
};