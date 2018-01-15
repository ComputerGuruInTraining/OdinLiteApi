<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportIndividuals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_individuals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mobile_user_id')->unsigned();//fk to users table
            $table->double('total_hours_worked');
            $table->integer('report_id')->unsigned();//fk
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_individuals');
    }
}
