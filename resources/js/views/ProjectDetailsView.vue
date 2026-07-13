<template>
  <div class="space-y-6 pb-8">
    <!-- Back to Projects & Header -->
    <div class="space-y-3">
      <router-link to="/projects" class="inline-flex items-center space-x-1 text-[10px] font-bold text-slate-400 hover:text-slate-600">
        <ArrowLeft class="w-3.5 h-3.5" />
        <span>Back to Projects</span>
      </router-link>

      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
        <div class="flex items-center space-x-3">
          <div 
            :class="['w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold shadow-md shadow-slate-100']"
            :style="project.color_label ? `background-color: ${getColorHex(project.color_label)}` : 'background-color: #4f46e5'"
          >
            <FolderOpen class="w-6 h-6" />
          </div>
          <div>
            <div class="flex items-center space-x-2">
              <h1 class="text-xl font-bold tracking-tight text-slate-800">{{ project.name }}</h1>
              <span class="text-[10px] font-mono text-slate-400 bg-slate-100 px-2 py-0.5 rounded">{{ project.code }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Client: <span class="font-semibold text-slate-700">{{ project.client?.name }}</span> • Manager: <span class="font-medium text-slate-600">{{ project.manager?.name }}</span></p>
          </div>
        </div>

        <div class="flex items-center space-x-2.5">
          <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', getStatusColorClass(project.status)]">{{ project.status }}</span>
          <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', getPriorityColorClass(project.priority)]">{{ project.priority }} Priority</span>
        </div>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-4 gap-4 animate-pulse">
      <div v-for="i in 4" :key="i" class="h-28 bg-white border rounded-2xl"></div>
    </div>

    <!-- Financial Quick Summary Cards -->
    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Budget -->
      <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Estimated Budget</span>
        <h2 class="text-xl font-bold text-slate-800 mt-1">{{ sym }}{{ financials.budget.toLocaleString() }}</h2>
        <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mt-3">
          <div class="h-full bg-indigo-600 rounded-full" :style="{ width: Math.min(financials.budget_percentage, 100) + '%' }"></div>
        </div>
        <p class="text-[10px] text-slate-400 mt-1.5 flex justify-between">
          <span>Spent: {{ financials.budget_percentage }}%</span>
          <span>Alert at 90%</span>
        </p>
      </div>

      <!-- Total Spent -->
      <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Spent Budget</span>
        <h2 class="text-xl font-bold text-slate-800 mt-1">{{ sym }}{{ financials.total_spent.toLocaleString() }}</h2>
        <div class="flex items-center space-x-2 mt-3.5 text-[10px] text-slate-400">
          <span class="font-medium text-rose-500">{{ sym }}{{ financials.total_expenses.toLocaleString() }}</span>
          <span>expenses</span>
          <span class="font-medium text-slate-600">{{ sym }}{{ financials.total_purchases.toLocaleString() }}</span>
          <span>purchases</span>
        </div>
      </div>

      <!-- Invoiced Payments -->
      <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Invoiced Income</span>
        <h2 class="text-xl font-bold text-slate-800 mt-1">{{ sym }}{{ financials.total_income.toLocaleString() }}</h2>
        <div class="flex items-center mt-3 text-[10px] text-slate-400">
          <span class="font-semibold text-emerald-500">Payments:</span>
          <span class="ml-1">{{ project.incomes?.length || 0 }} received invoices</span>
        </div>
      </div>

      <!-- Actual Profit -->
      <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Actual Net Profit</span>
        <h2 
          :class="[
            'text-xl font-bold mt-1',
            financials.actual_net_profit >= 0 ? 'text-slate-800' : 'text-rose-500'
          ]"
        >
          {{ sym }}{{ financials.actual_net_profit.toLocaleString() }}
        </h2>
        <div class="flex items-center mt-3 text-[10px] text-slate-400">
          <span class="font-semibold text-indigo-500">Margin:</span>
          <span class="ml-1">{{ financials.total_income > 0 ? ((financials.actual_net_profit / financials.total_income) * 100).toFixed(1) : 0 }}%</span>
        </div>
      </div>
    </div>

    <!-- Tabbed navigation folder -->
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <!-- Tabs header -->
      <div class="flex border-b border-slate-100 bg-slate-50/50 px-6 pt-3">
        <button 
          v-for="tab in ['overview', 'transactions', 'budget', 'files']"
          :key="tab"
          @click="activeTab = tab"
          :class="[
            'pb-3 text-xs font-semibold uppercase tracking-wider border-b-2 px-4 transition focus:outline-none cursor-pointer',
            activeTab === tab 
              ? 'border-indigo-600 text-indigo-600' 
              : 'border-transparent text-slate-500 hover:text-slate-600'
          ]"
        >
          {{ tab }}
        </button>
      </div>

      <!-- Tab Body -->
      <div class="p-6">
        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-4">
              <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Project Description</h3>
              <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100/50">
                {{ project.description || 'No description provided.' }}
              </p>

              <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pt-2">Status Notes</h3>
              <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100/50">
                {{ project.notes || 'No project manager updates yet.' }}
              </p>
            </div>

            <!-- Side attributes card -->
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 space-y-4">
              <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Timeline Details</h3>
              <div class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex justify-between">
                  <span class="text-slate-400">Start Date</span>
                  <span class="font-semibold text-slate-700">{{ formatDate(project.start_date) }}</span>
                </div>
                <div class="py-2.5 flex justify-between">
                  <span class="text-slate-400">End Date</span>
                  <span class="font-semibold text-slate-700">{{ formatDate(project.end_date) }}</span>
                </div>
                <div class="py-2.5 flex justify-between">
                  <span class="text-slate-400">Completion</span>
                  <span class="font-semibold text-slate-700">{{ project.progress }}%</span>
                </div>
                <div class="py-2.5 flex justify-between">
                  <span class="text-slate-400">Estimated Profit</span>
                  <span class="font-semibold text-slate-700">{{ sym }}{{ parseFloat(project.estimated_profit || 0).toLocaleString() }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Transactions Tab -->
        <div v-else-if="activeTab === 'transactions'" class="space-y-6">
          <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Transaction Ledger</h3>
          
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="border-b border-slate-100 text-slate-400 font-medium">
                  <th class="pb-2">Date</th>
                  <th class="pb-2">Type</th>
                  <th class="pb-2">Category / Party</th>
                  <th class="pb-2">Method</th>
                  <th class="pb-2">Reference</th>
                  <th class="pb-2 text-right">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <!-- Incomes -->
                <tr v-for="inc in project.incomes" :key="'inc_'+inc.id" class="hover:bg-slate-50/50">
                  <td class="py-3 text-slate-500">{{ formatDate(inc.income_date) }}</td>
                  <td class="py-3"><span class="px-2 py-0.5 rounded text-[10px] bg-emerald-50 text-emerald-600 border border-emerald-100">Income</span></td>
                  <td class="py-3 font-semibold text-slate-800">Payment (Invoice: {{ inc.invoice_number }})</td>
                  <td class="py-3 text-slate-500">{{ inc.payment_method }}</td>
                  <td class="py-3 font-mono text-slate-400">{{ inc.reference_number || 'N/A' }}</td>
                  <td class="py-3 text-right font-bold text-emerald-500">+{{ sym }}{{ parseFloat(inc.amount).toLocaleString() }}</td>
                </tr>

                <!-- Expenses -->
                <tr v-for="exp in project.expenses" :key="'exp_'+exp.id" class="hover:bg-slate-50/50">
                  <td class="py-3 text-slate-500">{{ formatDate(exp.expense_date) }}</td>
                  <td class="py-3"><span class="px-2 py-0.5 rounded text-[10px] bg-rose-50 text-rose-600 border border-rose-100">Expense</span></td>
                  <td class="py-3 font-semibold text-slate-800">{{ exp.category }} • {{ exp.paid_by?.name || 'Staff' }}</td>
                  <td class="py-3 text-slate-500">{{ exp.payment_method }}</td>
                  <td class="py-3 font-mono text-slate-400">{{ exp.status === 'approved' ? 'Approved' : 'Pending' }}</td>
                  <td class="py-3 text-right font-bold text-slate-700">-{{ sym }}{{ parseFloat(exp.amount).toLocaleString() }}</td>
                </tr>

                <!-- Purchases -->
                <tr v-for="pur in project.purchases" :key="'pur_'+pur.id" class="hover:bg-slate-50/50">
                  <td class="py-3 text-slate-500">{{ formatDate(pur.purchase_date) }}</td>
                  <td class="py-3"><span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 border border-slate-200">Purchase</span></td>
                  <td class="py-3 font-semibold text-slate-800">{{ pur.category }} • Supplier: {{ pur.supplier_name }}</td>
                  <td class="py-3 text-slate-500">{{ pur.payment_method }}</td>
                  <td class="py-3 font-mono text-slate-400">Invoice: {{ pur.invoice_no }}</td>
                  <td class="py-3 text-right font-bold text-slate-700">-{{ sym }}{{ parseFloat(pur.amount).toLocaleString() }}</td>
                </tr>

                <tr v-if="!(project.incomes?.length || project.expenses?.length || project.purchases?.length)">
                  <td colspan="6" class="py-8 text-center text-slate-400">No ledger entries logged for this project</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Budget Tab (Chart & aggregates) -->
        <div v-else-if="activeTab === 'budget'" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <!-- Chart -->
            <div class="h-60 relative flex items-center justify-center">
              <canvas id="budgetBreakdownChart"></canvas>
            </div>

            <!-- Explanatory cards -->
            <div class="md:col-span-2 space-y-4">
              <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Budget Allocation Analysis</h3>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="border border-slate-100 p-4 rounded-xl bg-slate-50/50">
                  <span class="text-slate-400 block mb-1">Total Allocated Budget</span>
                  <span class="text-sm font-bold text-slate-800">{{ sym }}{{ financials.budget.toLocaleString() }}</span>
                </div>
                <div class="border border-slate-100 p-4 rounded-xl bg-slate-50/50">
                  <span class="text-slate-400 block mb-1">Spent to Date ({{ financials.budget_percentage }}%)</span>
                  <span class="text-sm font-bold text-rose-500">{{ sym }}{{ financials.total_spent.toLocaleString() }}</span>
                </div>
                <div class="border border-slate-100 p-4 rounded-xl bg-slate-50/50">
                  <span class="text-slate-400 block mb-1">Remaining Balance</span>
                  <span class="text-sm font-bold text-emerald-500">{{ sym }}{{ financials.remaining_budget.toLocaleString() }}</span>
                </div>
                <div class="border border-slate-100 p-4 rounded-xl bg-slate-50/50">
                  <span class="text-slate-400 block mb-1">Budget Alerts</span>
                  <span 
                    :class="[
                      'text-sm font-bold',
                      financials.budget_percentage > 90 ? 'text-rose-500 animate-pulse' : 'text-slate-500'
                    ]"
                  >
                    {{ financials.budget_percentage > 90 ? 'CRITICAL: OVER 90%' : 'Normal Status' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Files Tab -->
        <div v-else-if="activeTab === 'files'" class="space-y-6">
          <div class="flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Project Files & Attachments</h3>
            
            <!-- Upload form -->
            <label class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 transition cursor-pointer flex items-center space-x-1.5 shadow-sm">
              <Upload class="w-4 h-4 text-slate-400" />
              <span>Upload Document</span>
              <input type="file" class="hidden" @change="handleFileUpload" />
            </label>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
              v-for="file in project.files" 
              :key="file.id"
              class="border border-slate-100 rounded-xl p-4 flex items-center space-x-3 bg-slate-50/30 hover:bg-slate-50 transition"
            >
              <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                <FileText class="w-5 h-5" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-slate-800 truncate">{{ file.filename }}</p>
                <p class="text-[9px] text-slate-500 mt-0.5">Size: {{ (file.file_size / 1024).toFixed(1) }} KB • By {{ file.uploader?.name }}</p>
              </div>
              <a 
                :href="'/storage/' + file.file_path" 
                target="_blank" 
                class="text-indigo-500 hover:text-indigo-600 p-1 hover:bg-slate-100 rounded flex-shrink-0"
              >
                <Download class="w-4 h-4" />
              </a>
            </div>

            <div v-if="project.files?.length === 0" class="col-span-full py-8 text-center text-slate-400 text-xs">
              No files uploaded to this project
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useSettingStore } from '@/stores/settings';
import { 
  ArrowLeft, 
  FolderOpen, 
  Download, 
  Upload, 
  FileText,
  ArrowUpRight,
  ArrowDownLeft
} from 'lucide-vue-next';
import axios from 'axios';
import { toast } from 'vue-sonner';
import { Chart, registerables } from 'chart.js';
import { formatDate } from '@/utils/date';

const settingStore = useSettingStore();
const sym = computed(() => settingStore.settings.currency_symbol || '৳');

Chart.register(...registerables);

const route = useRoute();
const authStore = useAuthStore();

const loading = ref(true);
const activeTab = ref('overview');
const project = ref({});
const financials = ref({
  budget: 0,
  total_purchases: 0,
  total_expenses: 0,
  total_spent: 0,
  remaining_budget: 0,
  budget_percentage: 0,
  total_income: 0,
  actual_net_profit: 0,
  budget_alert: false
});

async function loadProjectDetails() {
  loading.value = true;
  try {
    const response = await axios.get(`/projects/${route.params.id}`);
    project.value = response.data.data.project;
    financials.value = response.data.data.financials;
  } catch (error) {
    console.error('Failed to load project details:', error);
    toast.error('Failed to load project details.');
  } finally {
    loading.value = false;
  }
}

async function handleFileUpload(e) {
  const file = e.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);

  try {
    toast.info('Uploading file...');
    await axios.post(`/projects/${route.params.id}/files`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });
    toast.success('File uploaded successfully.');
    loadProjectDetails();
  } catch (error) {
    console.error(error);
    toast.error('Failed to upload file.');
  }
}

