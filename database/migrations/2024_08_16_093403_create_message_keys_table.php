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
        Schema::create('message_keys', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique(); // uuid (unique)
            $table->string('key'); // key
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
        Schema::dropIfExists('message_keys');
    }

};
