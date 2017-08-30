<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssignedShiftLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assigned_shift_locations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('assigned_shift_id')->unsigned();
            $table->integer('location_id')->unsigned();
            $table->integer('checks');
            $table->timestamps();
            $table->foreign('location_id')
            	->references('id')->on('locations');
            	//->onDelete('cascade');
            $table->foreign('assigned_shift_id')
            	->references('id')->on('assigned_shifts');
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
        Schema::dropIfExists('assigned_shift_locations');
    }
}