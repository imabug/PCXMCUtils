<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Dfr;
use App\Simulation;

class LoadDfr extends Command
{
    /**
     * The signature of the command.
     * simid - Simulation ID to associate the dfR file with (optional)
     *
     * @var string
     */
    protected $signature = 'load:dfr {simid?} {dfrfile?}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Load a PCXMCRotation definition file and store contents in the database';

    /**
     * dfR file extension.
     *
     * @var string
     */
    protected $dfrExt = '.dfR';

    /**
     * mGR file extension.
     *
     * @var string
     */
    protected $mgrExt = '.mGR';

    /**
     * Load the specified file and store the values to the database
     *
     * @return mixed
     */
    public function handle()
    {
        $arguments = $this->arguments();

        if (is_null($arguments['simid'])) {
            // Ask user which simulation set to associate the file data with
            $simSet = Simulation::findOrFail($this->ask('Simulation set ID for this file'));
        }

        $simSet = Simulation::findOrFail($this->argument('simid'));

        if (is_null($arguments['dfrfile'])) {
            // Ask user for the filename of the .dfR file
            $dfrFile = $this->ask('dfR file name to load: ');
        }

        $dfrFile = $arguments['dfrfile'];

        // Check if the file exists
        if (! is_file($dfrFile.$this->dfrExt)) {
            $this->error('Invalid file or file doesn\'t exist.');
            return 0;
        }

        $dfrID = $this->loadDfr($simSet->id,$dfrFile);
        $this->info('dfR file stored as '.$dfrID);
        // If the corresponding .mGR file exists, load it into the database
        if (is_file($dfrFile.$this->mgrExt)) {
            $this->call('load:mgr', [
                'simid' => $simSet->id,
                'dfrid' => $dfrID,
                'mgrfile' => $dfrFile,
            ]);
        }
        return 1;
    }


    /**
     * Load the dfR file and store contents into the database
     * Field names are in columns 0-31
     * Field values are in columns 32+
     *
     * @return int
     */

    public function loadDfr($simID, $dfrFile): int
    {
        $dfrData = file($dfrFile.$this->dfrExt);

        $dfr = new Dfr;

        $dfr->simulation_id  = $simID;
        $dfr->header         = trim(substr($dfrData[0], 33, 100));
        $dfr->projection     = trim(substr($dfrData[1], 33, 30));
        $dfr->oblAngle       = trim(substr($dfrData[2], 33, 30));
        $dfr->age            = trim(substr($dfrData[3], 33, 30));
        $dfr->phantLength    = trim(substr($dfrData[4], 33, 30));
        $dfr->phantMass      = trim(substr($dfrData[5], 33, 30));
        $dfr->phantArms      = trim(substr($dfrData[6], 33, 30));
        $dfr->frd            = trim(substr($dfrData[7], 33, 30));
        $dfr->xrayBeamWidth  = trim(substr($dfrData[8], 33, 30));
        $dfr->xrayBeamHeight = trim(substr($dfrData[9], 33, 30));
        $dfr->fsd            = trim(substr($dfrData[10], 33, 30));
        $dfr->skinBeamWidth  = trim(substr($dfrData[11], 33, 30));
        $dfr->skinBeamHeight = trim(substr($dfrData[12], 33, 30));
        $dfr->xRef           = trim(substr($dfrData[13], 33, 30));
        $dfr->yRef           = trim(substr($dfrData[14], 33, 30));
        $dfr->zRef           = trim(substr($dfrData[15], 33, 30));
        $dfr->eLevels        = trim(substr($dfrData[16], 33, 30));
        $dfr->nPhots         = trim(substr($dfrData[17], 33, 30));
        $dfr->kv             = trim(substr($dfrData[18], 33, 30));
        $dfr->anodeAngle     = trim(substr($dfrData[19], 33, 30));
        $dfr->filterAZ       = trim(substr($dfrData[20], 33, 30));
        $dfr->filterAThick   = trim(substr($dfrData[21], 33, 30));
        $dfr->filterBZ       = trim(substr($dfrData[22], 33, 30));
        $dfr->filterBThick   = trim(substr($dfrData[23], 33, 30));
        $dfr->inpDoseQty     = trim(substr($dfrData[24], 33, 30));
        $dfr->inpDoseVal     = trim(substr($dfrData[25], 33, 30));
        $dfr->OutputFileName = trim(substr($dfrData[26], 33, 100));

        $dfr->save();

        return $dfr->id;
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
