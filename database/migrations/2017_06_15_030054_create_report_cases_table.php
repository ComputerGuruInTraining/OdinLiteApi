<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportCasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_cases', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('location_id')->unsigned()->nullable();//fk
            $table->double('total_hours');//double, hours todo calculated from shift_checks->check_duration which is in seconds
            $table->integer('total_guards');
            $table->integer('report_id')->unsigned();//fk
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
        Schema::dropIfExists('report_cases');
    }
}