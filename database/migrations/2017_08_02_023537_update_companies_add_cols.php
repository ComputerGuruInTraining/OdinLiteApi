<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCompaniesAddCols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
    		$table->dropColumn('description');
    		$table->integer('primary_contact')->unsigned();
    		$table->string('status');
//    		$table->foreign('primary_contact')
//            		->references('id')->on('users');
	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
    		$table->string('description');
    		$table->dropColumn('primary_contact');
    		$table->dropColumn('status');
//    		$table->dropForeign('companies_primary_contact_foreign');
	});
    }
}
