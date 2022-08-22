<?php

namespace App\Commands;

use Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Dfr;
use App\Mgr;
use App\Simulation;

class LoadSimFiles extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'load:simFiles
{simid? : Simulation ID for the PCXMCRotation data files (optional)}
{dir? : Directory containing the PCXMCRotation data files (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Load PCXMCRotation simulation files in a directory and store them in the database.';

    /**
     * dfR file extension.
     * dfR files are the PCXMCRotation simulation definition files
     *
     * @var string
     *
     */
    protected $dfrExt = 'dfR';

    /**
     * mGR file extension.
     * mGR files contain the radiation dose results from the PCXMCRotation simulation
     *
     * @var string
     *
     */
    protected $mgrExt = 'mGR';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $arguments = $this->arguments();

        if (is_null($arguments['simid'])) {
            // Ask which simulation set to associate the data with
            $simSet = Simulation::findOrFail($this->ask('Simulation set ID: '));
        } else {
            $simSet = Simulation::findOrFail($this->argument('simid'));
        }


        if (is_null($arguments['dir'])) {
            // Ask which directory to load files from
            $directory = $this->ask('Directory to load data files from: ');
        } else {
            $directory = $arguments['dir'];
        }

        // Get all the .dfR files
        $dfrCollection = collect(Storage::disk('simulations')->files($directory))->
            filter(function ($value) {
                if (File::extension($value) == $this->dfrExt) {
                    return $value;
                }
            });

        $n = $dfrCollection->count();

        // No dfR files found.  Nothing to do so exit here.
        if ($n == 0) {
            $this->error('There are no PCXMCRotation simulation definition files available.');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($n);
        $progressBar->start();

        // Go through each file and process the corresponding dfR and mGR file
        foreach ($dfrCollection as $f) {
            $dfrid = $this->loadDfr($simSet->id, $f);

            // Replace the .dfR extension with .mGR and load the mGR file
            $mgrFile = substr($f, 0, -3).$this->mgrExt;
            if (Storage::exists($mgrFile)) {
                $mgrid = $this->loadMgr($simSet->id, $dfrid, $mgrFile);
            }

            $progressBar->advance();
        };

        $progressBar->finish();

        return 1;
    }

    /**
     * Load the dfR file and store contents into the database
     * Field names are in columns 0-31
     * Field values are in columns 32+
     *
     * @return int
     */
    private function loadDfr($simid, $f): int
    {
        // Load the dfR file
        // Storage::get() returns the file contents as a string.
        // Use explode() to split each line into an array element
        $dfrData = explode("\n", Storage::disk('simulations')->get($f));

        $dfr = new Dfr();

        // Parse the dfR data
        $dfr->simulation_id  = $simid;
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
     * Load the mGR data
     * First part of the .mGR file contains the simulation parameters.  Most of this
     * is already in the .dfR file.  Want to keep the last 5 lines of this first section
     * Dose data starts with line 26
     * Organ names are in columns 0-32
     * Dose values are in columns 33-47
     * Simulation error is in columns 48-62 (ignored)
     */
    private function loadMgr($simid, $dfrid, $f): int
    {
        // Load the mGR file
        // Storage::get() returns the file contents as a string.
        // Use explode() to split each line into an array element
        $mgrData = array_slice(
            explode("\r\n", Storage::disk('simulations')->get($f)),
            18
        );

        $mgr = new Mgr();

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
