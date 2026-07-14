<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with essentials only:
     * roles & permissions, default users, categories, and settings.
     */
    public function run(): void
    {
        // 1. Roles and Permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Default Income Categories
        $incomeCategories = [
            'Advance Payment', 'Milestone Payment', 'Final Payment',
            'Maintenance Fee', 'Consultancy Fee', 'Other Income'
        ];
        foreach ($incomeCategories as $name) {
            Category::create(['name' => $name, 'type' => 'income', 'status' => 'active']);
        }

        // 3. Default Expense Categories
        $expenseCategories = [
            'Material', 'Labour', 'Transport', 'Food', 'Fuel',
            'Accommodation', 'Electricity', 'Machine Rent',
            'Marketing', 'Miscellaneous', 'Admin Expense'
        ];
        foreach ($expenseCategories as $name) {
            Category::create(['name' => $name, 'type' => 'expense', 'status' => 'active']);
        }

        // 4. Default Users
        $adminRole = Role::where('slug', 'administrator')->first();
        $pmRole = Role::where('slug', 'project_manager')->first();

        $admin = User::create([
            'name' => 'System Administrator',
            'username' => 'admin',
            'email' => 'admin@projectflow.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $pm = User::create([
            'name' => 'Project Manager',
            'username' => 'manager',
            'email' => 'manager@projectflow.com',
            'password' => Hash::make('password'),
            'role_id' => $pmRole->id,
            'status' => 'active',
        ]);

        // 5. Demo Clients
        $client1 = Client::create([
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
            'phone' => '+880 1711-000001',
            'company' => 'Acme Corp Inc.',
            'address' => 'Gulshan-2, Dhaka',
        ]);

        $client2 = Client::create([
            'name' => 'Stark Industries',
            'email' => 'info@stark.com',
            'phone' => '+880 1711-000002',
            'company' => 'Stark Industries LLC',
            'address' => 'Banani, Dhaka',
        ]);

        $client3 = Client::create([
            'name' => 'Wayne Enterprises',
            'email' => 'hello@wayne.com',
            'phone' => '+880 1711-000003',
            'company' => 'Wayne Enterprises Ltd.',
            'address' => 'Agrabad, Chattogram',
        ]);

        // 6. Demo Projects
        $proj1 = Project::create([
            'code' => 'PRJ-2026-0001',
            'name' => 'Alpha Mobile Application',
            'client_id' => $client1->id,
            'manager_id' => $pm->id,
            'start_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonths(3)->format('Y-m-d'),
            'status' => 'running',
            'priority' => 'high',
            'budget' => 125000.00,
            'estimated_profit' => 45000.00,
            'description' => 'Cross-platform mobile application for logistics tracking.',
            'color_label' => 'indigo',
            'progress' => 45,
        ]);

        $proj2 = Project::create([
            'code' => 'PRJ-2026-0002',
            'name' => 'Beta Customer Support Portal',
            'client_id' => $client2->id,
            'manager_id' => $pm->id,
            'start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'end_date' => Carbon::now()->addMonths(2)->format('Y-m-d'),
            'status' => 'planning',
            'priority' => 'medium',
            'budget' => 60000.00,
            'estimated_profit' => 20000.00,
            'description' => 'Self-service customer portal with ticketing and live chat.',
            'color_label' => 'emerald',
            'progress' => 15,
        ]);

        $proj3 = Project::create([
            'code' => 'PRJ-2026-0003',
            'name' => 'Omega ERP Migration',
            'client_id' => $client3->id,
            'manager_id' => $admin->id,
            'start_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'end_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'status' => 'completed',
            'priority' => 'high',
            'budget' => 240000.00,
            'estimated_profit' => 85000.00,
            'description' => 'Legacy ERP data migration to a modern Laravel/MySQL stack.',
            'color_label' => 'amber',
            'progress' => 100,
        ]);

        // 7. Demo Incomes (client payments with categories)
        Income::create([
            'income_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'client_id' => $client1->id,
            'project_id' => $proj1->id,
            'invoice_number' => 'INV-2026-001',
            'category' => 'Advance Payment',
            'amount' => 35000.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-8812903',
            'remarks' => 'First 25% advance milestone payment.',
        ]);

        Income::create([
            'income_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'client_id' => $client1->id,
            'project_id' => $proj1->id,
            'invoice_number' => 'INV-2026-004',
            'category' => 'Milestone Payment',
            'amount' => 35000.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-9921389',
            'remarks' => 'Second milestone (design & mockup approval).',
        ]);

        Income::create([
            'income_date' => Carbon::now()->subDays(8)->format('Y-m-d'),
            'client_id' => $client2->id,
            'project_id' => $proj2->id,
            'invoice_number' => 'INV-2026-002',
            'category' => 'Advance Payment',
            'amount' => 15000.00,
            'payment_method' => 'Mobile Banking',
            'reference_number' => 'REF-1192803',
            'remarks' => 'Kick-off advance deposit (25%).',
        ]);

        Income::create([
            'income_date' => Carbon::now()->subMonths(5)->format('Y-m-d'),
            'client_id' => $client3->id,
            'project_id' => $proj3->id,
            'invoice_number' => 'INV-2025-098',
            'category' => 'Advance Payment',
            'amount' => 120000.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-7718290',
            'remarks' => '50% advance project payment.',
        ]);

        Income::create([
            'income_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'client_id' => $client3->id,
            'project_id' => $proj3->id,
            'invoice_number' => 'INV-2026-003',
            'category' => 'Final Payment',
            'amount' => 120000.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-7729903',
            'remarks' => 'Final 50% project delivery sign-off payment.',
        ]);

        // 8. Demo Expenses
        Expense::create([
            'expense_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'category' => 'Labour',
            'amount' => 8500.00,
            'project_id' => $proj1->id,
            'paid_by' => $pm->id,
            'payment_method' => 'Bank Transfer',
            'description' => 'Contractor fees for mobile app UX design screens.',
            'status' => 'approved',
        ]);

        Expense::create([
            'expense_date' => Carbon::now()->subDays(12)->format('Y-m-d'),
            'category' => 'Food',
            'amount' => 2400.00,
            'project_id' => $proj2->id,
            'paid_by' => $pm->id,
            'payment_method' => 'Cash',
            'description' => 'Client kick-off dinner and project scoping meeting.',
            'status' => 'approved',
        ]);

        Expense::create([
            'expense_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
            'category' => 'Transport',
            'amount' => 12000.00,
            'project_id' => $proj3->id,
            'paid_by' => $admin->id,
            'payment_method' => 'Bank Transfer',
            'description' => 'Transport for onsite data assessment.',
            'status' => 'approved',
        ]);

        Expense::create([
            'expense_date' => Carbon::now()->format('Y-m-d'),
            'category' => 'Labour',
            'amount' => 4500.00,
            'project_id' => $proj1->id,
            'paid_by' => $pm->id,
            'payment_method' => 'Bank Transfer',
            'description' => 'Freelance QA testing suite integration (awaiting signoff).',
            'status' => 'pending',
        ]);

        // 9. Default Settings
        $defaultSettings = [
            'company_name' => 'ProjectFlow',
            'company_email' => 'info@projectflow.com',
            'company_phone' => '',
            'company_address' => '',
            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'timezone' => 'Asia/Dhaka',
            'date_format' => 'Y-m-d',
            'theme' => 'light',
            'smtp_host' => '',
            'smtp_port' => '',
            'smtp_username' => '',
            'smtp_password' => '',
        ];

        foreach ($defaultSettings as $key => $val) {
            Setting::create(['key' => $key, 'value' => $val]);
        }
    }
}
