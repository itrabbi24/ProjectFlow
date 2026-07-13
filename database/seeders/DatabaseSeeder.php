<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Income;
use App\Models\Setting;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Run Roles and Permissions Seeder
        $this->call(RolesAndPermissionsSeeder::class);

        // 1b. Default Expense/Purchase Categories
        $defaultCategoryNames = [
            'Material', 'Labour', 'Transport', 'Food', 'Fuel',
            'Accommodation', 'Electricity', 'Machine Rent',
            'Marketing', 'Miscellaneous', 'Admin Expense'
        ];
        foreach ($defaultCategoryNames as $name) {
            Category::create(['name' => $name, 'type' => 'expense', 'status' => 'active']);
            Category::create(['name' => $name, 'type' => 'purchase', 'status' => 'active']);
        }

        $adminRole = Role::where('slug', 'administrator')->first();
        $pmRole = Role::where('slug', 'project_manager')->first();

        // 2. Create Users
        $admin = User::create([
            'name' => 'System Administrator',
            'username' => 'admin',
            'email' => 'admin@projectflow.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
            'status' => 'active',
            'last_login_at' => Carbon::now()->subHours(2),
        ]);

        $pm = User::create([
            'name' => 'John Manager',
            'username' => 'john',
            'email' => 'john@projectflow.com',
            'password' => Hash::make('password'),
            'role_id' => $pmRole->id,
            'status' => 'active',
            'last_login_at' => Carbon::now()->subMinutes(15),
        ]);

        $inactiveUser = User::create([
            'name' => 'Sarah Employee',
            'username' => 'sarah',
            'email' => 'sarah@projectflow.com',
            'password' => Hash::make('password'),
            'role_id' => $pmRole->id,
            'status' => 'inactive',
        ]);

        // 3. Create Clients
        $client1 = Client::create([
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
            'phone' => '+1 (555) 123-4567',
            'company' => 'Acme Corp Inc.',
            'address' => '123 Industrial Way, Suite A, Silicon Valley, CA',
            'remarks' => 'Enterprise client since 2024. Prefers bank transfer.',
        ]);

        $client2 = Client::create([
            'name' => 'Stark Industries',
            'email' => 'pepper@stark.com',
            'phone' => '+1 (555) 999-8888',
            'company' => 'Stark Industries LLC',
            'address' => '10880 Malibu Point, Malibu, CA',
            'remarks' => 'High-priority tech client. Fast approval workflow.',
        ]);

        $client3 = Client::create([
            'name' => 'Wayne Enterprises',
            'email' => 'lucius@wayne.com',
            'phone' => '+1 (555) 555-0199',
            'company' => 'Wayne Enterprises Ltd.',
            'address' => 'Wayne Tower, Gotham City, NY',
            'remarks' => 'Consistent recurring projects.',
        ]);

        // 4. Create Projects
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
            'description' => 'A cross-platform React Native mobile application for Acme\'s logistics department tracking drivers and shipments in real-time.',
            'color_label' => 'indigo',
            'tags' => ['Mobile', 'React Native', 'AWS', 'API'],
            'progress' => 45,
            'notes' => 'Milestone 2 completed. Milestone 3 API integrations are underway.',
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
            'description' => 'A self-service portal for Stark Industries customers, powered by Vue 3 and Node.js backend. Integrating ticketing, live chat, and knowledge base.',
            'color_label' => 'emerald',
            'tags' => ['Web Portal', 'Vue 3', 'Tailwind', 'Helpdesk'],
            'progress' => 15,
            'notes' => 'Figma designs approved by client. Setting up workspace.',
        ]);

        $proj3 = Project::create([
            'code' => 'PRJ-2025-0003',
            'name' => 'Omega Legacy ERP Migration',
            'client_id' => $client3->id,
            'manager_id' => $admin->id,
            'start_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'end_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'status' => 'completed',
            'priority' => 'high',
            'budget' => 240000.00,
            'estimated_profit' => 85000.00,
            'description' => 'Comprehensive data migration of Wayne Enterprises\' legacy COBOL ERP system to modern Laravel/MySQL instances with optimized indexing.',
            'color_label' => 'amber',
            'tags' => ['ERP', 'Migration', 'Database', 'Security'],
            'progress' => 100,
            'notes' => 'Successfully signed off. Client team successfully trained on Laravel DB schemas.',
        ]);

        // 5. Create Purchases (Supplier invoices related to projects)
        Purchase::create([
            'purchase_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
            'supplier_name' => 'AWS Web Services',
            'invoice_no' => 'AWS-7890-2026',
            'category' => 'Electricity', // mapped to electricity/hosting
            'amount' => 1500.00,
            'payment_method' => 'Credit Card',
            'project_id' => $proj1->id,
            'remarks' => 'Cloud infrastructure hosting for dev/staging environments.',
        ]);

        Purchase::create([
            'purchase_date' => Carbon::now()->subDays(20)->format('Y-m-d'),
            'supplier_name' => 'ThemeForest Market',
            'invoice_no' => 'TF-55122-VUE',
            'category' => 'Marketing',
            'amount' => 350.00,
            'payment_method' => 'PayPal',
            'project_id' => $proj2->id,
            'remarks' => 'Dashboard UI kit and component layouts.',
        ]);

        Purchase::create([
            'purchase_date' => Carbon::now()->subMonths(4)->format('Y-m-d'),
            'supplier_name' => 'Oracle Database Corp',
            'invoice_no' => 'ORCL-MIG-001',
            'category' => 'Machine Rent',
            'amount' => 15000.00,
            'payment_method' => 'Bank Transfer',
            'project_id' => $proj3->id,
            'remarks' => 'Temporary enterprise migration server licenses.',
        ]);

        // 6. Create Expenses (Internal operational costs paid by staff)
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
            'amount' => 240.00,
            'project_id' => $proj2->id,
            'paid_by' => $pm->id,
            'payment_method' => 'Cash',
            'description' => 'Client kick-off dinner and project scoping meeting.',
            'status' => 'approved',
        ]);

        Expense::create([
            'expense_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
            'category' => 'Transport',
            'amount' => 1200.00,
            'project_id' => $proj3->id,
            'paid_by' => $admin->id,
            'payment_method' => 'Bank Transfer',
            'description' => 'Flights and transport for onsite data assessment in Gotham.',
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

        // 7. Create Incomes (Client payments / billings)
        Income::create([
            'income_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'client_id' => $client1->id,
            'project_id' => $proj1->id,
            'invoice_number' => 'INV-2026-001',
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
            'amount' => 15000.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-1192803',
            'remarks' => 'Kick-off advance deposit (25%).',
        ]);

        Income::create([
            'income_date' => Carbon::now()->subMonths(5)->format('Y-m-d'),
            'client_id' => $client3->id,
            'project_id' => $proj3->id,
            'invoice_number' => 'INV-2025-098',
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
            'amount' => 120000.00,
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'REF-7729903',
            'remarks' => 'Final 50% project delivery sign-off payment.',
        ]);

        // 8. Create Default Settings
        $defaultSettings = [
            'company_name' => 'ProjectFlow Financials Inc.',
            'company_email' => 'finance@projectflow.com',
            'company_phone' => '+1 (555) 888-0000',
            'company_address' => '456 Flow Boulevard, Suite 500, New York, NY',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'theme' => 'light',
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => '2525',
            'smtp_username' => 'pf-smtp-sandbox',
            'smtp_password' => 'pf-pass-12345',
        ];

        foreach ($defaultSettings as $key => $val) {
            Setting::create(['key' => $key, 'value' => $val]);
        }

        // 9. Create Activity Logs
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'login',
            'description' => 'System Administrator logged in',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);

        ActivityLog::create([
            'user_id' => $pm->id,
            'action' => 'login',
            'description' => 'John Manager logged in',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);

        ActivityLog::create([
            'user_id' => $pm->id,
            'action' => 'created',
            'loggable_type' => Project::class,
            'loggable_id' => $proj1->id,
            'description' => 'Project "Alpha Mobile Application" (PRJ-2026-0001) created by John Manager',
            'properties' => ['budget' => 125000.00],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);

        ActivityLog::create([
            'user_id' => $pm->id,
            'action' => 'created',
            'loggable_type' => Project::class,
            'loggable_id' => $proj2->id,
            'description' => 'Project "Beta Customer Support Portal" (PRJ-2026-0002) created by John Manager',
            'properties' => ['budget' => 60000.00],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);

        ActivityLog::create([
            'user_id' => $pm->id,
            'action' => 'updated',
            'loggable_type' => Project::class,
            'loggable_id' => $proj1->id,
            'description' => 'Project "Alpha Mobile Application" status updated to running',
            'properties' => ['status' => 'running', 'progress' => 45],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);

        ActivityLog::create([
            'user_id' => $pm->id,
            'action' => 'created',
            'loggable_type' => Expense::class,
            'loggable_id' => 1,
            'description' => 'Expense of $8,500.00 (Labour) added to Alpha Mobile Application',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);

        ActivityLog::create([
            'user_id' => $pm->id,
            'action' => 'created',
            'loggable_type' => Income::class,
            'loggable_id' => 1,
            'description' => 'Income of $35,000.00 added to Alpha Mobile Application',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ]);
    }
}
