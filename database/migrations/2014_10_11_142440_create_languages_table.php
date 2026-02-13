<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Language;
use Carbon\Carbon;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
           
            $table->id();
            $table->uuid('uuid')->unique(); // Add unique UUID column 
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->longText('flag')->nullable();
            $table->boolean('rtl')->default(0);
            $table->string('auth_id')->default('1');
            $table->integer('status')->default('1');
            $table->integer('is_default')->default('0');
            $table->integer('is_admin_default')->default('0');
            $table->timestamps();
            $table->softDeletes();

        });


        $now = Carbon::now();

        // Insert initial roles 
        $role = Language::insert([
            ['uuid' => Str::uuid(), 'name' => 'English', 'code' => 'us', 'flag' => '/upload_files/flag/1723550553_usa_flag.PNG' , 'rtl' => '0', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27' , 'status' => '1' , 'created_at' => $now, 'updated_at' => $now],
            ['uuid' => Str::uuid(), 'name' => 'Arabic',  'code' => 'sa', 'flag' => '/upload_files/flag/1723552676_uae_flag.PNG' , 'rtl' => '1', 'auth_id' => 'e6efa188-56d9-449c-b85c-75bf1f0d4f27' , 'status' => '1' , 'created_at' => $now, 'updated_at' => $now]
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
