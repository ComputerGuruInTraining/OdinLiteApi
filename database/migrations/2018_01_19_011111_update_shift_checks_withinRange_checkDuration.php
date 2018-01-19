<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateShiftChecksWithinRangeCheckDuration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_checks', function (Blueprint $table) {
            $table->string('within_range_check_in');
            $table->string('within_range_check_out')->nullable();//no value until put check out
            $table->integer('check_duration')->nullable();//minutes & no value until put check out
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shift_checks', function (Blueprint $table) {
            $table->dropColumn('within_range_check_in');
            $table->dropColumn('within_range_check_out');
            $table->dropColumn('check_duration');
        });
    }
}
