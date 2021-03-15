<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDfrsTable extends Migration
{
    /**
     * Database table to hold the PCXMCRotation simulation definitions file (.dfR)
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dfrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('simulation_id')->comment('Foreign key for simulation index');
            $table->text('header')->comment('Header text');
            $table->float('projection',12,6)->comment('Projection angle');
            $table->float('oblAngle',12,6)->comment('Cranio-caudal angle');
            $table->unsignedTinyInteger('age')->comment('Patient age');
            $table->float('phantLength',12,6)->comment('Patient height');
            $table->float('phantMass',12,6)->comment('Phantom mass');
            $table->boolean('phantArms')->comment('Arms in phantom');
            $table->float('frd')->comment('Focus-reference point distance');
            $table->float('xrayBeamWidth',12,6)->comment('X-ray beam width at FRD');
            $table->float('xrayBeamHeight',12,6)->comment('X-ray beam height at FRD');
            $table->float('fsd',12,6)->comment('Focus-skin distance');
            $table->float('skinBeamWidth',12,6)->comment('X-ray beam width at skin');
            $table->float('skinBeamHeight',12,6)->comment('X-ray beam height at skin');
            $table->float('xRef',12,6)->comment('X reference point location');
            $table->float('yRef',12,6)->comment('Y reference point location');
            $table->float('zRef',12,6)->comment('Z reference point location');
            $table->unsignedTinyInteger('eLevels')->comment('Max photon energy / 10');
            $table->integer('nPhots')->comment('Number of photons');
            $table->unsignedTinyInteger('kv')->comment('Simulation kV');
            $table->float('anodeAngle',4,1)->comment('Anode angle');
            $table->unsignedTinyInteger('filterAZ')->comment('Filter A (Z)');
            $table->float('filterAThick',3,1)->comment('Filter A thickness (mm)');
            $table->unsignedTinyInteger('filterBZ')->comment('Filter B (Z)');
            $table->float('filterBThick',3,1)->comment('Filter B thickness (mm)');
            $table->enum('inpDoseQty', ['EAK','EE','DAP','EAP','MAS'])->comment('Input dose quantity');
            $table->float('inpDoseVal',12,6)->comment('Input dose value');
            $table->text('outputFileName')->comment('Output file name pattern');
            $table->index('simulation_id');
            $table->index('age');
            $table->index('kv');

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
        Schema::dropIfExists('dfrs');
    }
}
