<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->text('uuid')->nullable();
            $table->integer('parent_id')->nullable()->default(0);
            $table->integer('level')->default(0);
            $table->string('name')->nullable();
            $table->integer('order_level')->default(0);
            $table->decimal('commision_rate', 8, 2)->default(0.00);
            $table->longText('banner')->nullable();
            $table->longText('icon')->nullable();
            $table->string('cover_image', 100)->nullable();
            $table->integer('featured')->default(0);
            $table->integer('top')->default(0);
            $table->integer('digital')->default(0);
            $table->string('slug')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('og_title')->nullable();
            $table->longText('og_description')->nullable();
            $table->longText('og_image')->nullable();
            $table->longText('x_title')->nullable();
            $table->longText('x_description')->nullable();
            $table->longText('x_image')->nullable();
            $table->text('auth_id')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};