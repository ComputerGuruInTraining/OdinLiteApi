<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('location_id')->unsigned();//fk
            $table->integer('case_mgr_user_id')->unsigned();//fk //user that is overseeing the case and should be consulted regarding the case
            $table->timestamps();
//            $table->foreign('location_id')
//            	->references('id')->on('locations');
//            $table->foreign('case_mgr_user_id')
//            	->references('id')->on('users');
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
       Schema::dropIfExists('cases');

    }
}
