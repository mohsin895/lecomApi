<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email',228)->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('social_id')->nullable();
            $table->date('dob')->nullable();
            $table->string('avatar',1000)->nullable();
            $table->string('is_verify')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Active,2=Inactive,0=Deleted');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sellers');
    }
}
