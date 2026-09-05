<template>
  <div class="space-y-6 pb-8">
    <!-- Quick Actions Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Overview</h1>
        <p class="text-xs text-slate-500 mt-1">Real-time status of ProjectFlow financial portfolios.</p>
      </div>
      
      <!-- Quick Action Buttons -->
      <div class="flex items-center space-x-2.5">
        <router-link 
          to="/projects"
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition cursor-pointer shadow-sm shadow-indigo-150"
        >
          <Plus class="w-4 h-4" />
          <span>New Project</span>
        </router-link>
        <router-link 
          to="/expenses"
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 transition cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Log Expense</span>
        </router-link>
      </div>
    </div>

    <!-- Loading Skeleton Grid -->
    <div v-if="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="i in 4" :key="i" class="bg-white border border-slate-100 p-5 rounded-2xl animate-pulse space-y-3">
        <div class="w-10 h-10 rounded-xl bg-slate-100"></div>
        <div class="h-3 w-16 bg-slate-100 rounded"></div>
        <div class="h-6 w-28 bg-slate-100 rounded"></div>
      </div>
    </div>

    <!-- Metrics Cards Grid -->
    <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <!-- Total Projects -->
      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm relative overflow-hidden group">
        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2.5">
          <FolderOpen class="w-4 h-4" />
        </div>
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Projects</span>
        <h2 class="text-lg font-bold text-slate-800 mt-1">{{ stats.total_projects }}</h2>
        <div class="flex items-center space-x-2 mt-2 text-[10px] text-slate-400">
          <span class="text-emerald-500 font-semibold flex items-center"><ChevronUp class="w-3.5 h-3.5" />{{ stats.running_projects }}</span>
          <span>active</span>
        </div>
      </div>

      <!-- Income -->
      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm relative overflow-hidden group">
        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2.5">
          <ArrowUpRight class="w-4 h-4" />
        </div>
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Incomes</span>
        <h2 class="text-lg font-bold text-slate-800 mt-1">{{ sym }}{{ (stats.total_income || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</h2>
        <div class="flex items-center mt-2 text-[10px] text-slate-400">
          <span class="text-emerald-500 font-semibold flex items-center">+{{ sym }}{{ (stats.today_income || 0).toLocaleString() }}</span>
          <span class="ml-1">today</span>
        </div>
      </div>

      <!-- Expense -->
      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm relative overflow-hidden group">
        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-2.5">
          <ArrowDownLeft class="w-4 h-4" />
        </div>
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Spent</span>
        <h2 class="text-lg font-bold text-slate-800 mt-1">{{ sym }}{{ (stats.total_spent || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</h2>
        <div class="flex items-center mt-2 text-[10px] text-slate-400">
          <span class="text-rose-500 font-semibold flex items-center">+{{ sym }}{{ (stats.today_expense || 0).toLocaleString() }}</span>
          <span class="ml-1">today</span>
        </div>
      </div>

      <!-- Net Profit -->
      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm relative overflow-hidden group">
        <div 
          :class="[
            'w-9 h-9 rounded-xl flex items-center justify-center mb-2.5',
            stats.net_profit >= 0 ? 'bg-indigo-50 text-indigo-600' : 'bg-rose-50 text-rose-600'
          ]"
        >
          <DollarSign class="w-4 h-4" />
        </div>
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Net Profit</span>
        <h2 class="text-lg font-bold text-slate-800 mt-1">{{ sym }}{{ (stats.net_profit || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</h2>
        <div class="flex items-center mt-2 text-[10px] text-slate-400">
          <span class="font-semibold text-indigo-500">Margin:</span>
          <span class="ml-1 font-medium">{{ stats.total_income > 0 ? ((stats.net_profit / stats.total_income) * 100).toFixed(1) : 0 }}%</span>
        </div>
      </div>

      <!-- Fixed Assets Valuation -->
      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm relative overflow-hidden group col-span-2 sm:col-span-1">
        <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-2.5">
          <Boxes class="w-4 h-4" />
        </div>
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Fixed Assets</span>
        <h2 class="text-lg font-bold text-slate-800 mt-1">{{ sym }}{{ (stats.fixed_assets_valuation || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</h2>
        <div class="flex items-center justify-between mt-2 text-[10px] text-slate-400">
          <span class="font-semibold text-purple-600">{{ stats.total_fixed_assets || 0 }} assets</span>
          <router-link to="/assets" class="text-indigo-600 hover:underline">View</router-link>
        </div>
      </div>
    </div>

    <!-- Active Team Presence Bar -->
    <div v-if="stats.online_users && stats.online_users.length > 0" class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-3 flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center space-x-2">
        <span class="relative flex h-2.5 w-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
        </span>
        <span class="text-xs font-semibold text-emerald-800">
          {{ stats.online_users_count }} Team Member{{ stats.online_users_count > 1 ? 's' : '' }} Online Now
        </span>
      </div>
      <div class="flex items-center space-x-1.5 overflow-x-auto">
        <div 
          v-for="u in stats.online_users" 
          :key="u.id" 
          class="flex items-center space-x-1 px-2 py-0.5 rounded-full bg-white/80 border border-emerald-200 text-[10px] font-medium text-slate-700 shadow-2xs"
          :title="`Active: ${u.name} (@${u.username})`"
        >
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>{{ u.name }}</span>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Income vs Expense Chart -->
      <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-bold text-slate-800">Cash Flow (Income vs Expense)</h3>
          <span class="text-[10px] text-slate-400">Current calendar year</span>
        </div>
        <div class="h-64 relative">
          <canvas id="cashFlowChart"></canvas>
        </div>
      </div>

      <!-- Top Expense Categories Chart -->
      <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-bold text-slate-800">Expense Distribution</h3>
          <span class="text-[10px] text-slate-400">By category</span>
        </div>
        <div class="h-64 relative flex items-center justify-center">
          <canvas id="categoriesChart"></canvas>
          <div v-if="stats.top_categories.length === 0" class="absolute text-xs text-slate-400">
            No categorization data
          </div>
        </div>
      </div>
    </div>

    <!-- Lower Section: Tables & Activity Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Projects (List Table) -->
      <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm lg:col-span-2 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-800">Recent Projects</h3>
          <router-link to="/projects" class="text-[10px] text-indigo-500 font-semibold hover:underline">View all</router-link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-slate-100 text-slate-400 font-medium">
                <th class="pb-2">Code</th>
                <th class="pb-2">Name</th>
                <th class="pb-2">Client</th>
                <th class="pb-2">Status</th>
                <th class="pb-2 text-right">Budget</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr 
                v-for="project in stats.recent_projects" 
                :key="project.id" 
                class="hover:bg-slate-50/50"
              >
                <td class="py-3 font-mono font-medium text-slate-500">{{ project.code }}</td>
                <td class="py-3 font-semibold text-slate-800">
                  <router-link :to="'/projects/' + project.id" class="hover:text-indigo-500">{{ project.name }}</router-link>
                </td>
                <td class="py-3 text-slate-600">{{ project.client?.name }}</td>
                <td class="py-3">
                  <span 
                    :class="[
                      'inline-block px-2 py-0.5 rounded-full text-[10px] font-medium border',
                      getStatusColorClass(project.status)
                    ]"
                  >
                    {{ project.status }}
                  </span>
                </td>
                <td class="py-3 text-right font-semibold text-slate-700">{{ sym }}{{ project.budget.toLocaleString() }}</td>
              </tr>
              <tr v-if="stats.recent_projects.length === 0">
                <td colspan="5" class="py-6 text-center text-slate-400">No projects recorded</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Transactions / Activities Timeline -->
      <div class="bg-white border border-slate-100 p-6 rounded-2xl shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-slate-800">Recent Transactions</h3>
        
        <div class="flow-root">
          <ul role="list" class="-mb-8">
            <li v-for="(tx, idx) in stats.recent_transactions" :key="idx">
              <div class="relative pb-6">
                <!-- Line indicator -->
                <span 
                  v-if="idx !== stats.recent_transactions.length - 1" 
                  class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-100" 
                  aria-hidden="true"
                ></span>
                
                <div class="relative flex space-x-3">
                  <div>
                    <!-- Icon container -->
                    <span
                      :class="[
                        'h-8 w-8 rounded-lg flex items-center justify-center text-white ring-8 ring-white',
                        tx.type === 'income' ? 'bg-emerald-500' : 'bg-rose-500'
                      ]"
                    >
                      <ArrowUpRight v-if="tx.type === 'income'" class="w-4 h-4" />
                      <ArrowDownLeft v-else class="w-4 h-4" />
                    </span>
                  </div>
                  <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                    <div>
                      <p class="text-xs text-slate-700 font-medium">
                        {{ tx.type === 'income' ? 'Received client payment' : 'Project expense paid' }}
                      </p>
                      <p class="text-[10px] text-slate-400 mt-0.5">Project: <span class="font-medium text-slate-500">{{ tx.project?.name }}</span></p>
                    </div>
                    <div class="text-right text-xs whitespace-nowrap">
                      <span 
                        :class="[
                          'font-bold',
                          tx.type === 'income' ? 'text-emerald-500' : 'text-slate-700'
                        ]"
                      >
                        {{ tx.type === 'income' ? '+' : '-' }}{{ sym }}{{ tx.amount.toLocaleString() }}
                      </span>
                      <p class="text-[9px] text-slate-400 mt-1">{{ formatDate(tx.date) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </li>
            <li v-if="stats.recent_transactions.length === 0" class="text-center py-6 text-xs text-slate-400">
              No transactions recorded
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import {
  FolderOpen,
  ArrowUpRight,
  ArrowDownLeft,
  DollarSign,
  Plus,
  ChevronUp,
  Receipt,
  Boxes
} from 'lucide-vue-next';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';
import { formatDate } from '@/utils/date';
import { useSettingStore } from '@/stores/settings';

const settingStore = useSettingStore();
const sym = computed(() => settingStore.settings.currency_symbol || '৳');

Chart.register(...registerables);

const loading = ref(true);
const stats = ref({
  total_projects: 0,
  running_projects: 0,
  completed_projects: 0,
  total_income: 0.00,
  total_spent: 0.00,
  net_profit: 0.00,
  cash_in_hand: 0.00,
  today_expense: 0.00,
  today_income: 0.00,
  recent_projects: [],
  recent_transactions: [],
  charts: {
    months: [],
    income: [],
    expense: [],
    profit: []
  },
  top_categories: []
});

async function loadDashboardData() {
  loading.value = true;
  try {
    const response = await axios.get('/dashboard-stats');
    stats.value = response.data.data;
    
    loading.value = false;
    
    // Draw charts after DOM loads
    nextTick(() => {
      drawCashFlowChart();
      drawCategoriesChart();
    });
  } catch (error) {
    console.error('Failed to load dashboard statistics:', error);
    loading.value = false;
  }
}

// Chart rendering functions
let cashFlowChartInstance = null;
function drawCashFlowChart() {
  const ctx = document.getElementById('cashFlowChart');
  if (!ctx) return;

  if (cashFlowChartInstance) {
    cashFlowChartInstance.destroy();
  }

  
  const textClr = '#64748b';
  const gridClr = '#f1f5f9';

  cashFlowChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: stats.value.charts.months,
      datasets: [
        {
          label: 'Incomes',
          data: stats.value.charts.income,
          borderColor: '#10b981', // emerald
          backgroundColor: 'rgba(16, 185, 129, 0.05)',
          fill: true,
          tension: 0.35,
          borderWidth: 2.5,
          pointRadius: 2,
          pointHoverRadius: 5
        },
        {
          label: 'Expenses',
          data: stats.value.charts.expense,
          borderColor: '#f43f5e', // rose
          backgroundColor: 'rgba(244, 63, 94, 0.05)',
          fill: true,
          tension: 0.35,
          borderWidth: 2.5,
          pointRadius: 2,
          pointHoverRadius: 5
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            color: textClr,
            font: { size: 11, weight: 'semibold', family: 'Instrument Sans' },
            boxWidth: 8,
            usePointStyle: true
          }
        },
        tooltip: {
          padding: 10,
          backgroundColor: '#ffffff',
          titleColor: '#0f172a',
          bodyColor: '#334155',
          borderColor: '#e2e8f0',
          borderWidth: 1,
          usePointStyle: true
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: textClr, font: { size: 10 } }
        },
        y: {
          grid: { color: gridClr },
          ticks: {
            color: textClr,
            font: { size: 10 },
            callback: (val) => '$' + val.toLocaleString()
          }
        }
      }
    }
  });
}

let categoriesChartInstance = null;
function drawCategoriesChart() {
  const ctx = document.getElementById('categoriesChart');
  if (!ctx || stats.value.top_categories.length === 0) return;

  if (categoriesChartInstance) {
    categoriesChartInstance.destroy();
  }

  
  const textClr = '#64748b';

  const labels = stats.value.top_categories.map(c => c.name);
  const data = stats.value.top_categories.map(c => c.value);

  // Modern Fiori color scheme
  const colors = [
    '#4f46e5', // indigo
    '#06b6d4', // cyan
    '#10b981', // emerald
    '#f59e0b', // amber
    '#ec4899', // pink
  ];

  categoriesChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: data,
        backgroundColor: colors,
        borderWidth: 1,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: textClr,
            font: { size: 10, weight: 'semibold', family: 'Instrument Sans' },
            boxWidth: 8,
            usePointStyle: true,
            padding: 12
          }
        },
        tooltip: {
          padding: 10,
          backgroundColor: '#ffffff',
          titleColor: '#0f172a',
          bodyColor: '#334155',
          borderColor: '#e2e8f0',
          borderWidth: 1,
          callbacks: {
            label: (ctx) => ` ${ctx.label}: $${ctx.raw.toLocaleString()}`
          }
        }
      }
    }
  });
}

function getStatusColorClass(status) {
  switch (status) {
    case 'running':
      return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'planning':
      return 'bg-blue-50 text-blue-600 border-blue-100';
    case 'completed':
      return 'bg-slate-50 text-slate-600 border-slate-200';
    case 'cancelled':
      return 'bg-rose-50 text-rose-600 border-rose-100';
    default:
      return 'bg-slate-50 text-slate-600 border-slate-100';
  }
}


onMounted(() => {
  loadDashboardData();
});
</script>
