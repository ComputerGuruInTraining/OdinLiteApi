<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssignedShiftEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assigned_shift_employees', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mobile_user_id')->unsigned();
            $table->integer('assigned_shift_id')->unsigned();
            $table->timestamps();
//            $table->foreign('mobile_user_id')
//            	->references('id')->on('users');
//            //	->onDelete('cascade');
//            $table->foreign('assigned_shift_id')
//            	->references('id')->on('assigned_shifts');
            //	->onDelete('cascade');
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
        Schema::dropIfExists('assigned_shift_employees');
    }
}