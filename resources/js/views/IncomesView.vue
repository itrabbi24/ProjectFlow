<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Project Incomes</h1>
        <p class="text-xs text-slate-500 mt-1">Record client payments and billings received against projects.</p>
      </div>

      <button
        v-if="authStore.hasPermission('create_incomes')"
        @click="openCreateDrawer"
        class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Log Income</span>
      </button>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3 flex-1">
        <div class="relative w-64">
          <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
          <input
            type="text"
            v-model="filters.search"
            @input="debouncedSearch"
            class="pl-9 pr-4 py-1.5 w-full rounded-lg border border-slate-200 bg-transparent text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500"
            placeholder="Search invoice, reference..."
          />
        </div>

        <!-- Project Filter -->
        <select
          v-model="filters.project_id"
          @change="fetchIncomes"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none max-w-xs"
        >
          <option value="">All Projects</option>
          <option v-for="proj in projectsList" :key="proj.id" :value="proj.id">{{ proj.name }}</option>
        </select>

        <!-- Client Filter -->
        <select
          v-model="filters.client_id"
          @change="fetchIncomes"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none max-w-xs"
        >
          <option value="">All Clients</option>
          <option v-for="client in clientsList" :key="client.id" :value="client.id">{{ client.name }}</option>
        </select>

        <!-- Category Filter -->
        <select
          v-model="filters.category"
          @change="fetchIncomes"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none"
        >
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>
      </div>

      <div class="text-[10px] text-slate-400">
        Showing {{ incomesData.from || 0 }}-{{ incomesData.to || 0 }} of {{ incomesData.total || 0 }} Incomes
      </div>
    </div>

    <!-- Table Loading skeleton -->
    <div v-if="loading" class="bg-white border border-slate-100 rounded-2xl p-6 space-y-4">
      <div class="h-10 w-full bg-slate-100 rounded animate-pulse" v-for="i in 4" :key="i"></div>
    </div>

    <!-- Incomes Table -->
    <div v-else class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
              <th class="p-4">Date</th>
              <th class="p-4">Invoice #</th>
              <th class="p-4">Category</th>
              <th class="p-4">Client</th>
              <th class="p-4">Project</th>
              <th class="p-4">Method</th>
              <th class="p-4">Reference</th>
              <th class="p-4 text-right">Amount</th>
              <th class="p-4">Receipt</th>
              <th class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr
              v-for="income in incomesList"
              :key="income.id"
              class="hover:bg-slate-50/50 transition"
            >
              <td class="p-4 text-slate-500">{{ formatDate(income.income_date) }}</td>
              <td class="p-4 font-mono text-slate-700">{{ income.invoice_number }}</td>
              <td class="p-4"><span class="px-2 py-0.5 rounded text-[10px] bg-emerald-50 text-emerald-600 font-medium border border-emerald-100">{{ income.category }}</span></td>
              <td class="p-4 text-slate-600">{{ income.client?.name }}</td>
              <td class="p-4 text-slate-600 font-medium">
                <router-link :to="'/projects/' + income.project_id" class="hover:text-indigo-500 transition-colors">
                  {{ income.project?.name }}
                </router-link>
              </td>
              <td class="p-4 text-slate-600">{{ income.payment_method }}</td>
              <td class="p-4 font-mono text-slate-400">{{ income.reference_number || 'N/A' }}</td>
              <td class="p-4 text-right font-bold text-emerald-600">+{{ sym }}{{ parseFloat(income.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
              <td class="p-4">
                <a
                  v-if="income.attachment_path"
                  :href="'/storage/' + income.attachment_path"
                  target="_blank"
                  class="inline-flex items-center space-x-1 text-indigo-500 hover:underline"
                >
                  <FileText class="w-4 h-4" />
                  <span>View</span>
                </a>
                <span v-else class="text-slate-400">None</span>
              </td>
              <td class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-center space-x-1.5">
                  <button
                    v-if="authStore.hasPermission('edit_incomes')"
                    @click="openEditDrawer(income)"
                    class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Edit"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button
                    v-if="authStore.hasPermission('delete_incomes')"
                    @click="handleDelete(income.id)"
                    class="p-1 rounded text-slate-400 hover:text-rose-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Delete"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="incomesList.length === 0">
              <td colspan="10" class="p-8 text-center text-slate-400">
                <Wallet class="w-12 h-12 mx-auto text-slate-600 mb-2" />
                <p class="font-medium text-slate-500">No Incomes Found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="incomesData.last_page > 1" class="px-4 py-3 bg-slate-50/50 border-t border-slate-50 flex items-center justify-between text-xs">
        <span class="text-slate-500">Page {{ incomesData.current_page }} of {{ incomesData.last_page }}</span>
        <div class="flex space-x-1">
          <button
            @click="changePage(incomesData.current_page - 1)"
            :disabled="incomesData.current_page === 1"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Prev
          </button>
          <button
            @click="changePage(incomesData.current_page + 1)"
            :disabled="incomesData.current_page === incomesData.last_page"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Income form drawer -->
    <Drawer
      :is-open="drawerOpen"
      :title="isEditing ? 'Edit Income Record' : 'Log Project Income'"
      subtitle="Record a client payment against a project."
      @close="closeDrawer"
    >
      <form @submit.prevent="saveIncome" class="space-y-4" enctype="multipart/form-data">
        <!-- Date -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Income Date</label>
          <FlatPickr
            required
            v-model="form.income_date"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          />
        </div>

        <!-- Project selection -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Linked Project</label>
          <select
            required
            v-model="form.project_id"
            @change="syncClientFromProject"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select Project</option>
            <option v-for="proj in projectsList" :key="proj.id" :value="proj.id">{{ proj.name }} ({{ proj.code }})</option>
          </select>
        </div>

        <!-- Client selection -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Client</label>
          <select
            required
            v-model="form.client_id"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select Client</option>
            <option v-for="client in clientsList" :key="client.id" :value="client.id">{{ client.name }}</option>
          </select>
        </div>

        <!-- Invoice & Reference Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Invoice Number</label>
            <input
              type="text"
              required
              v-model="form.invoice_number"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="INV-2026-001"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Reference Number</label>
            <input
              type="text"
              v-model="form.reference_number"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="Bank/TXN reference (optional)"
            />
          </div>
        </div>

        <!-- Category & Payment Method Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Category</label>
            <select
              required
              v-model="form.category"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
            >
              <option value="" disabled>Select Category</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Payment Method</label>
            <select
              required
              v-model="form.payment_method"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
            >
              <option value="Cash">Cash</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Mobile Banking">Mobile Banking</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Received Amount ({{ sym }})</label>
          <input
            type="number"
            step="0.01"
            min="0.01"
            required
            v-model="form.amount"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="0.00"
          />
        </div>

        <!-- Attachment -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Payment Proof (PDF or Image)</label>
          <input
            type="file"
            class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
            @change="handleFileChange"
          />
        </div>

        <!-- Remarks -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Remarks</label>
          <textarea
            rows="2"
            v-model="form.remarks"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="Notes about this payment..."
          ></textarea>
        </div>
      </form>

      <!-- Footer Buttons -->
      <template #footer>
        <div class="flex justify-end space-x-2">
          <button
            type="button"
            @click="closeDrawer"
            class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="saveIncome"
            :disabled="saving"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 transition disabled:opacity-50 cursor-pointer"
          >
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </template>
    </Drawer>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSettingStore } from '@/stores/settings';
import { Plus, Search, Edit, Trash2, Wallet, FileText } from 'lucide-vue-next';
import axios from 'axios';
import Drawer from '@/components/ui/Drawer.vue';
import FlatPickr from '@/components/FlatPickr.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue-sonner';
import { formatDate } from '@/utils/date';

const settingStore = useSettingStore();
const sym = computed(() => settingStore.settings.currency_symbol || '৳');

const authStore = useAuthStore();

const loading = ref(true);
const saving = ref(false);
const incomesList = ref([]);
const incomesData = ref({});
const projectsList = ref([]);
const clientsList = ref([]);

const categories = ref([]);

async function fetchCategories() {
  try {
    const response = await axios.get('/categories?type=income&active_only=1');
    categories.value = (response.data.data || []).map(c => c.name);
  } catch (error) {
    console.error(error);
  }
}

const filters = reactive({
  search: '',
  project_id: '',
  client_id: '',
  category: '',
  page: 1
});

// Drawer state
const drawerOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = reactive({
  income_date: '',
  project_id: '',
  client_id: '',
  invoice_number: '',
  category: '',
  payment_method: 'Bank Transfer',
  amount: '',
  reference_number: '',
  remarks: ''
});
const attachmentFile = ref(null);

async function fetchIncomes() {
  loading.value = true;
  try {
    let url = `/incomes?page=${filters.page}`;
    if (filters.search) url += `&search=${filters.search}`;
    if (filters.project_id) url += `&project_id=${filters.project_id}`;
    if (filters.client_id) url += `&client_id=${filters.client_id}`;
    if (filters.category) url += `&category=${filters.category}`;

    const response = await axios.get(url);
    incomesData.value = response.data.data;
    incomesList.value = response.data.data.data;
  } catch (error) {
    console.error(error);
    toast.error('Failed to load incomes.');
  } finally {
    loading.value = false;
  }
}

async function fetchDropdowns() {
  try {
    const [projectsRes, clientsRes] = await Promise.all([
      axios.get('/projects/options').catch(() => axios.get('/projects?per_page=100')),
      axios.get('/clients?per_page=100')
    ]);
    projectsList.value = Array.isArray(projectsRes.data.data) ? projectsRes.data.data : (projectsRes.data.data?.data || []);
    clientsList.value = Array.isArray(clientsRes.data.data) ? clientsRes.data.data : (clientsRes.data.data?.data || []);
  } catch (e) {
    console.error('Failed to load income dropdown options:', e);
  }
}

// Auto-select the project's client when a project is chosen
function syncClientFromProject() {
  const proj = projectsList.value.find(p => p.id === form.project_id);
  if (proj?.client_id) {
    form.client_id = proj.client_id;
  }
}

let debounceTimer = null;
function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.page = 1;
    fetchIncomes();
  }, 350);
}

