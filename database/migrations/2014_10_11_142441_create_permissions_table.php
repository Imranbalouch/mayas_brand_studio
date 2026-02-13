<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str; // To generate UUID
use App\Models\Permission;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
           
            $table->id();
            $table->uuid('uuid')->unique(); // Add unique UUID column 
            $table->string('permission')->unique(); // Make 'permission' column unique
            $table->string('auth_id')->default('1');
            $table->integer('status')->default('1');
            $table->timestamps(); 
            $table->softDeletes();
            
        });

        $now = Carbon::now();

        // Insert initial roles 
        $role = Permission::insert([
            ['uuid' => Str::uuid(), 'permission' => 'Add',    'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'permission' => 'Edit',   'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'permission' => 'Update', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'permission' => 'Delete', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'permission' => 'View', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'permission' => 'ViewGlobal', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27'  , 'created_at' => $now, 'updated_at' => $now]
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
