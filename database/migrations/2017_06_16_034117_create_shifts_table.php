<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //table to capture shift info posted by user while completing shift
        Schema::create('shifts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('assigned_shift_id')->unsigned();
            $table->integer('mobile_user_id')->unsigned();//mobile-users ie employees
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->double('duration')->nullable();
            $table->timestamps();
//            $table->foreign('mobile_user_id')
//            	->references('id')->on('users');
//            $table->foreign('assigned_shift_id')
//            	->references('id')->on('assigned_shifts');
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
        Schema::dropIfExists('shifts');

    }
}