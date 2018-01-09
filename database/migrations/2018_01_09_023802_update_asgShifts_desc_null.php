<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAsgShiftsDescNull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('assigned_shifts', function (Blueprint $table) {
            $table->string('shift_description')->unsigned()->nullable()->change();
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
            $table->string('shift_description')->unsigned()->nullable()->change();
        });
    }
}
