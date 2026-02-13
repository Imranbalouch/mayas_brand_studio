<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // To generate UUID
use App\Models\Role;
use Carbon\Carbon;


return new class extends Migration
{

    /** 
     * Run the migrations.
    */

    public function up(): void
    {
        
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // Add unique UUID column
            $table->string('role')->unique(); // Make 'role' column unique
            $table->string('auth_id')->default('1');
            $table->integer('status')->default('1');
            $table->timestamps(); // Create 'created_at' and 'updated_at' columns
            $table->softDeletes(); // Create 'deleted_at' column for soft deletes
        }); 

        
        $now = Carbon::now();

        // Insert initial roles 
        $role = Role::insert([
            ['uuid' => Str::uuid(), 'role' => 'Super Admin', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'role' => 'Admin', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'role' => 'Vendor', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'role' => 'Customer', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'role' => 'User', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now]
        ]); 


    }

    /**
     * Reverse the migrations.
     */
    
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
