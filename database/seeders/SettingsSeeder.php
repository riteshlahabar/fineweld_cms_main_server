<?php

namespace Database\Seeders;

use App\Models\AppSettings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AppSettings::create([
            'application_name' => 'DeltaApp',
            'footer_text' => 'Copyright© DeltaApp - 2024',
            'language_id' => 1,
        ]);
    }
}
