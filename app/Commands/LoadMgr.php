<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Dfr;
use App\Mgr;
use App\Simulation;

class LoadMgr extends Command
{
    /**
     * The signature of the command.
     * simid - Simulation ID to associate the mGR file with (optional)
     * dfrid - dfR id to associate the mGR file with (optional)
     *
     * @var string
     */
    protected $signature = 'load:mgr {simid?} {dfrid?} {mgrfile?}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Load a PCXMCRotation simulation result file and store contents in the database';

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

        // Process the arguments
        if (is_null($arguments['simid'])) {
            // Ask the user which simulation set to associate the file data with
            $simSet = Simulation::findOrFail($this->ask('Simulation set ID for this file'));
        } else {
            $simSet = Simulation::findOrFail($arguments['simid']);
        }

        if (is_null($arguments['dfrid'])) {
            $dfr = Dfr::findOrFail($this->ask('dfR ID for this file'));
        } else {
            $dfr = Dfr::findOrFail($arguments['dfrid']);
        }

        if (is_null($arguments['mgrfile'])) {
            // Ask user for the file name of the .mGR file
            $mgrFile = $this->ask('mGR file name to load');
            if (! is_file($mgrFile.$this->mgrExt)) {
                $this->error('Invalid file or file doesn\'t exist');
                return 0;
            }
        } else {
            $mgrFile = $arguments['mgrfile'];
        }
        $mgrID = $this->loadMgr($simSet->id, $dfr->id, $mgrFile);

        $this->info('mGR file stored as '.$mgrID);
        return 1;
    }

    /**
     * Load the mGR data
     * First part of the .mGR file contains the simulation parameters.  Most of this
     * is already in the .dfR file.  Want to keep the last 5 lines of this first section
     * Dose data starts with line 26
     * Organ names are in columns 0-32
     * Dose values are in columns 33-47
     * Simulation error is in columns 48-62 (ignored)
     */
    public function loadMgr($simid, $dfrid, $mgrFile): int
    {
        $mgrData = array_slice(file($mgrFile.$this->mgrExt, FILE_SKIP_EMPTY_LINES), 18);

        $mgr = new Mgr;
        // Store the last 5 lines of the first section of the mGR file
        $mgr->simulation_id = $simid;
        $mgr->dfr_id        = $dfrid;
        $mgr->xyScale       = trim(substr($mgrData[0], 33, 30));
        $mgr->zScale        = trim(substr($mgrData[1], 33, 30));
        $mgr->kv            = trim(substr($mgrData[2], 33, 30));
        $mgr->filter        = trim(substr($mgrData[3], 33, 30));
        $mgr->refAK         = trim(substr($mgrData[4], 33, 30));

        // Store the dose data from the mGR file. Multiply doses by 1000
        // to convert from mGy to microGray.
        $mgr->activeBoneMarrow  = trim(substr($mgrData[7], 33, 15))*1000;
        $mgr->adrenals          = trim(substr($mgrData[8], 33, 15))*1000;
        $mgr->brain             = trim(substr($mgrData[9], 33, 15))*1000;
        $mgr->breasts           = trim(substr($mgrData[10], 33, 15))*1000;
        $mgr->colon             = trim(substr($mgrData[11], 33, 15))*1000;
        $mgr->upperLrgIntestine = trim(substr($mgrData[12], 33, 15))*1000;
        $mgr->lowerLrgIntestine = trim(substr($mgrData[13], 33, 15))*1000;
        $mgr->extrathorAirways  = trim(substr($mgrData[14], 33, 15))*1000;
        $mgr->gallbladder       = trim(substr($mgrData[15], 33, 15))*1000;
        $mgr->heart             = trim(substr($mgrData[16], 33, 15))*1000;
        $mgr->kidneys           = trim(substr($mgrData[17], 33, 15))*1000;
        $mgr->liver             = trim(substr($mgrData[18], 33, 15))*1000;
        $mgr->lungs             = trim(substr($mgrData[19], 33, 15))*1000;
        $mgr->lymphNodes        = trim(substr($mgrData[20], 33, 15))*1000;
        $mgr->muscle            = trim(substr($mgrData[21], 33, 15))*1000;
        $mgr->oesophagus        = trim(substr($mgrData[22], 33, 15))*1000;
        $mgr->oralMucosa        = trim(substr($mgrData[23], 33, 15))*1000;
        $mgr->ovaries           = trim(substr($mgrData[24], 33, 15))*1000;
        $mgr->pancreas          = trim(substr($mgrData[25], 33, 15))*1000;
        $mgr->prostate          = trim(substr($mgrData[26], 33, 15))*1000;
        $mgr->salGlands         = trim(substr($mgrData[27], 33, 15))*1000;
        $mgr->skeleton          = trim(substr($mgrData[28], 33, 15))*1000;
        $mgr->skull             = trim(substr($mgrData[29], 33, 15))*1000;
        $mgr->upperSpine        = trim(substr($mgrData[30], 33, 15))*1000;
        $mgr->midSpine          = trim(substr($mgrData[31], 33, 15))*1000;
        $mgr->lowerSpine        = trim(substr($mgrData[32], 33, 15))*1000;
        $mgr->scapulae          = trim(substr($mgrData[33], 33, 15))*1000;
        $mgr->clavicles         = trim(substr($mgrData[34], 33, 15))*1000;
        $mgr->ribs              = trim(substr($mgrData[35], 33, 15))*1000;
        $mgr->upperArmBones     = trim(substr($mgrData[36], 33, 15))*1000;
        $mgr->midArmBones       = trim(substr($mgrData[37], 33, 15))*1000;
        $mgr->lowerArmBones     = trim(substr($mgrData[38], 33, 15))*1000;
        $mgr->pelvis            = trim(substr($mgrData[39], 33, 15))*1000;
        $mgr->upperLegBones     = trim(substr($mgrData[40], 33, 15))*1000;
        $mgr->midLegBones       = trim(substr($mgrData[41], 33, 15))*1000;
        $mgr->lowerLegBones     = trim(substr($mgrData[42], 33, 15))*1000;
        $mgr->skin              = trim(substr($mgrData[43], 33, 15))*1000;
        $mgr->smIntenstine      = trim(substr($mgrData[44], 33, 15))*1000;
        $mgr->spleen            = trim(substr($mgrData[45], 33, 15))*1000;
        $mgr->stomach           = trim(substr($mgrData[46], 33, 15))*1000;
        $mgr->testicles         = trim(substr($mgrData[47], 33, 15))*1000;
        $mgr->thymus            = trim(substr($mgrData[48], 33, 15))*1000;
        $mgr->thyroid           = trim(substr($mgrData[49], 33, 15))*1000;
        $mgr->urinaryBladder    = trim(substr($mgrData[50], 33, 15))*1000;
        $mgr->uterus            = trim(substr($mgrData[51], 33, 15))*1000;
        $mgr->avgDose           = trim(substr($mgrData[52], 33, 15))*1000;
        $mgr->effDoseICRP60     = trim(substr($mgrData[53], 33, 15))*1000;
        $mgr->effDoseICRP103    = trim(substr($mgrData[54], 33, 15))*1000;
        $mgr->absFraction       = trim(substr($mgrData[55], 33, 15));

        $mgr->save();

        return $mgr->id;
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
