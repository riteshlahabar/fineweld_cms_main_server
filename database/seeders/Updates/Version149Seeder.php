<?php

namespace Database\Seeders\Updates;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class Version149Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        echo 'Version149Seeder Running...';
        $this->updatePermissions();
        $this->addNewPermissions();

        echo "\Version149Seeder Completed!!\n";
    }

    public function updatePermissions()
    {
        //
    }

    public function addNewPermissions()
    {

        $permissionGroupId = PermissionGroup::firstOrCreate(['name' => 'Expense'])->id;

        $reportPermissionsArray = [
            [
                'name' => 'expense.subcategory.create',
                'display_name' => 'Expense Subcategory Create',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'expense.subcategory.edit',
                'display_name' => 'Expense Subcategory Edit',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'expense.subcategory.view',
                'display_name' => 'Expense Subcategory View',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'expense.subcategory.delete',
                'display_name' => 'Expense Subcategory Delete',
                'permission_group_id' => $permissionGroupId,
            ],

        ];

        foreach ($reportPermissionsArray as $permission) {
            // Validate is the permission exist
            $isPermssionExist = Permission::where('name', $permission['name'])->count();
            $isPermssionExist = $isPermssionExist > 0 ? true : false;

            if (! $isPermssionExist) {
                Permission::firstOrCreate([
                    'name' => $permission['name'],
                    'display_name' => $permission['display_name'],
                    'permission_group_id' => $permission['permission_group_id'],
                    'status' => 1,
                ]);
            }// if

        }// foreach

    }
}
