<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_role', function (Blueprint $table) {
            $table->id('pkMenuRole');

            $table->unsignedBigInteger('pkMenu')->unsigned();
            $table->foreign('pkMenu')->references('pkMenu')->on('menus');

            $table->unsignedBigInteger('pkRole')->unsigned();
            $table->foreign('pkRole')->references('pkRole')->on('roles');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_role');
    }
}
