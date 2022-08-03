<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConciliationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conciliations', function (Blueprint $table) {
            $table->id('pkConciliation');
            
            $table->unsignedBigInteger('id')->unsigned();
            $table->foreign('id')->references('id')->on('users');

            $table->unsignedBigInteger('pkPeriod')->unsigned();
            $table->foreign('pkPeriod')->references('pkPeriod')->on('periods');

            $table->double('valueInvoiceBsp', 8, 2);
            $table->double('valuePreviousReport', 8, 2);
            $table->double('valueDiferences', 8, 2);
            $table->string('status'); //Pending, confirmed, canceled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conciliations');
    }
}
