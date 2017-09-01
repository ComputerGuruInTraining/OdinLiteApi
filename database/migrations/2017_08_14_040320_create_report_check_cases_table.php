<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportCheckCasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_check_cases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('report_case_id')->unsigned();//fk
            $table->integer('shift_check_case_id')->unsigned();//fk
//            $table->foreign('report_case_id')
//            	->references('id')->on('report_cases');
//            $table->foreign('shift_check_case_id')
//            	->references('id')->on('shift_check_cases');
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
        Schema::dropIfExists('report_check_cases');
    }
}
