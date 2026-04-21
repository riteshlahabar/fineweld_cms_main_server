<?php

namespace Database\Seeders;

use Database\Seeders\Updates\Version251Seeder;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminSeeder = new Version251Seeder;
        $adminSeeder->run();
    }
}
