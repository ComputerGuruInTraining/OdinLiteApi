<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCaseNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('case_notes', function (Blueprint $table) {
    		$table->renameColumn('mobile_user_id', 'user_id');//console users may also create a case_note
    		$table->integer('shift_id')->unsigned()->nullable()->change();
    		$table->integer('case_id')->unsigned();
    		$table->dropForeign('case_notes_mobile_user_id_foreign');
    		$table->foreign('user_id')
            		->references('id')->on('users');
                $table->foreign('case_id')
            		->references('id')->on('cases');
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
    		$table->renameColumn('user_id', 'mobile_user_id');
    		$table->integer('shift_id')->unsigned()->change();
    		$table->dropColumn('case_id');
    		$table->dropForeign('case_notes_user_id_foreign');
    		$table->foreign('mobile_user_id')
            		->references('id')->on('users');
            	$table->dropForeign('case_notes_case_id_foreign');
	});
    }
}
