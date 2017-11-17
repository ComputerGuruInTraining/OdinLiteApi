<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateEmployeesMob extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //change mobile column to string datatype to allow management to format
        //and to allow a mobile of a bigger length to be inserted (up to 25)
        Schema::table('employees', function (Blueprint $table) {
            $table->string('mobile', 25)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('mobile')->change();
        });


    }
}
