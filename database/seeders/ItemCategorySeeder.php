<?php

namespace Database\Seeders;

use App\Models\Items\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ItemCategory::create([
            'name' => 'General',
            'is_deletable' => 0,
        ]);
    }
}
