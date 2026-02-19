<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('auth_id');
            $table->string('company_name');
            $table->string('company_id')->nullable();
            $table->text('main_contact_id')->nullable();
            $table->string('billing_address_id')->nullable();
            $table->string('shipping_address_id')->nullable();
            $table->string('location_id')->nullable();
            $table->text('catalogs_id')->nullable();
            $table->text('payment_terms_id')->nullable();
            $table->double('deposit')->nullable();
            $table->integer('ship_to_address')->nullable();
            $table->text('order_submission')->nullable();
            $table->string('tax_id')->nullable();
            $table->text('tax_setting')->nullable();
            $table->integer('approved')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
