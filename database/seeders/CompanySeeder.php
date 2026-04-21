<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'id' => 1,
            'name' => 'The Quick Shop',
            'mobile' => '9999999999',
            'email' => 'company@example.com',
            'address' => 'Ap: Bangalore, India',
            'language_code' => null,
            'language_name' => null,
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'Y-m-d',
            'time_format' => '24',
        ]);
    }
}
