<template>
  <div class="space-y-6 pb-8">
    <!-- Header (No Print) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 no-print">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Financial Reports</h1>
        <p class="text-xs text-slate-500 mt-1">Audit profitability, transactions, budgets, and project ledger summaries.</p>
      </div>
      <div class="flex items-center space-x-2.5">
        <button @click="handlePrint"
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition cursor-pointer">
          <Printer class="w-4 h-4 text-slate-400" />
          <span>Print / PDF</span>
        </button>
        <button @click="exportCSV"
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm cursor-pointer">
          <Download class="w-4 h-4" />
          <span>Export Excel</span>
        </button>
      </div>
    </div>

    <!-- Report Type Tabs (No Print) -->
    <div class="no-print bg-white border border-slate-100 rounded-2xl shadow-sm p-4">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="report in reportTypes"
          :key="report.key"
          @click="switchMode(report.key)"
          :class="[
            'flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-[11px] font-semibold transition cursor-pointer border',
            activeMode === report.key
              ? 'bg-indigo-50 text-indigo-600 border-indigo-200'
              : 'text-slate-500 border-slate-100 hover:bg-slate-50 hover:text-slate-700'
          ]"
        >
          <component :is="report.icon" class="w-3.5 h-3.5" />
          <span>{{ report.label }}</span>
        </button>
      </div>
    </div>

    <!-- Filters (No Print) -->
    <div class="no-print bg-white border border-slate-100 rounded-2xl shadow-sm p-5">
      <h2 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-3">Report Filters</h2>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">

        <!-- Date From -->
        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">From Date</label>
          <DatePicker v-model="filters.start_date" placeholder="Start date" @change="fetchReport" />
        </div>

        <!-- Date To -->
        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">To Date</label>
          <DatePicker v-model="filters.end_date" placeholder="End date" @change="fetchReport" />
        </div>

        <!-- Project Filter -->
        <div>
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Project</label>
          <select v-model="filters.project_id" @change="fetchReport"
            class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-white text-slate-800 focus:outline-none">
            <option value="">All Projects</option>
            <option v-for="proj in projectsList" :key="proj.id" :value="proj.id">{{ proj.name }}</option>
          </select>
        </div>

        <!-- Client Filter -->
        <div v-if="!['expense_category','payment_method'].includes(activeMode)">
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Client</label>
          <select v-model="filters.client_id" @change="fetchReport"
            class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-white text-slate-800 focus:outline-none">
            <option value="">All Clients</option>
            <option v-for="c in clientsList" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div v-if="['profit','budget_utilization'].includes(activeMode)">
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</label>
          <select v-model="filters.status" @change="fetchReport"
            class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-white text-slate-800 focus:outline-none">
            <option value="">All Statuses</option>
            <option value="planning">Planning</option>
            <option value="running">Running</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Category Filter -->
        <div v-if="['expense','expense_category'].includes(activeMode)">
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Category</label>
          <select v-model="filters.category" @change="fetchReport"
            class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-white text-slate-800 focus:outline-none">
            <option value="">All Categories</option>
            <option v-for="cat in expenseCategories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
        </div>

        <!-- Income Category Filter -->
        <div v-if="activeMode === 'income'">
          <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Category</label>
          <select v-model="filters.category" @change="fetchReport"
            class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-white text-slate-800 focus:outline-none">
            <option value="">All Categories</option>
            <option v-for="cat in incomeCategories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Printable Report Card -->
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden printable-card">

      <!-- Print Header (only on print) -->
      <div class="hidden print-header px-8 pt-8 pb-4 border-b border-slate-200">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center space-x-2 mb-1">
              <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center">
                <span class="text-white text-[11px] font-bold">{{ sym }}</span>
              </div>
              <span class="text-base font-bold text-slate-800">{{ settingStore.settings.company_name }}</span>
            </div>
            <h2 class="text-xl font-extrabold text-slate-900 mt-2">{{ currentReport?.label }}</h2>
            <p class="text-[10px] text-slate-400 mt-1">
              Period: {{ filters.start_date || 'All Time' }} to {{ filters.end_date || 'Present' }}
              · Generated: {{ new Date().toLocaleDateString('en-BD', { day:'2-digit', month:'long', year:'numeric' }) }}
            </p>
          </div>
          <div class="text-right text-[10px] text-slate-400 mt-1">
            <p class="font-bold text-slate-700">Confidential Statement</p>
            <p>{{ settingStore.settings.company_email }}</p>
          </div>
        </div>
      </div>

      <!-- Report Summary Totals Bar -->
      <div class="px-6 pt-5 pb-4 border-b border-slate-50">
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
          <div>
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ currentReport?.label }}</h2>
            <p class="text-[10px] text-slate-400 mt-0.5">
              {{ filters.start_date || 'Inception' }} → {{ filters.end_date || 'Present' }}
              <span v-if="filters.project_id" class="ml-2 text-indigo-500">· Filtered by project</span>
            </p>
          </div>
          <div class="flex flex-wrap gap-4">
            <div v-for="kpi in summaryKPIs" :key="kpi.label" class="text-right">
              <p class="text-[10px] text-slate-400">{{ kpi.label }}</p>
              <p :class="['text-sm font-bold', kpi.color]">{{ kpi.value }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="py-16 flex justify-center">
        <svg class="animate-spin h-7 w-7 text-indigo-500" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>

      <div v-else class="overflow-x-auto">

        <!-- ① Profit Report -->
        <table v-if="activeMode === 'profit'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Code</th>
              <th class="px-6 py-3">Project</th>
              <th class="px-6 py-3">Client</th>
              <th class="px-6 py-3">Manager</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Budget</th>
              <th class="px-6 py-3 text-right">Spent</th>
              <th class="px-6 py-3 text-right">Income</th>
              <th class="px-6 py-3 text-right">Net Profit</th>
              <th class="px-6 py-3 text-right">Margin</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="item in reportData" :key="item.project_id" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5 font-mono text-slate-500 text-[10px]">{{ item.code }}</td>
              <td class="px-6 py-3.5 font-bold text-slate-800">{{ item.name }}</td>
              <td class="px-6 py-3.5 text-slate-600">{{ item.client }}</td>
              <td class="px-6 py-3.5 text-slate-500">{{ item.manager }}</td>
              <td class="px-6 py-3.5">
                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold border', statusClass(item.status)]">{{ item.status }}</span>
              </td>
              <td class="px-6 py-3.5 text-right font-semibold text-slate-700">{{ sym }}{{ Number(item.budget).toLocaleString() }}</td>
              <td class="px-6 py-3.5 text-right text-rose-500 font-semibold">{{ sym }}{{ Number(item.spent).toLocaleString() }}</td>
              <td class="px-6 py-3.5 text-right text-emerald-600 font-semibold">{{ sym }}{{ Number(item.income).toLocaleString() }}</td>
              <td :class="['px-6 py-3.5 text-right font-bold', Number(item.profit) >= 0 ? 'text-slate-800' : 'text-rose-500']">
                {{ sym }}{{ Number(item.profit).toLocaleString() }}
              </td>
              <td class="px-6 py-3.5 text-right font-bold text-indigo-600">{{ item.margin }}%</td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="10" class="px-6 py-12 text-center text-slate-400">No data found for selected filters.</td>
            </tr>
          </tbody>
        </table>

        <!-- ② Expense Ledger -->
        <table v-else-if="activeMode === 'expense'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Date</th>
              <th class="px-6 py-3">Category</th>
              <th class="px-6 py-3">Project</th>
              <th class="px-6 py-3">Paid By</th>
              <th class="px-6 py-3">Method</th>
              <th class="px-6 py-3">Description</th>
              <th class="px-6 py-3 text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(item, idx) in reportData" :key="idx" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5 text-slate-500">{{ formatDate(item.date) }}</td>
              <td class="px-6 py-3.5 font-semibold text-slate-800">{{ item.category }}</td>
              <td class="px-6 py-3.5 text-slate-600">{{ item.project }}</td>
              <td class="px-6 py-3.5 text-slate-500">{{ item.party }}</td>
              <td class="px-6 py-3.5 text-slate-500">{{ item.payment_method }}</td>
              <td class="px-6 py-3.5 text-slate-400 max-w-xs truncate">{{ item.description || '—' }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-rose-500">-{{ sym }}{{ Number(item.amount).toLocaleString() }}</td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">No expense records found.</td>
            </tr>
          </tbody>
        </table>

        <!-- ③ Income Collections -->
        <table v-else-if="activeMode === 'income'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Date</th>
              <th class="px-6 py-3">Invoice #</th>
              <th class="px-6 py-3">Category</th>
              <th class="px-6 py-3">Client</th>
              <th class="px-6 py-3">Project</th>
              <th class="px-6 py-3">Method</th>
              <th class="px-6 py-3">Reference</th>
              <th class="px-6 py-3 text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(item, idx) in reportData" :key="idx" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5 text-slate-500">{{ formatDate(item.date) }}</td>
              <td class="px-6 py-3.5 font-mono text-slate-700">{{ item.invoice_number }}</td>
              <td class="px-6 py-3.5"><span class="px-2 py-0.5 rounded text-[10px] bg-emerald-50 text-emerald-600 border border-emerald-100">{{ item.category || '—' }}</span></td>
              <td class="px-6 py-3.5 font-bold text-slate-800">{{ item.client }}</td>
              <td class="px-6 py-3.5 text-slate-600">{{ item.project }}</td>
              <td class="px-6 py-3.5 text-slate-500">{{ item.payment_method }}</td>
              <td class="px-6 py-3.5 text-slate-400 font-mono">{{ item.reference || '—' }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-emerald-600">+{{ sym }}{{ Number(item.amount).toLocaleString() }}</td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="8" class="px-6 py-12 text-center text-slate-400">No income records found.</td>
            </tr>
          </tbody>
        </table>

        <!-- ④ Budget Utilization -->
        <table v-else-if="activeMode === 'budget_utilization'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Project</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Budget</th>
              <th class="px-6 py-3 text-right">Spent</th>
              <th class="px-6 py-3 text-right">Remaining</th>
              <th class="px-6 py-3" style="min-width:160px">Utilization</th>
              <th class="px-6 py-3 text-right">%</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="item in reportData" :key="item.project_id" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5">
                <p class="font-bold text-slate-800">{{ item.name }}</p>
                <p class="font-mono text-[10px] text-slate-400">{{ item.code }}</p>
              </td>
              <td class="px-6 py-3.5">
                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-semibold border', statusClass(item.status)]">{{ item.status }}</span>
              </td>
              <td class="px-6 py-3.5 text-right text-slate-700 font-semibold">{{ sym }}{{ Number(item.budget).toLocaleString() }}</td>
              <td class="px-6 py-3.5 text-right text-rose-500 font-semibold">{{ sym }}{{ Number(item.spent).toLocaleString() }}</td>
              <td :class="['px-6 py-3.5 text-right font-bold', Number(item.budget - item.spent) >= 0 ? 'text-emerald-600' : 'text-rose-500']">
                {{ sym }}{{ Math.abs(Number(item.budget) - Number(item.spent)).toLocaleString() }}
                <span v-if="Number(item.budget) - Number(item.spent) < 0" class="text-[9px]">over</span>
              </td>
              <td class="px-6 py-3.5">
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div :style="{ width: Math.min(item.margin, 100) + '%' }"
                    :class="['h-full rounded-full', Number(item.margin) > 90 ? 'bg-rose-500' : Number(item.margin) > 70 ? 'bg-amber-500' : 'bg-indigo-500']">
                  </div>
                </div>
              </td>
              <td :class="['px-6 py-3.5 text-right font-bold text-[11px]', Number(item.margin) > 90 ? 'text-rose-500' : Number(item.margin) > 70 ? 'text-amber-600' : 'text-indigo-600']">
                {{ item.margin }}%
              </td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">No budget data available.</td>
            </tr>
          </tbody>
        </table>

        <!-- ⑤ Expense by Category -->
        <table v-else-if="activeMode === 'expense_category'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Category</th>
              <th class="px-6 py-3 text-right">Transactions</th>
              <th class="px-6 py-3 text-right">Total Amount</th>
              <th class="px-6 py-3 text-right">Avg per Txn</th>
              <th class="px-6 py-3" style="min-width:200px">Share of Total</th>
              <th class="px-6 py-3 text-right">%</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(item, idx) in reportData" :key="idx" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5">
                <div class="flex items-center space-x-2">
                  <div :class="['w-2.5 h-2.5 rounded-full flex-shrink-0', catColors[idx % catColors.length]]"></div>
                  <span class="font-bold text-slate-800">{{ item.category }}</span>
                </div>
              </td>
              <td class="px-6 py-3.5 text-right text-slate-600">{{ item.count }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-slate-800">{{ sym }}{{ Number(item.total).toLocaleString() }}</td>
              <td class="px-6 py-3.5 text-right text-slate-500">{{ sym }}{{ Math.round(item.total / item.count).toLocaleString() }}</td>
              <td class="px-6 py-3.5">
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div :style="{ width: item.percentage + '%' }" :class="['h-full rounded-full', catColors[idx % catColors.length].replace('bg-', 'bg-')]"></div>
                </div>
              </td>
              <td class="px-6 py-3.5 text-right font-bold text-slate-600">{{ item.percentage }}%</td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">No expense category data.</td>
            </tr>
          </tbody>
        </table>

        <!-- ⑥ Client Revenue -->
        <table v-else-if="activeMode === 'client_revenue'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Client</th>
              <th class="px-6 py-3">Company</th>
              <th class="px-6 py-3 text-right">Projects</th>
              <th class="px-6 py-3 text-right">Total Income</th>
              <th class="px-6 py-3 text-right">Total Spent</th>
              <th class="px-6 py-3 text-right">Net Profit</th>
              <th class="px-6 py-3 text-right">Margin</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(item, idx) in reportData" :key="idx" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5 font-bold text-slate-800">{{ item.client }}</td>
              <td class="px-6 py-3.5 text-slate-500">{{ item.company || '—' }}</td>
              <td class="px-6 py-3.5 text-right text-slate-600 font-semibold">{{ item.project_count }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-emerald-600">{{ sym }}{{ Number(item.income).toLocaleString() }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-rose-500">{{ sym }}{{ Number(item.spent).toLocaleString() }}</td>
              <td :class="['px-6 py-3.5 text-right font-bold', Number(item.profit) >= 0 ? 'text-slate-800' : 'text-rose-500']">
                {{ sym }}{{ Number(item.profit).toLocaleString() }}
              </td>
              <td class="px-6 py-3.5 text-right font-bold text-indigo-600">{{ item.margin }}%</td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">No client revenue data.</td>
            </tr>
          </tbody>
        </table>

        <!-- ⑧ Payment Method Analysis -->
        <table v-else-if="activeMode === 'payment_method'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Payment Method</th>
              <th class="px-6 py-3 text-right">Income Txns</th>
              <th class="px-6 py-3 text-right">Income Amount</th>
              <th class="px-6 py-3 text-right">Expense Txns</th>
              <th class="px-6 py-3 text-right">Expense Amount</th>
              <th class="px-6 py-3 text-right">Net Flow</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(item, idx) in reportData" :key="idx" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5">
                <div class="flex items-center space-x-2">
                  <component :is="methodIcon(item.method)" class="w-4 h-4 text-slate-400" />
                  <span class="font-bold text-slate-800">{{ item.method }}</span>
                </div>
              </td>
              <td class="px-6 py-3.5 text-right text-slate-600">{{ item.income_count }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-emerald-600">{{ sym }}{{ Number(item.income_total).toLocaleString() }}</td>
              <td class="px-6 py-3.5 text-right text-slate-600">{{ item.expense_count }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-rose-500">{{ sym }}{{ Number(item.expense_total).toLocaleString() }}</td>
              <td :class="['px-6 py-3.5 text-right font-bold', Number(item.net) >= 0 ? 'text-indigo-600' : 'text-rose-600']">
                {{ sym }}{{ Number(item.net).toLocaleString() }}
              </td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">No payment method data.</td>
            </tr>
          </tbody>
        </table>

        <!-- ⑨ Top Expense Transactions -->
        <table v-else-if="activeMode === 'top_expenses'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">#</th>
              <th class="px-6 py-3">Date</th>
              <th class="px-6 py-3">Category</th>
              <th class="px-6 py-3">Project</th>
              <th class="px-6 py-3">Paid By</th>
              <th class="px-6 py-3">Description</th>
              <th class="px-6 py-3 text-right">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(item, idx) in reportData.slice(0, 25)" :key="idx" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5 text-slate-400 font-bold">{{ idx + 1 }}</td>
              <td class="px-6 py-3.5 text-slate-500">{{ formatDate(item.date) }}</td>
              <td class="px-6 py-3.5 font-semibold text-slate-800">{{ item.category }}</td>
              <td class="px-6 py-3.5 text-slate-600">{{ item.project }}</td>
              <td class="px-6 py-3.5 text-slate-500">{{ item.party }}</td>
              <td class="px-6 py-3.5 text-slate-400 max-w-xs truncate">{{ item.description || '—' }}</td>
              <td class="px-6 py-3.5 text-right font-bold text-rose-500 text-sm">{{ sym }}{{ Number(item.amount).toLocaleString() }}</td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">No expense data.</td>
            </tr>
          </tbody>
        </table>

        <!-- ⑩ Monthly Trend -->
        <table v-else-if="activeMode === 'monthly_trend'" class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
              <th class="px-6 py-3">Month</th>
              <th class="px-6 py-3 text-right">Income</th>
              <th class="px-6 py-3 text-right">Expenses</th>
              <th class="px-6 py-3 text-right">Net Profit</th>
              <th class="px-6 py-3">Trend</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(item, idx) in reportData" :key="idx" class="hover:bg-slate-50/50 transition">
              <td class="px-6 py-3.5 font-bold text-slate-800">{{ item.month }}</td>
              <td class="px-6 py-3.5 text-right font-semibold text-emerald-600">{{ sym }}{{ Number(item.income).toLocaleString() }}</td>
              <td class="px-6 py-3.5 text-right font-semibold text-rose-500">{{ sym }}{{ Number(item.expenses).toLocaleString() }}</td>
              <td :class="['px-6 py-3.5 text-right font-bold', Number(item.profit) >= 0 ? 'text-indigo-600' : 'text-rose-500']">
                {{ sym }}{{ Number(item.profit).toLocaleString() }}
              </td>
              <td class="px-6 py-3.5">
                <div class="flex items-center space-x-1">
                  <div class="h-4 bg-emerald-100 rounded" :style="{ width: Math.min((item.income / maxIncome) * 80, 80) + 'px' }"></div>
                  <div class="h-4 bg-rose-100 rounded" :style="{ width: Math.min((item.expenses / maxIncome) * 80, 80) + 'px' }"></div>
                </div>
              </td>
            </tr>
            <tr v-if="reportData.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-slate-400">No monthly trend data available.</td>
            </tr>
          </tbody>
        </table>

      </div>

      <!-- Table Footer totals row -->
      <div v-if="!loading && reportData.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
        <div class="flex justify-between items-center">
          <span class="text-[10px] text-slate-400 font-semibold">{{ reportData.length }} records · {{ currentReport?.label }}</span>
          <div class="flex space-x-6 text-xs">
            <span v-for="kpi in summaryKPIs" :key="kpi.label" class="text-slate-500">
              {{ kpi.label }}: <span :class="['font-bold', kpi.color]">{{ kpi.value }}</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useSettingStore } from '@/stores/settings';
import {
  Printer, Download,
  TrendingUp, Receipt, Wallet, PieChart,
  Users, CreditCard, AlertTriangle,
  BarChart3, Calendar, Landmark, Banknote
} from 'lucide-vue-next';
import axios from 'axios';
import { formatDate } from '@/utils/date';
import DatePicker from '@/components/ui/DatePicker.vue';

const settingStore = useSettingStore();
const sym = computed(() => settingStore.settings.currency_symbol || '৳');

const loading = ref(true);
const activeMode = ref('profit');
const projectsList = ref([]);
const clientsList = ref([]);
const reportData = ref([]);

const catColors = [
  'bg-indigo-400', 'bg-emerald-400', 'bg-rose-400',
  'bg-amber-400', 'bg-cyan-400', 'bg-violet-400',
  'bg-pink-400', 'bg-teal-400', 'bg-orange-400', 'bg-blue-400'
];

const reportTypes = [
  { key: 'profit',            label: 'Project Profitability',    icon: TrendingUp },
  { key: 'expense',           label: 'Expense Ledger',           icon: Receipt },
  { key: 'income',            label: 'Income Collections',       icon: Wallet },
  { key: 'budget_utilization',label: 'Budget Utilization',       icon: PieChart },
  { key: 'expense_category',  label: 'Expense by Category',      icon: BarChart3 },
  { key: 'client_revenue',    label: 'Client Revenue',           icon: Users },
  { key: 'payment_method',    label: 'Payment Method Analysis',  icon: CreditCard },
  { key: 'top_expenses',      label: 'Top Expenses',             icon: AlertTriangle },
  { key: 'monthly_trend',     label: 'Monthly Trend',            icon: Calendar },
];

const currentReport = computed(() => reportTypes.find(r => r.key === activeMode.value));

function switchMode(key) {
  activeMode.value = key;
  filters.category = '';
  fetchReport();
}

const expenseCategories = ref([]);
const incomeCategories = ref([]);

async function fetchCategories() {
  try {
    const response = await axios.get('/categories?active_only=1');
    const all = response.data.data || [];
    expenseCategories.value = all.filter(c => c.type === 'expense').map(c => c.name);
    incomeCategories.value = all.filter(c => c.type === 'income').map(c => c.name);
  } catch (e) { console.error(e); }
}

const filters = reactive({
  start_date: '',
  end_date: '',
  project_id: '',
  client_id: '',
  category: '',
  status: ''
});

const totals = reactive({ income: 0, spent: 0, profit: 0 });

const maxIncome = computed(() => {
  if (!reportData.value.length) return 1;
  return Math.max(...reportData.value.map(i => Number(i.income || 0)), 1);
});

const summaryKPIs = computed(() => {
  const kpis = [];
  if (['profit', 'income', 'client_revenue', 'monthly_trend'].includes(activeMode.value)) {
    kpis.push({ label: 'Total Income', value: `${sym.value}${totals.income.toLocaleString()}`, color: 'text-emerald-600' });
  }
  if (['profit', 'expense', 'expense_category', 'budget_utilization', 'monthly_trend', 'client_revenue'].includes(activeMode.value)) {
    kpis.push({ label: 'Total Spent', value: `${sym.value}${totals.spent.toLocaleString()}`, color: 'text-rose-500' });
  }
  if (['profit', 'budget_utilization', 'client_revenue', 'monthly_trend'].includes(activeMode.value)) {
    kpis.push({ label: 'Net Profit', value: `${sym.value}${totals.profit.toLocaleString()}`, color: totals.profit >= 0 ? 'text-indigo-600' : 'text-rose-500' });
  }
  return kpis;
});

function statusClass(status) {
  const map = {
    running:   'bg-emerald-50 text-emerald-700 border-emerald-100',
    completed: 'bg-indigo-50 text-indigo-700 border-indigo-100',
    planning:  'bg-amber-50 text-amber-700 border-amber-100',
    cancelled: 'bg-rose-50 text-rose-600 border-rose-100',
  };
  return map[status] || 'bg-slate-50 text-slate-600 border-slate-200';
}

function methodIcon(method) {
  if (!method) return Banknote;
  const m = method.toLowerCase();
  if (m.includes('bank') || m.includes('transfer')) return Landmark;
  if (m.includes('card') || m.includes('credit')) return CreditCard;
  return Banknote;
}

function buildQuery() {
  const q = [];
  if (filters.start_date)  q.push(`start_date=${filters.start_date}`);
  if (filters.end_date)    q.push(`end_date=${filters.end_date}`);
  if (filters.project_id)  q.push(`project_id=${filters.project_id}`);
  if (filters.client_id)   q.push(`client_id=${filters.client_id}`);
  if (filters.category)    q.push(`category=${filters.category}`);
  if (filters.status)      q.push(`status=${filters.status}`);
  return q.length ? '?' + q.join('&') : '';
}

// Map report modes → API endpoints (fallback to profit for modes without dedicated APIs)
const endpointMap = {
  profit:             '/reports/profit',
  expense:            '/reports/expense',
  income:             '/reports/income',
  budget_utilization: '/reports/profit',  // reuse profit data, we reformat it
  expense_category:   '/reports/expense', // aggregate client-side
  client_revenue:     '/reports/profit',  // aggregate by client client-side
  top_expenses:       '/reports/expense', // sort by amount desc
  monthly_trend:      '/reports/profit',  // aggregate monthly
};

async function fetchReport() {
  loading.value = true;
  try {
    let data;
    if (activeMode.value === 'payment_method') {
      // Merge both ledgers so income and expense methods are analysed together
      const [expRes, incRes] = await Promise.all([
        axios.get('/reports/expense' + buildQuery()),
        axios.get('/reports/income' + buildQuery())
      ]);
      data = [
        ...(expRes.data.data || []).map(i => ({ ...i, flow: 'expense' })),
        ...(incRes.data.data || []).map(i => ({ ...i, flow: 'income' }))
      ];
    } else {
      const endpoint = endpointMap[activeMode.value] || '/reports/profit';
      const response = await axios.get(endpoint + buildQuery());
      data = response.data.data;
    }

    reportData.value = transformData(data);
    calculateTotals();
  } catch (error) {
    console.error(error);
    reportData.value = [];
  } finally {
    loading.value = false;
  }
}

function transformData(raw) {
  switch (activeMode.value) {
    case 'budget_utilization':
      return (raw || []).map(item => ({
        ...item,
        margin: item.budget > 0 ? Math.round((item.spent / item.budget) * 100) : 0
      }));

    case 'expense_category': {
      const grouped = {};
      (raw || []).forEach(item => {
        const cat = item.category || 'Uncategorized';
        if (!grouped[cat]) grouped[cat] = { category: cat, count: 0, total: 0 };
        grouped[cat].count++;
        grouped[cat].total += parseFloat(item.amount || 0);
      });
      const grandTotal = Object.values(grouped).reduce((s, g) => s + g.total, 0);
      return Object.values(grouped)
        .sort((a, b) => b.total - a.total)
        .map(g => ({ ...g, percentage: grandTotal > 0 ? Math.round((g.total / grandTotal) * 100) : 0 }));
    }

    case 'client_revenue': {
      const grouped = {};
      (raw || []).forEach(item => {
        const key = item.client || 'Unknown';
        if (!grouped[key]) grouped[key] = { client: key, company: item.company || '', project_count: 0, income: 0, spent: 0, profit: 0 };
        grouped[key].project_count++;
        grouped[key].income += parseFloat(item.income || 0);
        grouped[key].spent  += parseFloat(item.spent || 0);
        grouped[key].profit += parseFloat(item.profit || 0);
      });
      return Object.values(grouped)
        .sort((a, b) => b.income - a.income)
        .map(g => ({ ...g, margin: g.income > 0 ? Math.round((g.profit / g.income) * 100) : 0 }));
    }

    case 'payment_method': {
      const grouped = {};
      (raw || []).forEach(item => {
        const m = item.payment_method || 'Unknown';
        if (!grouped[m]) grouped[m] = { method: m, income_count: 0, income_total: 0, expense_count: 0, expense_total: 0 };
        if (item.flow === 'income') {
          grouped[m].income_count++;
          grouped[m].income_total += parseFloat(item.amount || 0);
        } else {
          grouped[m].expense_count++;
          grouped[m].expense_total += parseFloat(item.amount || 0);
        }
      });
      return Object.values(grouped).map(g => ({ ...g, net: g.income_total - g.expense_total }));
    }

    case 'top_expenses':
      return (raw || []).sort((a, b) => parseFloat(b.amount) - parseFloat(a.amount));

    case 'monthly_trend': {
      const grouped = {};
      (raw || []).forEach(item => {
        // Use project start dates grouped by month (rough approximation using spent/income)
        const month = item.start_date ? item.start_date.substring(0, 7) : 'Unknown';
        if (!grouped[month]) grouped[month] = { month, income: 0, expenses: 0, profit: 0 };
        grouped[month].income   += parseFloat(item.income || 0);
        grouped[month].expenses += parseFloat(item.spent || 0);
        grouped[month].profit   += parseFloat(item.profit || 0);
      });
      return Object.entries(grouped)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([, v]) => ({ ...v, month: new Date(v.month + '-01').toLocaleDateString('en-BD', { month: 'long', year: 'numeric' }) }));
    }

    default:
      return raw || [];
  }
}

function calculateTotals() {
  totals.income = 0;
  totals.spent = 0;
  totals.profit = 0;

  const data = reportData.value;
  if (['profit', 'budget_utilization'].includes(activeMode.value)) {
    data.forEach(i => { totals.income += parseFloat(i.income || 0); totals.spent += parseFloat(i.spent || 0); totals.profit += parseFloat(i.profit || 0); });
  } else if (['expense', 'expense_category', 'top_expenses'].includes(activeMode.value)) {
    data.forEach(i => { totals.spent += parseFloat(i.total || i.amount || 0); });
  } else if (activeMode.value === 'income') {
    data.forEach(i => { totals.income += parseFloat(i.amount || 0); });
  } else if (activeMode.value === 'client_revenue') {
    data.forEach(i => { totals.income += parseFloat(i.income || 0); totals.spent += parseFloat(i.spent || 0); totals.profit += parseFloat(i.profit || 0); });
  } else if (activeMode.value === 'monthly_trend') {
    data.forEach(i => { totals.income += parseFloat(i.income || 0); totals.spent += parseFloat(i.expenses || 0); totals.profit += parseFloat(i.profit || 0); });
  } else if (activeMode.value === 'payment_method') {
    data.forEach(i => { totals.income += parseFloat(i.income_total || 0); totals.spent += parseFloat(i.expense_total || 0); });
  }
}

async function fetchDropdowns() {
  try {
    const [projRes, clientRes] = await Promise.all([
      axios.get('/projects?per_page=200'),
      axios.get('/clients?per_page=200')
    ]);
    projectsList.value = projRes.data.data.data || [];
    clientsList.value  = clientRes.data.data.data || [];
  } catch (e) { console.error(e); }
}

function handlePrint() { window.print(); }

function exportCSV() {
  let csv = '';
  const filename = `projectflow_${activeMode.value}_report.csv`;
  const s = sym.value;

  if (activeMode.value === 'profit' || activeMode.value === 'budget_utilization') {
    csv = 'Code,Project,Client,Manager,Status,Budget,Spent,Income,Net Profit,Margin(%)\n';
    reportData.value.forEach(i => {
      csv += `"${i.code}","${i.name}","${i.client}","${i.manager}","${i.status}",${i.budget},${i.spent},${i.income},${i.profit},${i.margin}\n`;
    });
  } else if (activeMode.value === 'expense' || activeMode.value === 'top_expenses') {
    csv = 'Date,Category,Project,Paid By,Method,Description,Amount\n';
    reportData.value.forEach(i => {
      csv += `"${i.date}","${i.category}","${i.project}","${i.party}","${i.payment_method}","${i.description || ''}",${i.amount}\n`;
    });
  } else if (activeMode.value === 'income') {
    csv = 'Date,Invoice,Category,Client,Project,Method,Reference,Remarks,Amount\n';
    reportData.value.forEach(i => {
      csv += `"${i.date}","${i.invoice_number}","${i.category || ''}","${i.client}","${i.project}","${i.payment_method}","${i.reference || ''}","${i.remarks || ''}",${i.amount}\n`;
    });
  } else if (activeMode.value === 'expense_category') {
    csv = 'Category,Transactions,Total Amount,Share(%)\n';
    reportData.value.forEach(i => { csv += `"${i.category}",${i.count},${i.total},${i.percentage}\n`; });
  } else if (activeMode.value === 'client_revenue') {
    csv = 'Client,Company,Projects,Income,Spent,Net Profit,Margin(%)\n';
    reportData.value.forEach(i => { csv += `"${i.client}","${i.company}",${i.project_count},${i.income},${i.spent},${i.profit},${i.margin}\n`; });
  } else if (activeMode.value === 'payment_method') {
    csv = 'Method,Income Txns,Income Amount,Expense Txns,Expense Amount,Net Flow\n';
    reportData.value.forEach(i => { csv += `"${i.method}",${i.income_count},${i.income_total},${i.expense_count},${i.expense_total},${i.net}\n`; });
  } else if (activeMode.value === 'monthly_trend') {
    csv = 'Month,Income,Expenses,Net Profit\n';
    reportData.value.forEach(i => { csv += `"${i.month}",${i.income},${i.expenses},${i.profit}\n`; });
  }

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

onMounted(() => {
  fetchReport();
  fetchDropdowns();
  fetchCategories();
});
</script>

<style>
@media print {
  aside, header, .no-print, nav { display: none !important; }
  main { padding: 0 !important; }
  .printable-card { border: none !important; box-shadow: none !important; }
  .print-header { display: flex !important; flex-direction: column; }
  body { font-size: 11px !important; }
  table { page-break-inside: auto; }
  tr { page-break-inside: avoid; }
}
</style>
