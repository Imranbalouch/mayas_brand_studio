<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('master_import_product_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('auth_id');
            $table->text('excel_file_name');
            $table->string('queue_status')->default('Pending');
            $table->integer('product_status')->nullable()->default(0);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('master_import_product_logs');
    }
};
