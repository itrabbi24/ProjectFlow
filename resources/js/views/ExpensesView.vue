<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Project Expenses</h1>
        <p class="text-xs text-slate-500 mt-1">Track contractor fees, labor, travel and miscellaneous expenses charged to projects.</p>
      </div>

      <button 
        v-if="authStore.hasPermission('create_expenses')"
        @click="openCreateDrawer" 
        class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Log Expense</span>
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
            placeholder="Search descriptions..."
          />
        </div>

        <!-- Project Filter -->
        <select 
          v-model="filters.project_id"
          @change="fetchExpenses"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none max-w-xs"
        >
          <option value="">All Projects</option>
          <option v-for="proj in projectsList" :key="proj.id" :value="proj.id">{{ proj.name }}</option>
        </select>

        <!-- Category Filter -->
        <select 
          v-model="filters.category"
          @change="fetchExpenses"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none"
        >
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>

        <!-- Status Filter -->
        <select 
          v-model="filters.status"
          @change="fetchExpenses"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none"
        >
          <option value="">All Statuses</option>
          <option value="approved">Approved</option>
          <option value="pending">Pending</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      <div class="text-[10px] text-slate-400">
        Showing {{ expensesData.from || 0 }}-{{ expensesData.to || 0 }} of {{ expensesData.total || 0 }} Expenses
      </div>
    </div>

    <!-- Table Loading skeleton -->
    <div v-if="loading" class="bg-white border border-slate-100 rounded-2xl p-6 space-y-4">
      <div class="h-10 w-full bg-slate-100 rounded animate-pulse" v-for="i in 4" :key="i"></div>
    </div>

    <!-- Expenses Table -->
    <div v-else class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
              <th class="p-4">Date</th>
              <th class="p-4">Category</th>
              <th class="p-4">Paid By</th>
              <th class="p-4">Project</th>
              <th class="p-4">Method</th>
              <th class="p-4">Description</th>
              <th class="p-4 text-right">Amount</th>
              <th class="p-4">Status</th>
              <th class="p-4">Receipt</th>
              <th class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr 
              v-for="expense in expensesList" 
              :key="expense.id" 
              class="hover:bg-slate-50/50 transition"
            >
              <td class="p-4 text-slate-500">{{ formatDate(expense.expense_date) }}</td>
              <td class="p-4 text-slate-800"><span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700 font-medium">{{ expense.category }}</span></td>
              <td class="p-4 text-slate-600">{{ expense.paid_by?.name }}</td>
              <td class="p-4 text-slate-600 font-medium">
                <router-link :to="'/projects/' + expense.project_id" class="hover:text-indigo-500 transition-colors">
                  {{ expense.project?.name }}
                </router-link>
              </td>
              <td class="p-4 text-slate-600">{{ expense.payment_method }}</td>
              <td class="p-4 text-slate-500 truncate max-w-xs">{{ expense.description || 'N/A' }}</td>
              <td class="p-4 text-right font-bold text-slate-700">{{ sym }}{{ parseFloat(expense.amount).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</td>
              <td class="p-4">
                <span 
                  :class="[
                    'inline-block px-2.5 py-0.5 rounded-full text-[10px] font-medium border',
                    getStatusColorClass(expense.status)
                  ]"
                >
                  {{ expense.status }}
                </span>
              </td>
              <td class="p-4">
                <a 
                  v-if="expense.attachment_path" 
                  :href="'/storage/' + expense.attachment_path" 
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
                  <!-- Approve/Reject actions for PM/Admins -->
                  <template v-if="expense.status === 'pending' && authStore.hasPermission('approve_expenses')">
                    <button 
                      @click="approveExpense(expense.id, 'approved')"
                      class="p-1 rounded text-emerald-500 hover:bg-emerald-50 focus:outline-none cursor-pointer"
                      title="Approve"
                    >
                      <CheckCircle class="w-4 h-4" />
                    </button>
                    <button 
                      @click="approveExpense(expense.id, 'rejected')"
                      class="p-1 rounded text-rose-500 hover:bg-rose-50 focus:outline-none cursor-pointer"
                      title="Reject"
                    >
                      <XCircle class="w-4 h-4" />
                    </button>
                  </template>

                  <!-- Standard actions -->
                  <button 
                    v-if="authStore.hasPermission('edit_expenses')"
                    @click="openEditDrawer(expense)"
                    class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Edit"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button 
                    v-if="authStore.hasPermission('delete_expenses')"
                    @click="handleDelete(expense.id)"
                    class="p-1 rounded text-slate-400 hover:text-rose-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Delete"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="expensesList.length === 0">
              <td colspan="10" class="p-8 text-center text-slate-400">
                <Receipt class="w-12 h-12 mx-auto text-slate-600 mb-2" />
                <p class="font-medium text-slate-500">No Expenses Found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="expensesData.last_page > 1" class="px-4 py-3 bg-slate-50/50 border-t border-slate-50 flex items-center justify-between text-xs">
        <span class="text-slate-500">Page {{ expensesData.current_page }} of {{ expensesData.last_page }}</span>
        <div class="flex space-x-1">
          <button 
            @click="changePage(expensesData.current_page - 1)" 
            :disabled="expensesData.current_page === 1"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Prev
          </button>
          <button 
            @click="changePage(expensesData.current_page + 1)" 
            :disabled="expensesData.current_page === expensesData.last_page"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Expenses form drawer -->
    <Drawer 
      :is-open="drawerOpen" 
      :title="isEditing ? 'Edit Expense Record' : 'Log Project Expense'" 
      subtitle="Complete operational cost details."
      @close="closeDrawer"
    >
      <form @submit.prevent="saveExpense" class="space-y-4" enctype="multipart/form-data">
        <!-- Date -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Expense Date</label>
          <FlatPickr 
            required 
            v-model="form.expense_date"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          />
        </div>

        <!-- Project selection -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Linked Project</label>
          <select 
            required
            v-model="form.project_id"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select Project</option>
            <option v-for="proj in projectsList" :key="proj.id" :value="proj.id">{{ proj.name }} ({{ proj.code }})</option>
          </select>
        </div>

        <!-- Paid By dropdown -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Paid By (User/Staff)</label>
          <select 
            required
            v-model="form.paid_by"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select Staff member</option>
            <option v-for="user in staffList" :key="user.id" :value="user.id">{{ user.name }} ({{ user.role?.name }})</option>
          </select>
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
            </select>
          </div>
        </div>

        <!-- Amount -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Expense Amount ($)</label>
          <input 
            type="number" 
            step="0.01" 
            required 
            v-model="form.amount"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="0.00"
          />
        </div>

        <!-- Attachment -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Receipt Attachment (PDF or Image)</label>
          <input 
            type="file" 
            class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
            @change="handleFileChange"
          />
        </div>

        <!-- Status (if manager/admin) -->
        <div v-if="authStore.hasPermission('approve_expenses')">
          <label class="block text-xs font-semibold text-slate-700">Verification Status</label>
          <select 
            v-model="form.status"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
          >
            <option value="approved">Approved</option>
            <option value="pending">Pending</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Description</label>
          <textarea 
            rows="2"
            v-model="form.description"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="Describe reason for cost..."
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
            @click="saveExpense"
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
import { Plus, Search, Edit, Trash2, Receipt, FileText, CheckCircle, XCircle } from 'lucide-vue-next';
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
const expensesList = ref([]);
const expensesData = ref({});
const projectsList = ref([]);
const staffList = ref([]);

const categories = ref([]);

async function fetchCategories() {
  try {
    const response = await axios.get('/categories?type=expense&active_only=1');
    categories.value = (response.data.data || []).map(c => c.name);
  } catch (error) {
    console.error(error);
  }
}

const filters = reactive({
  search: '',
  project_id: '',
  category: '',
  status: '',
  page: 1
});

// Drawer state
const drawerOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = reactive({
  expense_date: '',
  project_id: '',
  paid_by: '',
  category: '',
  payment_method: 'Cash',
  amount: 0,
  description: '',
  status: 'approved'
});
const attachmentFile = ref(null);

async function fetchExpenses() {
  loading.value = true;
  try {
    let url = `/expenses?page=${filters.page}`;
    if (filters.search) url += `&search=${filters.search}`;
    if (filters.project_id) url += `&project_id=${filters.project_id}`;
    if (filters.category) url += `&category=${filters.category}`;
    if (filters.status) url += `&status=${filters.status}`;

    const response = await axios.get(url);
    expensesData.value = response.data.data;
    expensesList.value = response.data.data.data;
  } catch (error) {
    console.error(error);
    toast.error('Failed to load expenses.');
  } finally {
    loading.value = false;
  }
}

async function fetchDropdowns() {
  try {
    const [projectsRes, usersRes] = await Promise.all([
      axios.get('/projects?per_page=100'),
      axios.get('/users?per_page=100')
    ]);
    projectsList.value = projectsRes.data.data.data || [];
    staffList.value = usersRes.data.data.data || [];
  } catch (e) {
    console.error(e);
  }
}

let debounceTimer = null;
function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.page = 1;
    fetchExpenses();
  }, 350);
}

