<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name');
            $table->text('theme_logo')->nullable();
            $table->text('theme_path')->nullable();
            $table->text('short_description')->nullable();
            $table->text('thumbnail_img')->nullable();
            $table->string('version')->default('v1')->nullable();
            $table->text('css_link')->nullable();
            $table->text('js_link')->nullable();
            $table->text('css_file')->nullable();
            $table->text('js_file')->nullable();
            $table->string('auth_id')->default('1');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
