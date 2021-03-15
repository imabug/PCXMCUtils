<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMgrsTable extends Migration
{
    /**
     * Database table to hold the PCXMCRotation simulation results (.mGR)
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mgrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('dfr_id')->comment('Foreign key for dfR file');
            $table->unsignedInteger('simulation_id')->comment('Foreign key for simulation index');
            $table->float('xyScale',12,6);
            $table->float('zScale',12,6);
            $table->unsignedTinyInteger('kv')->comment('Simulation kV');
            $table->char('filter',50);
            $table->float('refAK',12,6)->comment('Reference point air kerma');
            $table->float('activeBoneMarrow',12,6)->comment('Active bone marrow (uGy)');
            $table->float('adrenals',12,6)->comment('Adrenals (uGy)');
            $table->float('brain',12,6)->comment('Brain (uGy)');
            $table->float('breasts',12,6)->comment('Breasts (uGy)');
            $table->float('colon',12,6)->comment('Colon (Large intestine) (uGy)');
            $table->float('upperLrgIntestine',12,6)->comment('Upper large intestine (uGy)');
            $table->float('lowerLrgIntestine',12,6)->comment('Lower large intestine (uGy)');
            $table->float('extrathorAirways',12,6)->comment('Extrathoracic airways (uGy)');
            $table->float('gallbladder',12,6)->comment('Gallbladder (uGy)');
            $table->float('heart',12,6)->comment('Heart (uGy)');
            $table->float('kidneys',12,6)->comment('Kidneys (uGy)');
            $table->float('liver',12,6)->comment('Liver (uGy)');
            $table->float('lungs',12,6)->comment('Lungs (uGy)');
            $table->float('lymphNodes',12,6)->comment('Lymph nodes (uGy)');
            $table->float('muscle',12,6)->comment('Muscle (uGy)');
            $table->float('oesophagus',12,6)->comment('Oesophagus (uGy)');
            $table->float('oralMucosa',12,6)->comment('Oral mucosa (uGy)');
            $table->float('ovaries',12,6)->comment('Ovaries (uGy)');
            $table->float('pancreas',12,6)->comment('Pancreas (uGy)');
            $table->float('prostate',12,6)->comment('Prostate (uGy)');
            $table->float('salGlands',12,6)->comment('Salivary glands (uGy)');
            $table->float('skeleton',12,6)->comment('Skeleton (uGy)');
            $table->float('skull',12,6)->comment('Skull (uGy)');
            $table->float('upperSpine',12,6)->comment('Upper Spine (uGy)');
            $table->float('midSpine',12,6)->comment('Middle Spine (uGy)');
            $table->float('lowerSpine',12,6)->comment('Lower Spine (uGy)');
            $table->float('scapulae',12,6)->comment('Scapulae (uGy)');
            $table->float('clavicles',12,6)->comment('Clavicles (uGy)');
            $table->float('ribs',12,6)->comment('Ribs (uGy)');
            $table->float('upperArmBones',12,6)->comment('Upper arm bones (uGy)');
            $table->float('midArmBones',12,6)->comment('Middle arm bones (uGy)');
            $table->float('lowerArmBones',12,6)->comment('Lower arm bones (uGy)');
            $table->float('pelvis',12,6)->comment('Pelvis (uGy)');
            $table->float('upperLegBones',12,6)->comment('Upper leg bones (uGy)');
            $table->float('midLegBones',12,6)->comment('Middle leg bones (uGy)');
            $table->float('lowerLegBones',12,6)->comment('Lower leg bones (uGy)');
            $table->float('skin',12,6)->comment('Skin (uGy)');
            $table->float('smIntenstine',12,6)->comment('Small intestine (uGy)');
            $table->float('spleen',12,6)->comment('Spleen (uGy)');
            $table->float('stomach',12,6)->comment('Stomach (uGy)');
            $table->float('testicles',12,6)->comment('Testicles (uGy)');
            $table->float('thymus',12,6)->comment('Thymus (uGy)');
            $table->float('thyroid',12,6)->comment('Thyroid (uGy)');
            $table->float('urinaryBladder',12,6)->comment('Urinary bladder (uGy)');
            $table->float('uterus',12,6)->comment('Uterus (uGy)');
            $table->float('avgDose',12,6)->comment('Average dose in total body (uGy)');
            $table->float('effDoseICRP60',12,6)->comment('Effective dose ICRP60  (uSv)');
            $table->float('effDoseICRP103',12,6)->comment('Effective dose ICRP103 (uSv)');
            $table->float('absFraction',12,6)->comment('Abs. energy fraction (%)');
            $table->index('dfr_id');
            $table->index('simulation_id');

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
        Schema::dropIfExists('mgrs');
    }
}
