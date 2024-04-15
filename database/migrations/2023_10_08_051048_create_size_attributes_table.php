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
        Schema::create('size_attributes', function (Blueprint $table) {
            $table->id();
            $table->integer('size_id')->nullable();
            $table->string('attribute')->nullable();
            $table->tinyInteger('status')->default(0)->comment('1=yes,0=no');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('size_attributes');
    }
};
