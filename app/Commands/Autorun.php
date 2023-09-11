<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Dfr;
use App\Mgr;
use App\Simulation;

class Autorun extends Command
{
    /**
     * The signature of the command
     *
     * @var string
     */
    protected $signature = 'make:autorun
{simid : Simulation ID for the simulation run}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create autorun files for PCXMCRotation and run the simulations';

    /**
     * PCXMC MCRUNS directory
     */
    protected $mcrunsDir = "/home/eugenem/.wine/drive_c/Program Files (x86)/PCXMC/MCRUNS/";

    /**
     * Path to the PCXMCRotation executable
     */
    protected $pcxmcExe = "/home/eugenem/.wine/drive_c/Program\ Files\ \(x86\)/PCXMC/PCXMC20Rotation.exe";

    /**
     * Generate PCXMCRotation autocalc.dfR files
     */
    public function handle(): void
    {
        $arguments = $this->arguments();

        if (is_null($arguments['simid'])) {
            $this->error('No simulation ID provided');
            exit();
        }

        // Phantom parameter array
        // Each row is [phantom age, length, mass]
        $phantParam = [
            // [0, 50.9, 3.4],
            // [1, 74.4, 9.2],
            // [5, 109.1, 19],
            [10, 139, 32.4],
            // [15, 168.1, 56.3],
            // [30, 178.6, 73.2]
        ];

        // Range of zRef to simulate
        $zRange[10] = [57, 64];
        $zRange[15] = [72, 80];
        $zRange[30] = [80, 88];

        $hdrFormat = '%dy kV=%d X=%d Y=%d Z=%d filtA=%d FID=60 W=%d H=%d Proj=%d';

        // Read the last simulation state (if available)
        if(is_array($simParams = $this->readCheckpoint())) {
            // Checkpoint file was read.
        } else {
            // No checkpoint file was found.
            // Array to store simulation parameters.  These correspond to the lines defined in the
            // Autocalc.dfR file used by PCXMCRotation (PCXMC 2.0 Supplementary Programs Users Guide Page 11)
            $simParams = [
                'simid' => $arguments['simid'],
                'outFile' => $arguments['simid'],
                'xRef' => 0,
                'yRef' => 0,
                'nPhotons' => 20000,
                'age' => $phantParam[0][0],
                'length' => $phantParam[0][1],
                'mass' => $phantParam[0][2],
                'kv' => 60,
                'height' => 2,
                'width' => 2,
                'filtA' => 2,
                'zRef' => $zRange[$phantParam[0][0]][0],
            ];
        }

        foreach ($phantParam as [$age, $length, $mass]) {
            $simParams['age'] = $age;
            $simParams['length'] = $length;
            $simParams['mass'] = $mass;
            for ($kv = 60; $kv <= 120; $kv += 20) {
                $simParams['kv'] = $kv;
                for ($height = 2; $height <= 16; $height += 2) {
                    $simParams['height'] = $height;
                    for ($width = 2; $width <= 16; $width += 2) {
                        $simParams['width'] = $width;
                        for ($filtA = 2; $filtA <= 6; $filtA += 2) {
                            $simParams['filtA'] = $filtA;
                            for ($zRef = $zRange[$age][0]; $zRef <= $zRange[$age][1]; $zRef += 2) {
                                $simParams['zRef'] = $zRef;
                                for ($angle = 0; $angle < 360; $angle++) {
                                    $simParams['angle'] = $angle;
                                    $simParams['header'] = sprintf(
                                        $hdrFormat,
                                        $simParams['age'],
                                        $simParams['kv'],
                                        $simParams['xRef'],
                                        $simParams['yRef'],
                                        $simParams['zRef'],
                                        $simParams['filtA'],
                                        $simParams['width'],
                                        $simParams['height'],
                                        $simParams['angle']
                                    );
                                    // Save the current simulation state before we start
                                    $this->checkpoint($simParams);
                                    $startTime = microtime(true);
                                    // Run the simulation
                                    $this->runSim($simParams);
                                    // Load the simulation data from the dfR file into the database
                                    $dfrid = $this->loadDfr($simParams['simid'], $simParams['outFile']);
                                    // Load the simulation dosimetry data from the mGR file
                                    // into the database
                                    $this->loadMgr($simParams['simid'], $dfrid, $simParams['outFile']);
                                    // PCXMC's Autorun won't overwrite existing simulation result
                                    // files, so delete them
                                    // Path to the files are hardcoded.  Should work on making this
                                    // more programmatic.
                                    unlink($this->mcrunsDir.$simParams['outFile'].".dfR");
                                    unlink($this->mcrunsDir.$simParams['outFile'].".enR");
                                    unlink($this->mcrunsDir.$simParams['outFile'].".mGR");
                                    $endTime = microtime(true);
                                    $this->info(round(($endTime - $startTime) / 60, 4));
                                } // $angle
                            } // $zRef
                        } // $filtA
                    } // $width
                } // $height
            } // $kV
        } // $phantParam
    }

