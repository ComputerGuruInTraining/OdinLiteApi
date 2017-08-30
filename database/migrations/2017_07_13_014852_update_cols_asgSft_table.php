<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColsAsgSftTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assigned_shifts', function (Blueprint $table) {
    		$table->integer('company_id')->unsigned()->default(0);
    		$table->foreign('company_id')
            		->references('id')->on('companies');		
	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('assigned_shifts', function (Blueprint $table) {
    		$table->dropColumn('company_id');
	});
    }
}
