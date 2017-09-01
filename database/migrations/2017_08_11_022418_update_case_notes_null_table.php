<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCaseNotesNullTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('case_notes', function (Blueprint $table) {
    		$table->integer('case_id')->unsigned()->nullable()->change();//change to nullable
    		$table->string('description')->nullable()->change();//change to nullable
	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('case_notes', function (Blueprint $table) {
    		$table->integer('case_id')->unsigned()->change();
    		$table->string('description')->change();
	});
    }
}
