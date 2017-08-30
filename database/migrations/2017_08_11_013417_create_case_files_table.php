<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCaseFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('case_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('case_id')->unsigned();//fk
            $table->string('file');//path on server filesystem
            $table->string('description')->nullable();
            $table->integer('user_id')->unsigned();//fk //if upload files via case rather than case note, gather user details, else copy from case_note
            $table->integer('case_note_id')->nullable()->unsigned();//fk //if file uploaded via a new case note, else null if uploaded directly via case itself
            $table->timestamps();
            $table->foreign('user_id')
            	->references('id')->on('users');
            $table->foreign('case_id')
            	->references('id')->on('cases');
            $table->foreign('case_note_id')
            	->references('id')->on('case_notes');
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
       Schema::dropIfExists('case_files');

    }
}
