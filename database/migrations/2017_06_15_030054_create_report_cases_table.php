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
            $table->integer('location_id')->unsigned();//fk
            $table->double('total_hours');
            $table->integer('total_guards');
            $table->integer('report_id')->unsigned();//fk
            $table->timestamps();
            $table->foreign('location_id')
            	->references('id')->on('locations');
            //	->onDelete('set null');
            $table->foreign('report_id')
            	->references('id')->on('reports');
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
        Schema::dropIfExists('report_cases');
    }
}