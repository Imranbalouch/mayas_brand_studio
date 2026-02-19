<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('giftcard_product', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('auth_id');
            $table->string('title');
            $table->longText('description')->nullable();
            $table->integer('status')->nullable()->default(0);
            $table->text('media')->nullable();
            $table->string('short_desc')->nullable();
            $table->string('page_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('url_handle')->nullable();
            $table->dateTime('published_date')->nullable();
            $table->string('theme_template')->nullable();
            $table->string('giftcard_template')->nullable();
            $table->string('type')->nullable();
            $table->string('tags')->nullable();
            $table->string('vendor')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('giftcard_product');
    }
};
