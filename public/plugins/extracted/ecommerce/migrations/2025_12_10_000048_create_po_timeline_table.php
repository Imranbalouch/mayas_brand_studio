<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('po_timeline', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('po_id');
            $table->longText('message');
            $table->integer('status')->nullable();
            $table->text('auth_id');
            $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('po_timeline');
    }
};