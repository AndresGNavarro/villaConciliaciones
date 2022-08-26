<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSubsidiaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_subsidiary', function (Blueprint $table) {
            $table->id('pkUserSubsidiary');

            $table->unsignedBigInteger('id')->unsigned();
            $table->foreign('id')->references('id')->on('users');

            $table->unsignedBigInteger('pkSubsidiary')->unsigned();
            $table->foreign('pkSubsidiary')->references('pkSubsidiary')->on('subsidiaries');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_subsidiary');
    }
}
