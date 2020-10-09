<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import', function(Blueprint $table) {
            $table->char('id', 36);
            $table->integer('user_id')->references('id')->on('user');
            $table->timestamps();
            $table->primary("id");
        });
        Schema::table('expense', function (Blueprint $table) {
            $table->char('import_id', 36)->nullable()->references('id')->on('import');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('import');
        Schema::table('expense', function (Blueprint $table) {
            $table->dropColumn('import_id');
        });
    }
}
