<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $newPermissions = [
            // Fixed Assets
            [
                'name' => 'View Assets',
                'slug' => 'view_assets',
                'category' => 'assets',
                'description' => 'Can view fixed assets directory and reports'
            ],
            [
                'name' => 'Manage Assets',
                'slug' => 'manage_assets',
                'category' => 'assets',
                'description' => 'Can create, edit, dispose and delete fixed assets'
            ],
            // Activity Logs
            [
                'name' => 'View Activity Logs',
                'slug' => 'view_activity_logs',
                'category' => 'activity_logs',
                'description' => 'Can view system activity audit trail'
            ],
        ];

        $created = [];
        foreach ($newPermissions as $p) {
            $created[] = Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        // Assign all to Administrator
        $admin = Role::where('slug', 'administrator')->first();
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(collect($created)->pluck('id')->toArray());
        }

        // Assign View Assets & Manage Assets to Project Manager
        $pm = Role::where('slug', 'project_manager')->first();
        if ($pm) {
            $pmPerms = Permission::whereIn('slug', ['view_assets', 'manage_assets'])->pluck('id')->toArray();
            $pm->permissions()->syncWithoutDetaching($pmPerms);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('slug', ['view_assets', 'manage_assets', 'view_activity_logs'])->delete();
    }
};
