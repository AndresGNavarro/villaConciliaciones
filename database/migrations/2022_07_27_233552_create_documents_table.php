<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id('pkDocument');

            $table->string('originalName'); 
            $table->string('diskName');
            $table->string('iata')->nullable();

            $table->unsignedBigInteger('id')->unsigned();
            $table->foreign('id')->references('id')->on('users');

            $table->unsignedBigInteger('pkDocumentType')->unsigned();
            $table->foreign('pkDocumentType')->references('pkDocumentType')->on('document_types');

            $table->unsignedBigInteger('pkPeriod')->unsigned();
            $table->foreign('pkPeriod')->references('pkPeriod')->on('periods');

            $table->unsignedBigInteger('pkConciliation')->unsigned()->nullable();
            $table->foreign('pkConciliation')->references('pkConciliation')->on('conciliations');

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
        Schema::dropIfExists('documents');
    }
}
