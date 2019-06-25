<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShiftResumeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_resume', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shift_id')->unsigned();//fk
            $table->string('status', 10);//values = start or resume
            $table->integer('current_shift_check_id')->unsigned()->nullable();//fk values=shift_checks_id while checkIn active, null if no check started yet, 0 if checked out
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
        Schema::dropIfExists('shift_resume');
    }
}
