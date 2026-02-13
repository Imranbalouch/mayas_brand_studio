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
        Schema::create('message_key_translations', function (Blueprint $table) {
            
            $table->id(); // id (int)
            $table->uuid('uuid')->unique(); // uuid (unique)
            $table->unsignedBigInteger('key_id'); // key_id
            $table->unsignedBigInteger('language_id'); // language_id
            $table->longText('translation'); // translation
            $table->tinyInteger('status')->default(1); // status
            $table->timestamps(); // created_at, updated_at
            $table->softDeletes(); // deleted_at

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_key_translations');
    }
};
