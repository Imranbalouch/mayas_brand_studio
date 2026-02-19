<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('company');
            $table->string('country_id');
            $table->text('address')->nullable();
            $table->text('apart_suite')->nullable();
            $table->text('city')->nullable();
            $table->text('postal_code')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->text('phone_number')->nullable();
            $table->integer('status')->default(0);
            $table->string('auth_id');
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('suppliers');
    }
};