function changePage(page) {
  filters.page = page;
  fetchIncomes();
}

function handleFileChange(e) {
  attachmentFile.value = e.target.files[0] || null;
}

function openCreateDrawer() {
  isEditing.value = false;
  editingId.value = null;
  form.income_date = new Date().toISOString().split('T')[0];
  form.project_id = '';
  form.client_id = '';
  form.invoice_number = '';
  form.category = '';
  form.payment_method = 'Bank Transfer';
  form.amount = '';
  form.reference_number = '';
  form.remarks = '';
  attachmentFile.value = null;
  drawerOpen.value = true;
}

function openEditDrawer(income) {
  isEditing.value = true;
  editingId.value = income.id;
  form.income_date = income.income_date;
  form.project_id = income.project_id;
  form.client_id = income.client_id;
  form.invoice_number = income.invoice_number;
  form.category = income.category || '';
  form.payment_method = income.payment_method;
  form.amount = parseFloat(income.amount);
  form.reference_number = income.reference_number || '';
  form.remarks = income.remarks || '';
  attachmentFile.value = null;
  drawerOpen.value = true;
}

function closeDrawer() {
  drawerOpen.value = false;
}

async function saveIncome() {
  saving.value = true;

  const data = new FormData();
  data.append('income_date', form.income_date);
  data.append('project_id', form.project_id);
  data.append('client_id', form.client_id);
  data.append('invoice_number', form.invoice_number);
  data.append('category', form.category);
  data.append('payment_method', form.payment_method);
  data.append('amount', form.amount);
  if (form.reference_number) data.append('reference_number', form.reference_number);
  if (form.remarks) data.append('remarks', form.remarks);
  if (attachmentFile.value) data.append('attachment', attachmentFile.value);

  if (isEditing.value) {
    data.append('_method', 'PUT');
  }

  try {
    if (isEditing.value) {
      await axios.post(`/incomes/${editingId.value}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      toast.success('Income record updated.');
    } else {
      await axios.post('/incomes', data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      toast.success('Income logged successfully.');
    }
    closeDrawer();
    fetchIncomes();
  } catch (error) {
    console.error(error);
    const msg = error.response?.data?.message || 'Error occurred during save.';
    toast.error(msg);
  } finally {
    saving.value = false;
  }
}

async function handleDelete(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'This removes the income record from all reports and balances.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete income'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/incomes/${id}`);
      toast.success('Income deleted.');
      fetchIncomes();
    } catch (error) {
      console.error(error);
      toast.error('Failed to delete income.');
    }
  }
}

onMounted(() => {
  fetchIncomes();
  fetchDropdowns();
  fetchCategories();
});
</script>
