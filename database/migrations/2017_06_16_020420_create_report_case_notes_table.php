<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportCaseNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_case_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('report_case_id')->unsigned();//fk
            $table->integer('case_note_id')->unsigned();//fk
            $table->timestamps();
            $table->foreign('report_case_id')
            	->references('id')->on('report_cases');
            	//->onDelete('cascade');
            $table->foreign('case_note_id')
            	->references('id')->on('case_notes');
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
        Schema::dropIfExists('report_case_notes');
    }
}