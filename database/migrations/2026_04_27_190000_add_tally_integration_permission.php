<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_groups')) {
            return;
        }

        $now = now();

        $group = DB::table('permission_groups')
            ->where('name', 'General')
            ->first();

        if (! $group) {
            $groupId = DB::table('permission_groups')->insertGetId([
                'name' => 'General',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $groupId = $group->id;
        }

        $exists = DB::table('permissions')
            ->where('name', 'tally.integration.view')
            ->where('guard_name', 'web')
            ->exists();

        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => 'tally.integration.view',
                'guard_name' => 'web',
                'display_name' => 'Tally Integration',
                'permission_group_id' => $groupId,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('name', 'tally.integration.view')
            ->where('guard_name', 'web')
            ->delete();
    }
};