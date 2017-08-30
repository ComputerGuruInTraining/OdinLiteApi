<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShiftCheckCasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::create('shift_check_cases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shift_check_id')->unsigned();//fk
            $table->integer('case_note_id')->unsigned();//fk
            $table->timestamps();
            $table->foreign('shift_check_id')
            	->references('id')->on('shift_checks');
            $table->foreign('case_note_id')
            	->references('id')->on('case_notes');
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
        Schema::dropIfExists('shift_check_cases');

    }
}
