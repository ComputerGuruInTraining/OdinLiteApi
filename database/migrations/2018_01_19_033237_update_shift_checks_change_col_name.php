<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateShiftChecksChangeColName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shift_checks', function (Blueprint $table) {
            $table->renameColumn('within_range_check_in', 'distance_check_in');
            $table->renameColumn('within_range_check_out', 'distance_check_out');
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
            $table->renameColumn('distance_check_in', 'within_range_check_in');
            $table->renameColumn('distance_check_out', 'within_range_check_out');
        });
    }
}
