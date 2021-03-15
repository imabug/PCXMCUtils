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
    protected $signature = 'make:autorun';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create autorun files for PCXMCRotation and run the simulations';

    /**
     * Generate PCXMCRotation autocalc.dfR files
     *
     * @return mixed
     */
    public function handle()
    {
        // Phantom parameter array
        // Each row is [phantom age, height, mass, zref]
        $phantParam = [
#            [0, 50.9, 3.4, 25.5],
#            [1, 74.4, 9.2, 36],
#            [5, 109.1, 19, 47],
            [10, 139, 32.4, 59],
#            [15, 168.1, 56.3, 75],
#            [30, 178.6, 73.2, 83],
        ];

        // Scaling factor to calculate X-ray beam height and width at FRD
        // Scaling factor = FRD/FID
        $scale = 40.0 / 60.0;

        $hdrFormat = "%dy kV=%d X=0 Y=0 Z=%d filtA=%d FID=60 IW=%d IH=%d";
        $fnFormat = "%dy_kV=%d_X=0_Y=0_Z=%d__filtA=%d_FID=60_IW=%d_IH=%d";

        foreach ($phantParam as list($age, $length, $mass, $zRef)) {
            for ($kv = 60; $kv <= 120; $kv += 20) {
                for ($height = 1; $height <= 24; $height += 2) {
                    $xRHeight = $height * $scale;
                    for ($width = 1; $width <= 24; $width += 2) {
                    $xRWidth = $width * $scale;
                    for ($filtA = 2; $filtA <= 6; $filtA++ ) {
                        $startTime = microtime(true);
                        $header = sprintf($hdrFormat, $age, $kv, $zRef, $filtA, $width, $height);
                        $filename = sprintf($fnFormat, $age, $kv, $zRef, $filtA, $width, $height);
                    $content = <<<EOD
                      Header text: {$header}
                       Projection:                        0.0000
                       Obl. Angle:                        0.0000
                              Age: {$age}
                           Length: {$length}
                             Mass: {$mass}
                  Arms in phantom:                             1
                              FRD:                       40.0000
                 X-ray beam width: {$xRWidth}
                X-ray beam height: {$xRHeight}
                              FSD:                       37.2000
               Beam width at skin:                       14.8800
              Beam height at skin:                        3.0969
                             Xref:                        0.0000
                             Yref:                        0.0000
                             Zref: {$zRef}
            E-levels (Max.en./10):                            15
                           NPhots:                       1000000
                               kV: {$kv}
                       AnodeAngle:                             5
                     Filter A (Z):                            13
                    Filter A (mm): {$filtA}
                     Filter B (Z):                            29
                    Filter B (mm):                             0
              Input dose quantity:                           DAP
                 Input dose value:                           1.0
                 Output file name: {$filename}
  EOD;

                    $fh = fopen("/home/eugenem/.wine/drive_c/Program Files (x86)/PCXMC/MCRUNS/Autocalc.dfR", 'w+');
                    fwrite($fh, $content);
                    fclose($fh);
                    exec("/usr/bin/wine /home/eugenem/.wine/drive_c/Program\ Files\ \(x86\)/PCXMC/PCXMC20Rotation.exe");
                    $endTime = microtime(true);
                    $this->info(round(($endTime - $startTime)/60, 4));
                    }
                    }
                }
            }
        }
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
