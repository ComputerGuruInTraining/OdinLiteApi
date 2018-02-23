<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateOdinerrorloggingsVarcharLen extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('odin_error_loggings', function (Blueprint $table) {
            $table->string('description', 5000)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('odin_error_loggings', function (Blueprint $table) {
            $table->string('description')->change();
        });
    }
}
