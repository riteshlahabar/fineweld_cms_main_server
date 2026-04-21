<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create instances of the seeder classes & call its method run()
        $adminSeeder = new VersionSeeder;
        $adminSeeder->run();

    }
}
