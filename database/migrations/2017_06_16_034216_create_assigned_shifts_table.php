<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssignedShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assigned_shifts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->integer('console_user_id')->unsigned();
            $table->string('shift_title');
            $table->string('shift_description');
            $table->integer('roster_id')->nullable()->unsigned();
            $table->timestamps();
            $table->foreign('roster_id')
            	->references('id')->on('rosters');
            	//->onDelete('set null');
            $table->foreign('console_user_id')
            	->references('id')->on('console_users');
            	//->onDelete('set null');
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
        Schema::dropIfExists('assigned_shifts');
    }
}