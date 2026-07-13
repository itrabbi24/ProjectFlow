<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\Income;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getDashboardStats(): array
    {
        $totalProjects = Project::count();
        $runningProjects = Project::where('status', 'running')->count();
        $completedProjects = Project::where('status', 'completed')->count();

        $totalIncome = (float) Income::sum('amount');
        
        $totalExpenses = (float) Expense::where('status', 'approved')->sum('amount');
        $totalPurchases = (float) Purchase::sum('amount');
        $totalSpent = $totalExpenses + $totalPurchases;

        $netProfit = $totalIncome - $totalSpent;
        $cashInHand = $totalIncome - $totalSpent; // Cash flow balance

        $todayExpense = (float) Expense::whereDate('expense_date', Carbon::today())
            ->where('status', 'approved')
            ->sum('amount') 
            + (float) Purchase::whereDate('purchase_date', Carbon::today())->sum('amount');

        $todayIncome = (float) Income::whereDate('income_date', Carbon::today())->sum('amount');

        // Recent projects
        $recentProjects = Project::with('client', 'manager')
            ->latest()
            ->limit(5)
            ->get();

        // Recent transactions (merged logs of incomes, expenses, purchases)
        $incomes = Income::with('project')
            ->select('id', 'income_date as date', DB::raw('"income" as type'), 'amount', 'payment_method', 'project_id', 'invoice_number as reference')
            ->latest('income_date')
            ->limit(5)
            ->get();

        $expenses = Expense::with('project')
            ->select('id', 'expense_date as date', DB::raw('"expense" as type'), 'amount', 'payment_method', 'project_id', 'category as reference')
            ->where('status', 'approved')
            ->latest('expense_date')
            ->limit(5)
            ->get();

        $purchases = Purchase::with('project')
            ->select('id', 'purchase_date as date', DB::raw('"purchase" as type'), 'amount', 'payment_method', 'project_id', 'supplier_name as reference')
            ->latest('purchase_date')
            ->limit(5)
            ->get();

        $recentTransactions = $incomes->concat($expenses)->concat($purchases)
            ->sortByDesc('date')
            ->take(8)
            ->values();

        // Chart data
        $year = Carbon::now()->year;
        $monthlyIncome = array_fill(1, 12, 0.00);
        $monthlyExpense = array_fill(1, 12, 0.00);
        $monthlyProfit = array_fill(1, 12, 0.00);

        $dbIncomes = Income::selectRaw('MONTH(income_date) as month, SUM(amount) as total')
            ->whereYear('income_date', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        $dbExpenses = Expense::selectRaw('MONTH(expense_date) as month, SUM(amount) as total')
            ->whereYear('expense_date', $year)
            ->where('status', 'approved')
            ->groupBy('month')
            ->pluck('total', 'month');

        $dbPurchases = Purchase::selectRaw('MONTH(purchase_date) as month, SUM(amount) as total')
            ->whereYear('purchase_date', $year)
            ->groupBy('month')
            ->pluck('total', 'month');

        for ($m = 1; $m <= 12; $m++) {
            $inc = (float) ($dbIncomes[$m] ?? 0);
            $exp = (float) ($dbExpenses[$m] ?? 0) + (float) ($dbPurchases[$m] ?? 0);
            
            $monthlyIncome[$m] = $inc;
            $monthlyExpense[$m] = $exp;
            $monthlyProfit[$m] = $inc - $exp;
        }

        // Top expense categories (combining approved expenses + purchases)
        $expenseCategories = Expense::selectRaw('category, SUM(amount) as total')
            ->where('status', 'approved')
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->toArray();

        $purchaseCategories = Purchase::selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category')
            ->toArray();

        $categories = array_keys(array_merge($expenseCategories, $purchaseCategories));
        $topCategories = [];
        foreach ($categories as $cat) {
            $total = (float) ($expenseCategories[$cat] ?? 0) + (float) ($purchaseCategories[$cat] ?? 0);
            $topCategories[] = [
                'name' => $cat,
                'value' => $total
            ];
        }
        usort($topCategories, fn($a, $b) => $b['value'] <=> $a['value']);
        $topCategories = array_slice($topCategories, 0, 5);

        return [
            'total_projects' => $totalProjects,
            'running_projects' => $runningProjects,
            'completed_projects' => $completedProjects,
            'total_income' => $totalIncome,
            'total_spent' => $totalSpent,
            'net_profit' => $netProfit,
            'cash_in_hand' => $cashInHand,
            'today_expense' => $todayExpense,
            'today_income' => $todayIncome,
            'recent_projects' => $recentProjects,
            'recent_transactions' => $recentTransactions,
            'charts' => [
                'months' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'income' => array_values($monthlyIncome),
                'expense' => array_values($monthlyExpense),
                'profit' => array_values($monthlyProfit)
            ],
            'top_categories' => $topCategories
        ];
    }

    public function getProjectProfitReport(array $filters): array
    {
        $query = Project::with('client', 'manager');

        if (!empty($filters['project_id'])) {
            $query->where('id', $filters['project_id']);
        }
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $projects = $query->get();
        $report = [];

        foreach ($projects as $proj) {
            $budget = (float) $proj->budget;
            $income = (float) $proj->incomes()->sum('amount');
            $expenses = (float) $proj->expenses()->where('status', 'approved')->sum('amount');
            $purchases = (float) $proj->purchases()->sum('amount');
            $spent = $expenses + $purchases;
            $profit = $income - $spent;
            $margin = $income > 0 ? round(($profit / $income) * 100, 2) : 0;

            $report[] = [
                'project_id' => $proj->id,
                'code' => $proj->code,
                'name' => $proj->name,
                'client' => $proj->client->name,
                'manager' => $proj->manager->name,
                'status' => $proj->status,
                'budget' => $budget,
                'income' => $income,
                'spent' => $spent,
                'profit' => $profit,
                'margin' => $margin
            ];
        }

        return $report;
    }

    public function getExpenseReport(array $filters): array
    {
        $query = Expense::with('project', 'paidBy')->where('status', 'approved');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('expense_date', [$filters['start_date'], $filters['end_date']]);
        }
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $expenses = $query->get();
        
        $purchaseQuery = Purchase::with('project');
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $purchaseQuery->whereBetween('purchase_date', [$filters['start_date'], $filters['end_date']]);
        }
        if (!empty($filters['project_id'])) {
            $purchaseQuery->where('project_id', $filters['project_id']);
        }
        if (!empty($filters['category'])) {
            $purchaseQuery->where('category', $filters['category']);
        }
        
        $purchases = $purchaseQuery->get();

        $report = [];

        foreach ($expenses as $exp) {
            $report[] = [
                'date' => $exp->expense_date->format('Y-m-d'),
                'type' => 'Expense',
                'category' => $exp->category,
                'project' => $exp->project->name,
                'project_code' => $exp->project->code,
                'party' => $exp->paidBy->name,
                'payment_method' => $exp->payment_method,
                'amount' => (float) $exp->amount,
                'description' => $exp->description
            ];
        }

        foreach ($purchases as $pur) {
            $report[] = [
                'date' => $pur->purchase_date->format('Y-m-d'),
                'type' => 'Purchase',
                'category' => $pur->category,
                'project' => $pur->project->name,
                'project_code' => $pur->project->code,
                'party' => $pur->supplier_name,
                'payment_method' => $pur->payment_method,
                'amount' => (float) $pur->amount,
                'description' => $pur->remarks
            ];
        }

        usort($report, fn($a, $b) => strcmp($b['date'], $a['date']));

        return $report;
    }

    public function getPurchaseSummaryReport(array $filters): array
    {
        $query = Purchase::with('project');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('purchase_date', [$filters['start_date'], $filters['end_date']]);
        }
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        return $query->latest('purchase_date')->get()->map(fn($pur) => [
            'date' => $pur->purchase_date->format('Y-m-d'),
            'supplier' => $pur->supplier_name,
            'item' => $pur->category,
            'project' => $pur->project->name,
            'project_code' => $pur->project->code,
            'payment_method' => $pur->payment_method,
            'amount' => (float) $pur->amount,
        ])->toArray();
    }

    public function getIncomeReport(array $filters): array
    {
        $query = Income::with('project', 'client');

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('income_date', [$filters['start_date'], $filters['end_date']]);
        }
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        return $query->get()->map(fn($inc) => [
            'date' => $inc->income_date->format('Y-m-d'),
            'invoice_number' => $inc->invoice_number,
            'client' => $inc->client->name,
            'project' => $inc->project->name,
            'project_code' => $inc->project->code,
            'amount' => (float) $inc->amount,
            'payment_method' => $inc->payment_method,
            'reference' => $inc->reference_number,
            'remarks' => $inc->remarks
        ])->toArray();
    }
}
