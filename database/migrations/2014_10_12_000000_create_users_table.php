<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name', 100);
            $table->string('email')->unique();
            $table->string('access_token', 100);
            $table->string('password');
            $table->string('phone', 30);
            $table->timestamp('birthday')->nullable();
            $table->string('address', 100)->nullable();
            $table->string('avatar', 100)->default('no-user.jpg');
            $table->boolean('active', 1)->default(0);
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
