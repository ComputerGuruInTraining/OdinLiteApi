<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateReportsCompIdCol extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('reports', function (Blueprint $table) {
    		$table->integer('company_id')->unsigned();
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
       Schema::table('reports', function (Blueprint $table) {
    		$table->dropColumn('company_id');
    		$table->dropForeign('reports_company_id_foreign');
    		
	});
    }
}
