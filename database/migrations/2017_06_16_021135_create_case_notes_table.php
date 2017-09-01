<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCaseNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('case_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('img')->nullable();
            $table->string('description');
            $table->integer('mobile_user_id')->unsigned();//fk
            $table->integer('shift_id')->unsigned();//fk
            $table->integer('curr_loc_id')->unsigned();//fk
            $table->timestamps();
//            $table->foreign('mobile_user_id')
//            	->references('id')->on('users');
//            $table->foreign('shift_id')
//            	->references('id')->on('shifts');
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
        Schema::dropIfExists('case_notes');
    }
}