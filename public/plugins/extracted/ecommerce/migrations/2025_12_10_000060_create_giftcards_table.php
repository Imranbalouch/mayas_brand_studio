<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('giftcards', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->index();
            $table->string('giftcard')->nullable();
            $table->string('code')->nullable();
            $table->integer('value')->nullable();
            $table->integer('balance')->nullable()->default(0);
            $table->text('customer_id')->nullable();
            $table->longText('note')->nullable();
            $table->dateTime('expiry_date')->nullable();
            $table->longText('og_title')->nullable();
            $table->longText('og_description')->nullable();
            $table->longText('og_image')->nullable();
            $table->longText('x_title')->nullable();
            $table->longText('x_description')->nullable();
            $table->longText('x_image')->nullable();
            $table->string('auth_id')->default('1');
            $table->string('status')->nullable();
            $table->integer('featured')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giftcards');
    }
};
