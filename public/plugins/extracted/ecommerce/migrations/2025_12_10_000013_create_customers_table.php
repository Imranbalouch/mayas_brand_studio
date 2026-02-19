<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->nullable();
            $table->string('address_id')->nullable();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique()->comment('email must be unique');
            $table->string('phone', 50)->nullable()->unique()->comment('phone must be unique');
            $table->longText('image')->nullable();
            $table->string('password')->nullable();
            $table->string('language')->nullable();
            $table->integer('market_emails')->default(0)->comment('is one so use for market emails');
            $table->integer('market_sms')->default(0)->comment('is one so use for market sms');
            $table->text('address')->nullable();
            $table->text('country')->nullable();
            $table->text('company')->nullable();
            $table->text('tax_setting')->nullable();
            $table->text('apart_suite')->nullable();
            $table->text('city')->nullable();
            $table->text('postal_code')->nullable();
            $table->text('notes')->nullable();
            $table->text('tags')->nullable();
            $table->text('auth_id')->nullable();
            $table->integer('status')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};