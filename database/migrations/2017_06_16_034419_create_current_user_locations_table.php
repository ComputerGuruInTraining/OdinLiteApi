<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCurrentUserLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_user_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('address');
            $table->double('latitude');
            $table->double('longitude');
            $table->integer('location_id')->nullable()->unsigned();
            $table->integer('shift_id')->unsigned();
            $table->integer('mobile_user_id')->unsigned();
            $table->string('user_first_name');//mobile-user
            $table->string('user_last_name');//mobile-user
            $table->timestamps();
            $table->foreign('location_id')
            	->references('id')->on('locations');
            $table->foreign('shift_id')
            	->references('id')->on('shifts');
            $table->foreign('mobile_user_id')
            	->references('id')->on('users')
            	->onUpdate('cascade');
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
        Schema::dropIfExists('current_user_locations');
    }
}