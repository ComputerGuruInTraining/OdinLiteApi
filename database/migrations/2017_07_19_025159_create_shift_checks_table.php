<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShiftChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_checks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shift_id')->unsigned();
            $table->integer('location_id')->unsigned();
            $table->integer('checks');
            $table->timestamp('check_ins')->useCurrent();
            $table->timestamp('check_outs')->nullable();
            $table->integer('user_loc_check_in_id')->unsigned()->nullable();
            $table->integer('user_loc_check_out_id')->unsigned()->nullable();
            $table->timestamps();
            $table->foreign('user_loc_check_in_id')
            	->references('id')->on('current_user_locations');
            $table->foreign('user_loc_check_out_id')
            	->references('id')->on('current_user_locations');
            $table->foreign('location_id')
            	->references('id')->on('locations');
            $table->foreign('shift_id')
            	->references('id')->on('shifts');
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
        Schema::dropIfExists('shift_checks');
    }
}
