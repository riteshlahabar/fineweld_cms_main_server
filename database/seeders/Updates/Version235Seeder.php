<?php

namespace Database\Seeders\Updates;

use App\Models\Prefix;
use Illuminate\Database\Seeder;

class Version235Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        echo 'Version235Seeder Running...';
        $this->updatePermissions();
        $this->addNewPermissions();

        echo "\Version235Seeder Completed!!\n";
    }

    public function updatePermissions()
    {
        Prefix::query()->update(['stock_adjustment' => 'SA/']);
    }

    public function addNewPermissions()
    {
        //
    }// funciton addNewPermissions
}
