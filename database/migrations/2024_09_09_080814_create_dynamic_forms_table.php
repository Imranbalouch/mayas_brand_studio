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
        
        Schema::create('dynamic_forms', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('theme_id');
            $table->text('form_name');
            $table->longText('details');
            $table->text('short_code')->unique();
            $table->boolean('is_recaptcha')->default(0);
            $table->longText('language_code')->nullable();
            $table->longText('from_email')->nullable();
            $table->longText('to_email')->nullable();
            $table->longText('submission_message')->nullable();
            $table->longText('redirect_url')->nullable();
            $table->boolean('status')->default(1);
            $table->string('auth_id')->default('1');


            $table->timestamps();

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_forms');
    }
};
