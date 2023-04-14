<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

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
        // Each row is [phantom age, height, masss]
        $phantParam = [
            [0, 50.9, 3.4],
            [1, 74.4, 9.2],
            [5, 109.1, 19],
            [10, 139, 32.4],
            [15, 168.1, 56.3],
            [30, 178.6, 73.2],
        ];

        // Range of zRef to simulate
        $zRange[10] = [57, 64];
        $zRange[15] = [72, 80];
        $zRange[30] = [80, 88];

        $hdrFormat = '%dy kV=%d X=%d Y=%d Z=%d filtA=%d FID=60 W=%d H=%d Proj=%d';

        // Array to store simulation parameters.  These correspond to the lines defined in the
        // Autocalc.dfR file used by PCXMCRotation (PCXMC 2.0 Supplementary Programs Users Guide Page 11)
        $simParams = [
            'simid' => $arguments['simid'],
            'outFile' => $arguments['simid'],
            'xRef' => 0,
            'yRef' => 0,
            'nPhotons' => 1000,
        ];

        foreach ($phantParam as [$age, $length, $mass]) {
            $simParams['age'] = $age;
            $simParams['length'] = $length;
            $simParams['mass'] = $mass;
            for ($kv = 60; $kv <= 120; $kv += 20) {
                $simParams['kv'] = $kv;
                for ($height = 1; $height <= 16; $height += 2) {
                    $simParams['height'] = $height;
                    for ($width = 1; $width <= 16; $width += 2) {
                        $simParams['width'] = $width;
                        for ($filtA = 2; $filtA <= 6; $filtA += 2) {
                            $simParams['filtA'] = $filtA;
                            for ($zRef = $zRange[$age][0]; $zRef <= $zRange[$age][1]; $zRef += 2) {
                                $simParams['zRef'] = $zRef;
                                for ($angle = 0; $angle < 360; $angle++) {
                                    $startTime = microtime(true);
                                    $simParams['angle'] = $angle;
                                    $simParams['header'] = sprintf($hdrFormat,
                                                                   $simParams['age'],
                                                                   $simParams['kv'],
                                                                   $simParams['xRef'],
                                                                   $simParams['yRef'],
                                                                   $simParams['zRef'],
                                                                   $simParams['filtA'],
                                                                   $simParams['width'],
                                                                   $simParams['height'],
                                                                   $simParams['angle']);
                                    $this->runSim($simParams);
                                    $this->loadDfr($simParams['simid']);
                                    $this->loadMgr($simParams['simid']);
                                    $endTime = microtime(true);
                                    $this->info(round(($endTime - $startTime)/60, 4));
                                } // $angle
                            } // $zRef
                        } // $filtA
                    } // $width
                } // $height
            } // $kV
        } // $phantParam
    }

    public function runSim($simParams): void
    {
        $content = <<<EOD
                      Header text: {$simParams['header']}
                      Projection: {$simParams['angle']}
                      Obl. Angle:                        0.0000
                      Age: {$simParams['age']}
                      Length: {$simParams['length']}
                      Mass: {$simParams['mass']}
                      Arms in phantom:                             1
                      FRD:                       40.0000
                      X-ray beam width: {$simParams['width']}
                      X-ray beam height: {$simParams['height']}
                      FSD:                       37.2000
                      Beam width at skin:                       14.8800
                      Beam height at skin:                        3.0969
                      Xref:                             0
                      Yref:                             0
                      Zref: {$simParams['zRef']}
                      E-levels (Max.en./10):                            15
                      NPhots: {$simParams['nPhotons']}
                      kV: {$simParams['kv']}
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
        $fh = fopen("/home/eugenem/.wine/drive_c/Program Files (x86)/PCXMC/MCRUNS/Autocalc.dfR", 'w+');
        fwrite($fh, $content);
        fclose($fh);
        exec("/usr/bin/wine /home/eugenem/.wine/drive_c/Program\ Files\ \(x86\)/PCXMC/PCXMC20Rotation.exe");
    }

    public function loadDfr(): void
    {

    }

    public function loadMgr(): void
    {

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
