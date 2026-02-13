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
        Schema::create('widget_fields', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('widget_id');
            $table->string('field_name');
            $table->string('field_id');
            $table->string('field_type');
            $table->string('field_options')->nullable();
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
        Schema::dropIfExists('widget_fields');
    }
};
