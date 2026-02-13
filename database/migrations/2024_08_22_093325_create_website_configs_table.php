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
        Schema::create('website_configs', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('site_name')->nullable(); // Website name
            $table->longText('site_logo')->nullable(); // Logo path or URL
            $table->longText('site_favicon')->nullable(); // Favicon path or URL
            $table->longText('meta_title')->nullable(); // Meta title
            $table->longText('meta_description')->nullable(); // Meta description
            $table->longText('meta_keywords')->nullable(); // Meta keywords
            $table->longText('contact_email')->nullable(); // Contact email
            $table->longText('contact_phone')->nullable(); // Contact phone number
            $table->longText('contact_address')->nullable(); // Contact address
            $table->longText('facebook_url')->nullable(); // Facebook page URL
            $table->longText('twitter_url')->nullable(); // Twitter handle URL
            $table->longText('linkedin_url')->nullable(); // LinkedIn profile URL
            $table->longText('instagram_url')->nullable(); // Instagram profile URL
            $table->longText('youtube_url')->nullable(); // YouTube channel URL
            $table->longText('footer_text')->nullable(); // Footer text
            $table->longText('google_analytics_code')->nullable(); // Google Analytics code
            $table->integer('maintenance_mode')->default(0); // Maintenance mode flag
            $table->string('auth_id')->default('1');
            $table->timestamps(); // Created and updated timestamps
            $table->softDeletes(); // Soft delete column

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_configs');
    }
};
