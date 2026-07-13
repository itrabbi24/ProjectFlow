<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define permissions list by category
        $permissions = [
            // User Management
            ['name' => 'View Users', 'slug' => 'view_users', 'category' => 'users', 'description' => 'Can view list of users'],
            ['name' => 'Create Users', 'slug' => 'create_users', 'category' => 'users', 'description' => 'Can create new users'],
            ['name' => 'Edit Users', 'slug' => 'edit_users', 'category' => 'users', 'description' => 'Can edit existing users'],
            ['name' => 'Delete Users', 'slug' => 'delete_users', 'category' => 'users', 'description' => 'Can soft-delete users'],
            ['name' => 'Assign Roles & Permissions', 'slug' => 'assign_roles', 'category' => 'users', 'description' => 'Can assign roles/permissions to users'],

            // Project Management
            ['name' => 'View Projects', 'slug' => 'view_projects', 'category' => 'projects', 'description' => 'Can view projects list and details'],
            ['name' => 'Create Projects', 'slug' => 'create_projects', 'category' => 'projects', 'description' => 'Can create new projects'],
            ['name' => 'Edit Projects', 'slug' => 'edit_projects', 'category' => 'projects', 'description' => 'Can edit project details'],
            ['name' => 'Delete Projects', 'slug' => 'delete_projects', 'category' => 'projects', 'description' => 'Can soft-delete projects'],

            // Client Management
            ['name' => 'View Clients', 'slug' => 'view_clients', 'category' => 'clients', 'description' => 'Can view clients list and history'],
            ['name' => 'Create Clients', 'slug' => 'create_clients', 'category' => 'clients', 'description' => 'Can add new clients'],
            ['name' => 'Edit Clients', 'slug' => 'edit_clients', 'category' => 'clients', 'description' => 'Can edit client information'],
            ['name' => 'Delete Clients', 'slug' => 'delete_clients', 'category' => 'clients', 'description' => 'Can delete clients'],

            // Purchase Management
            ['name' => 'View Purchases', 'slug' => 'view_purchases', 'category' => 'purchases', 'description' => 'Can view purchases list'],
            ['name' => 'Create Purchases', 'slug' => 'create_purchases', 'category' => 'purchases', 'description' => 'Can add new purchases'],
            ['name' => 'Edit Purchases', 'slug' => 'edit_purchases', 'category' => 'purchases', 'description' => 'Can edit purchase records'],
            ['name' => 'Delete Purchases', 'slug' => 'delete_purchases', 'category' => 'purchases', 'description' => 'Can delete purchase records'],

            // Expense Management
            ['name' => 'View Expenses', 'slug' => 'view_expenses', 'category' => 'expenses', 'description' => 'Can view expenses list'],
            ['name' => 'Create Expenses', 'slug' => 'create_expenses', 'category' => 'expenses', 'description' => 'Can add new expenses'],
            ['name' => 'Edit Expenses', 'slug' => 'edit_expenses', 'category' => 'expenses', 'description' => 'Can edit expense records'],
            ['name' => 'Delete Expenses', 'slug' => 'delete_expenses', 'category' => 'expenses', 'description' => 'Can delete expense records'],
            ['name' => 'Approve Expenses', 'slug' => 'approve_expenses', 'category' => 'expenses', 'description' => 'Can approve or reject pending expenses'],

            // Income Management
            ['name' => 'View Income', 'slug' => 'view_incomes', 'category' => 'incomes', 'description' => 'Can view income list'],
            ['name' => 'Create Income', 'slug' => 'create_incomes', 'category' => 'incomes', 'description' => 'Can add new income records'],
            ['name' => 'Edit Income', 'slug' => 'edit_incomes', 'category' => 'incomes', 'description' => 'Can edit income records'],
            ['name' => 'Delete Income', 'slug' => 'delete_incomes', 'category' => 'incomes', 'description' => 'Can delete income records'],

            // Reports
            ['name' => 'View Reports', 'slug' => 'view_reports', 'category' => 'reports', 'description' => 'Can view financial and system reports'],

            // Category Management
            ['name' => 'View Categories', 'slug' => 'view_categories', 'category' => 'categories', 'description' => 'Can view expense/purchase categories'],
            ['name' => 'Edit Categories', 'slug' => 'edit_categories', 'category' => 'categories', 'description' => 'Can add, edit, or delete expense/purchase categories'],

            // Settings
            ['name' => 'View Settings', 'slug' => 'view_settings', 'category' => 'settings', 'description' => 'Can view system settings'],
            ['name' => 'Edit Settings', 'slug' => 'edit_settings', 'category' => 'settings', 'description' => 'Can edit system settings'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $p) {
            $createdPermissions[] = Permission::create($p);
        }

        // Create Administrator Role
        $adminRole = Role::create([
            'name' => 'Administrator',
            'slug' => 'administrator',
            'description' => 'System administrator with full access'
        ]);

        // Assign all permissions to Administrator
        $adminRole->permissions()->sync(
            array_map(fn($p) => $p->id, $createdPermissions)
        );

        // Create Project Manager Role
        $pmRole = Role::create([
            'name' => 'Project Manager',
            'slug' => 'project_manager',
            'description' => 'Operational project manager with standard access'
        ]);

        // Define permissions for Project Manager (excludes user management and global settings)
        $pmPermissionSlugs = [
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            'view_clients', 'create_clients', 'edit_clients',
            'view_purchases', 'create_purchases', 'edit_purchases',
            'view_expenses', 'create_expenses', 'edit_expenses', 'approve_expenses',
            'view_incomes', 'create_incomes', 'edit_incomes',
            'view_reports',
            'view_categories'
        ];

        $pmPermissions = Permission::whereIn('slug', $pmPermissionSlugs)->pluck('id');
        $pmRole->permissions()->sync($pmPermissions);
    }
}