    public function runSim(array $simParams): void
    {
        $content =
<<<EOD
                     Header text: {$simParams['header']}
                      Projection: {$simParams['angle']}
                      Obl. Angle:                        0.0000
                             Age: {$simParams['age']}
                          Length: {$simParams['length']}
                            Mass: {$simParams['mass']}
                 Arms in phantom:                             1
                             FRD:                        40.200
                X-ray beam width: {$simParams['width']}
               X-ray beam height: {$simParams['height']}
                             FSD:                       37.2000
              Beam width at skin:                       14.5424
             Beam height at skin:                       48.4810
                            Xref:                        0.0000
                            Yref:                        0.0000
                            Zref: {$simParams['zRef']}
           E-levels (Max.en./10):                            10
                          NPhots: {$simParams['nPhotons']}
         X-ray tube voltage (kV): {$simParams['kv']}
                      AnodeAngle:                             5
                    Filter A (Z):                            13
                   Filter A (mm): {$simParams['filtA']}
                    Filter B (Z):                            29
                   Filter B (mm):                             0
             Input dose quantity:                           DAP
                Input dose value:                           1.0
                Output file name: {$simParams['outFile']}
EOD;

        // Write the simulation parameter file and run PCXMC
        $fh = fopen($this->mcrunsDir."Autocalc.dfR", 'w+');
        fwrite($fh, $content);
        fclose($fh);
        exec("/usr/bin/wine ".$this->pcxmcExe);
    }