function changePage(page) {
  filters.page = page;
  fetchExpenses();
}

function handleFileChange(e) {
  attachmentFile.value = e.target.files[0] || null;
}

function openCreateDrawer() {
  isEditing.value = false;
  editingId.value = null;
  form.expense_date = new Date().toISOString().split('T')[0];
  form.project_id = '';
  form.paid_by = authStore.user?.id || '';
  form.category = '';
  form.payment_method = 'Cash';
  form.amount = 0;
  form.description = '';
  form.status = authStore.hasPermission('approve_expenses') ? 'approved' : 'pending';
  attachmentFile.value = null;
  drawerOpen.value = true;
}

function openEditDrawer(expense) {
  isEditing.value = true;
  editingId.value = expense.id;
  form.expense_date = expense.expense_date;
  form.project_id = expense.project_id;
  form.paid_by = expense.paid_by;
  form.category = expense.category;
  form.payment_method = expense.payment_method;
  form.amount = parseFloat(expense.amount);
  form.description = expense.description || '';
  form.status = expense.status;
  attachmentFile.value = null;
  drawerOpen.value = true;
}

function closeDrawer() {
  drawerOpen.value = false;
}

async function saveExpense() {
  saving.value = true;

  const data = new FormData();
  data.append('expense_date', form.expense_date);
  data.append('project_id', form.project_id);
  data.append('paid_by', form.paid_by);
  data.append('category', form.category);
  data.append('payment_method', form.payment_method);
  data.append('amount', form.amount);
  data.append('status', form.status);
  if (form.description) data.append('description', form.description);
  if (attachmentFile.value) data.append('attachment', attachmentFile.value);

  if (isEditing.value) {
    data.append('_method', 'PUT');
  }

  try {
    if (isEditing.value) {
      await axios.post(`/expenses/${editingId.value}`, data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      toast.success('Expense record updated.');
    } else {
      await axios.post('/expenses', data, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      toast.success('Expense logged successfully.');
    }
    closeDrawer();
    fetchExpenses();
  } catch (error) {
    console.error(error);
    const msg = error.response?.data?.message || 'Error occurred during save.';
    toast.error(msg);
  } finally {
    saving.value = false;
  }
}

async function approveExpense(id, status) {
  const label = status === 'approved' ? 'approve' : 'reject';
  const result = await Swal.fire({
    title: `Are you sure you want to ${label} this expense?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: `Yes, ${label} it!`,
    cancelButtonText: 'Cancel'
  });

  if (result.isConfirmed) {
    try {
      await axios.post(`/expenses/${id}/approve`, { status });
      toast.success(`Expense status updated to ${status}.`);
      fetchExpenses();
    } catch (e) {
      console.error(e);
      toast.error('Failed to update expense status.');
    }
  }
}

async function handleDelete(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "This removes the expense record from all budgets and ledger balances.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete expense'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/expenses/${id}`);
      toast.success('Expense soft-deleted.');
      fetchExpenses();
    } catch (error) {
      console.error(error);
      toast.error('Failed to delete expense.');
    }
  }
}

function getStatusColorClass(status) {
  switch (status) {
    case 'approved':
      return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'pending':
      return 'bg-amber-50 text-amber-600 border-amber-100';
    case 'rejected':
      return 'bg-rose-50 text-rose-600 border-rose-100';
    default:
      return 'bg-slate-50 text-slate-600 border-slate-100';
  }
}

onMounted(() => {
  fetchExpenses();
  fetchDropdowns();
  fetchCategories();
});
</script>
