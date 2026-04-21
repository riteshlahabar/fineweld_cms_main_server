<?php

use App\Models\PermissionGroup;
use Spatie\Permission\Models\Permission;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $moduleName = 'Partnership Module';

        $permissionGroupId = PermissionGroup::firstOrCreate(['name' => $moduleName])->id;

        $reportPermissionsArray = [

            // Partner
            [
                'name' => 'partner.create',
                'display_name' => 'Create Partner',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.edit',
                'display_name' => 'Edit Partner',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.view',
                'display_name' => 'View Partners',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.delete',
                'display_name' => 'Delete Partner',
                'permission_group_id' => $permissionGroupId,
            ],

            //Contract
            [
                'name' => 'partner.contract.create',
                'display_name' => 'Create Contract',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.contract.edit',
                'display_name' => 'Edit Contract',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.contract.view',
                'display_name' => 'View Contracts',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.contract.delete',
                'display_name' => 'Delete Contract',
                'permission_group_id' => $permissionGroupId,
            ],

            //Settlement
            [
                'name' => 'partner.settlement.create',
                'display_name' => 'Create Settlement',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.settlement.edit',
                'display_name' => 'Edit Settlement',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.settlement.view',
                'display_name' => 'View Settlements',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.settlement.delete',
                'display_name' => 'Delete Settlement',
                'permission_group_id' => $permissionGroupId,
            ],

            //Party Payment * Party Opening Balance - allocation to parter
            [
                'name' => 'partner.payment-allocation.view',
                'display_name' => 'View Party Payment Allocation',
                'permission_group_id' => $permissionGroupId,
            ],
            [
                'name' => 'partner.payment-allocation.delete',
                'display_name' => 'Delete Party Payment Allocation',
                'permission_group_id' => $permissionGroupId,
            ],

            //Report
            [
                'name' => 'partner.report',
                'display_name' => 'Partnership Module Reports',
                'permission_group_id' => $permissionGroupId,
            ],
        ];

        foreach ($reportPermissionsArray as $permission) {
            // Validate if the permission exists
            $isPermissionExist = Permission::where('name', $permission['name'])->exists();

            if (! $isPermissionExist) {
                Permission::create([
                    'name' => $permission['name'],
                    'display_name' => $permission['display_name'],
                    'permission_group_id' => $permission['permission_group_id'],
                    'status' => 1,
                ]);
            }

        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }


};
