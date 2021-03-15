<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSimulationsTable extends Migration
{
    /**
     * Simulation index table
     *
     * @return void
     */
    public function up()
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('simDate')->comment('Simulation date');
            $table->text('simNotes')->comment('Notes about simulation run');
            $table->index('simDate');
            //            $table->index('simNotes');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('simulations');
    }
}
