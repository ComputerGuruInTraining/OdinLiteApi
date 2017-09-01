<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateShiftsDurationCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
        //use an integer duration with the number in minutes for ease of calculation
    		$table->integer('duration')->change();
    		$table->integer('company_id')->unsigned();
//    		$table->foreign('company_id')
//            		->references('id')->on('companies');
	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
    		$table->double('duration')->change();
    		$table->dropColumn('company_id');
//    		$table->dropForeign('reports_company_id_foreign');
	});
    }
}
