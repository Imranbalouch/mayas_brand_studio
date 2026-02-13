<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Carbon\Carbon;


return new class extends Migration
{
    /**
     * Run the migrations.
    */

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            
            $table->id();
            $table->uuid('uuid')->unique(); // Add unique UUID column
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('password'); 
            $table->longText('image')->nullable();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->unsignedBigInteger('role_id')->nullable(); // Adjust placement as needed
            $table->timestamp('email_verified_at')->nullable(); 
            $table->string('ip'); 
            $table->string('auth_id')->default('1');
            $table->integer('status')->default('1');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

        });

        $now = Carbon::now();
        $password = bcrypt('@SuperAdmin!23#');
        $ipAddress = getHostByName(getHostName());

        // Insert initial roles 
        $role = User::insert([
            ['uuid' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27', 'first_name' => 'Super', 'last_name' => 'Admin', 'email' => 'custombackend@gmail.com', 'password' => $password, 'image' => '', 'role_id' => '1', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27' , 'email_verified_at' => null , 'ip' => $ipAddress, 'created_at' => $now, 'updated_at' => $now]
        ]); 

    }


    /**
     * Reverse the migrations.
    */


    public function down(): void
    {
        Schema::dropIfExists('users');
    }

};
