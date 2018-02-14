<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOdinErrorLoggingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('odin_error_loggings', function (Blueprint $table) {


            //we will need the recipient and then perhaps find the recipient email address in the users table
            //and extract the user_id and insert this (in case the email changes or whatnot, we still know which user it relates to)
            //other than that, simply details related to the log
            $table->increments('id');
            $table->string('event');
            $table->string('recipient');
            $table->string('description');
            $table->timestamps();
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
        Schema::dropIfExists('odin_error_loggings');
    }
}
