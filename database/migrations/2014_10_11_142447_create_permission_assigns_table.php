<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // To generate UUID
use App\Models\Permission_assign;
use Carbon\Carbon;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission_assigns', function (Blueprint $table) {

            $table->id(); // Auto-incrementing primary key
            $table->uuid('uuid')->unique(); // Add unique UUID column 
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('menu_id');
            $table->string('auth_id')->default('1');
            $table->integer('status')->default('1');
            $table->timestamps(); // Adds created_at and updated_at columns
            $table->softDeletes(); // Adds deleted_at column for soft deletes


            // Foreign keys
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_assigns');
    }
    
};
