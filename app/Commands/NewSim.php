<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use App\Simulation;

class NewSim extends Command
{
    /**
     * Create a new simulation set
     *
     * @var string
     */
    protected $signature = 'make:newsim';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new simulation set';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $simSet = new Simulation;

        $simSet->simDate = $this->ask('Simulation date (YYYY-MM-DD)');
        $simSet->simNotes = $this->ask('Simulation notes');

        $simSet->save();

        $this->info('Simulation set ' . $simSet->simDate . ' saved as simulation ID ' . $simSet->id);

        return 1;
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