    public function loadDfr(int $simid, int|string $fname): int
    {
        $dfrData = file($this->mcrunsDir.$fname.".dfR");

        $dfr = new Dfr();

        $dfr->simulation_id = $simid;
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

    public function loadMgr(int $simid, int $dfrid, int|string $fname): void
    {
        $mgrData = array_slice(file($this->mcrunsDir.$fname.".mGR", FILE_SKIP_EMPTY_LINES), 18);

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
        $mgr->activeBoneMarrow  = trim(substr($mgrData[7], 33, 15)) * 1000;
        $mgr->adrenals          = trim(substr($mgrData[8], 33, 15)) * 1000;
        $mgr->brain             = trim(substr($mgrData[9], 33, 15)) * 1000;
        $mgr->breasts           = trim(substr($mgrData[10], 33, 15)) * 1000;
        $mgr->colon             = trim(substr($mgrData[11], 33, 15)) * 1000;
        $mgr->upperLrgIntestine = trim(substr($mgrData[12], 33, 15)) * 1000;
        $mgr->lowerLrgIntestine = trim(substr($mgrData[13], 33, 15)) * 1000;
        $mgr->extrathorAirways  = trim(substr($mgrData[14], 33, 15)) * 1000;
        $mgr->gallbladder       = trim(substr($mgrData[15], 33, 15)) * 1000;
        $mgr->heart             = trim(substr($mgrData[16], 33, 15)) * 1000;
        $mgr->kidneys           = trim(substr($mgrData[17], 33, 15)) * 1000;
        $mgr->liver             = trim(substr($mgrData[18], 33, 15)) * 1000;
        $mgr->lungs             = trim(substr($mgrData[19], 33, 15)) * 1000;
        $mgr->lymphNodes        = trim(substr($mgrData[20], 33, 15)) * 1000;
        $mgr->muscle            = trim(substr($mgrData[21], 33, 15)) * 1000;
        $mgr->oesophagus        = trim(substr($mgrData[22], 33, 15)) * 1000;
        $mgr->oralMucosa        = trim(substr($mgrData[23], 33, 15)) * 1000;
        $mgr->ovaries           = trim(substr($mgrData[24], 33, 15)) * 1000;
        $mgr->pancreas          = trim(substr($mgrData[25], 33, 15)) * 1000;
        $mgr->prostate          = trim(substr($mgrData[26], 33, 15)) * 1000;
        $mgr->salGlands         = trim(substr($mgrData[27], 33, 15)) * 1000;
        $mgr->skeleton          = trim(substr($mgrData[28], 33, 15)) * 1000;
        $mgr->skull             = trim(substr($mgrData[29], 33, 15)) * 1000;
        $mgr->upperSpine        = trim(substr($mgrData[30], 33, 15)) * 1000;
        $mgr->midSpine          = trim(substr($mgrData[31], 33, 15)) * 1000;
        $mgr->lowerSpine        = trim(substr($mgrData[32], 33, 15)) * 1000;
        $mgr->scapulae          = trim(substr($mgrData[33], 33, 15)) * 1000;
        $mgr->clavicles         = trim(substr($mgrData[34], 33, 15)) * 1000;
        $mgr->ribs              = trim(substr($mgrData[35], 33, 15)) * 1000;
        $mgr->upperArmBones     = trim(substr($mgrData[36], 33, 15)) * 1000;
        $mgr->midArmBones       = trim(substr($mgrData[37], 33, 15)) * 1000;
        $mgr->lowerArmBones     = trim(substr($mgrData[38], 33, 15)) * 1000;
        $mgr->pelvis            = trim(substr($mgrData[39], 33, 15)) * 1000;
        $mgr->upperLegBones     = trim(substr($mgrData[40], 33, 15)) * 1000;
        $mgr->midLegBones       = trim(substr($mgrData[41], 33, 15)) * 1000;
        $mgr->lowerLegBones     = trim(substr($mgrData[42], 33, 15)) * 1000;
        $mgr->skin              = trim(substr($mgrData[43], 33, 15)) * 1000;
        $mgr->smIntenstine      = trim(substr($mgrData[44], 33, 15)) * 1000;
        $mgr->spleen            = trim(substr($mgrData[45], 33, 15)) * 1000;
        $mgr->stomach           = trim(substr($mgrData[46], 33, 15)) * 1000;
        $mgr->testicles         = trim(substr($mgrData[47], 33, 15)) * 1000;
        $mgr->thymus            = trim(substr($mgrData[48], 33, 15)) * 1000;
        $mgr->thyroid           = trim(substr($mgrData[49], 33, 15)) * 1000;
        $mgr->urinaryBladder    = trim(substr($mgrData[50], 33, 15)) * 1000;
        $mgr->uterus            = trim(substr($mgrData[51], 33, 15)) * 1000;
        $mgr->avgDose           = trim(substr($mgrData[52], 33, 15)) * 1000;
        $mgr->effDoseICRP60     = trim(substr($mgrData[53], 33, 15)) * 1000;
        $mgr->effDoseICRP103    = trim(substr($mgrData[54], 33, 15)) * 1000;
        $mgr->absFraction       = trim(substr($mgrData[55], 33, 15));

        $mgr->save();

        return;

    }

    /**
     * Read the checkpoint file if it exists
     */
    public static function readCheckpoint(): array|bool
    {
        // Check if the checkpoint file exists
        if (file_exists('chk.json')) {
            // Decode the JSON file and return the array.
            return json_decode(file_get_contents('chk.json'), true);
        }

        // No checkpoint file present.
        return false;
    }

    /**
     * Write the current state contents of the $simParams
     * variable to a checkpoint file.
     */
    public static function checkpoint(array $simParams): bool
    {
        $fh = fopen('chk.json', 'w');
        fwrite($fh, json_encode($simParams));
        fclose($fh);

        if(file_exists('chk.json')) {
            return true;
        }

        return false;
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
