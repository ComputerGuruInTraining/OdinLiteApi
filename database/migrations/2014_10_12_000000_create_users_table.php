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
            $table->string('first_name');
            $table->string('last_name');
            $table->timestamp('dob')->nullable();
            $table->string('gender');
            $table->integer('mobile');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('company_id')->unsigned();//fk
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('company_id')
            	->references('id')->on('companies');
            	//->onDelete('cascade');
            $table->softDeletes();
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