// Draw budget breakdown chart
let budgetChartInstance = null;
function drawBudgetChart() {
  const ctx = document.getElementById('budgetBreakdownChart');
  if (!ctx) return;

  if (budgetChartInstance) {
    budgetChartInstance.destroy();
  }

  
  const textClr = '#64748b';

  budgetChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Purchases', 'Approved Expenses', 'Remaining Budget'],
      datasets: [{
        data: [
          financials.value.total_purchases,
          financials.value.total_expenses,
          Math.max(0, financials.value.remaining_budget)
        ],
        backgroundColor: [
          '#64748b', // purchases (grey slate)
          '#f43f5e', // expenses (rose)
          '#10b981'  // remaining (emerald)
        ],
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

// Watch tab modifications to draw chart
watch(activeTab, (newTab) => {
  if (newTab === 'budget') {
    nextTick(() => {
      drawBudgetChart();
    });
  }
});

// Styling helpers
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

function getPriorityColorClass(priority) {
  switch (priority) {
    case 'high':
      return 'bg-rose-50 text-rose-600 border-rose-100';
    case 'medium':
      return 'bg-amber-50 text-amber-600 border-amber-100';
    case 'low':
      return 'bg-slate-50 text-slate-600 border-slate-200';
    default:
      return 'bg-slate-50 text-slate-600 border-slate-250';
  }
}

function getColorHex(color) {
  const colors = {
    indigo: '#4f46e5',
    emerald: '#10b981',
    amber: '#f59e0b',
    rose: '#f43f5e',
    cyan: '#06b6d4'
  };
  return colors[color] || '#cbd5e1';
}

onMounted(() => {
  loadProjectDetails();
});
</script>
