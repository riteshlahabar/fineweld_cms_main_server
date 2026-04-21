<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CustomClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all types of cache and debugbar cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Clear all standard caches with one command
        $this->call('optimize:clear');
        $this->info('All standard caches cleared!');

        // Clear browser.log safely
        $browserLog = storage_path('logs/browser.log');
        if (file_exists($browserLog)) {
            file_put_contents($browserLog, '');
        }

        //Clear Laravel Debugbar log safely
        $laravelLog = storage_path('logs/laravel.log');
        if (file_exists($laravelLog)) {
            file_put_contents($laravelLog, '');
        }

        //Clear custom log safely
        $customLog = storage_path('logs/custom.log');
        if (file_exists($customLog)) {
            file_put_contents($customLog, '');
        }

        // Clear debugbar cache if package exists
        if (class_exists(\Barryvdh\Debugbar\Facades\Debugbar::class)) {
            $this->call('debugbar:clear');
            $this->info('Debugbar cache cleared!');
        } else {
            $this->warn('Debugbar package not found. Skipping debugbar clear.');
        }

        $this->info('All caches cleared successfully!');

        return 0;
    }
}
