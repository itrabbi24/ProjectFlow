<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Projects</h1>
        <p class="text-xs text-slate-500 mt-1">Manage project finances, timelines, budget allocations and margins.</p>
      </div>

      <!-- Actions -->
      <div class="flex items-center space-x-2.5">
        <button 
          v-if="authStore.hasPermission('create_projects')"
          @click="openCreateDrawer" 
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Add Project</span>
        </button>
        <button 
          @click="exportCSV" 
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 transition cursor-pointer"
        >
          <Download class="w-4 h-4 text-slate-400" />
          <span>Export Excel</span>
        </button>
      </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
      <!-- Search and filters -->
      <div class="flex flex-wrap items-center gap-3 flex-1">
        <div class="relative w-64">
          <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
          <input 
            type="text" 
            v-model="filters.search"
            @input="debouncedSearch"
            class="pl-9 pr-4 py-1.5 w-full rounded-lg border border-slate-200 bg-transparent text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500"
            placeholder="Search code or name..."
          />
        </div>

        <!-- Status Filter -->
        <select 
          v-model="filters.status"
          @change="fetchProjects"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none"
        >
          <option value="">All Statuses</option>
          <option value="planning">Planning</option>
          <option value="running">Running</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
          <option value="archived">Archived</option>
        </select>

        <!-- Priority Filter -->
        <select 
          v-model="filters.priority"
          @change="fetchProjects"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none"
        >
          <option value="">All Priorities</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>

        <!-- Client Filter -->
        <select 
          v-model="filters.client_id"
          @change="fetchProjects"
          class="px-3 py-1.5 rounded-lg border border-slate-200 bg-transparent text-xs text-slate-600 focus:outline-none max-w-xs"
        >
          <option value="">All Clients</option>
          <option v-for="client in clientsList" :key="client.id" :value="client.id">{{ client.name }}</option>
        </select>
      </div>

      <!-- Quick Summary counter labels -->
      <div class="flex items-center space-x-2 text-[10px] text-slate-400">
        <span>Showing {{ projectsData.from || 0 }}-{{ projectsData.to || 0 }} of {{ projectsData.total || 0 }} Projects</span>
      </div>
    </div>

    <!-- Loading Skeleton View -->
    <div v-if="loading" class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-4">
      <div class="h-6 w-1/4 bg-slate-100 rounded animate-pulse"></div>
      <div class="space-y-2">
        <div v-for="i in 5" :key="i" class="h-10 w-full bg-slate-100 rounded animate-pulse"></div>
      </div>
    </div>

    <!-- Project List Table -->
    <div v-else class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
              <th class="p-4 font-mono">Code</th>
              <th class="p-4">Project Name</th>
              <th class="p-4">Client</th>
              <th class="p-4">Manager</th>
              <th class="p-4">Priority</th>
              <th class="p-4">Progress</th>
              <th class="p-4 text-right">Budget</th>
              <th class="p-4">Status</th>
              <th class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr 
              v-for="project in projectsList" 
              :key="project.id" 
              class="hover:bg-slate-50/50 transition"
            >
              <td class="p-4 font-mono font-medium text-slate-500">{{ project.code }}</td>
              <td class="p-4">
                <div class="flex items-center space-x-2">
                  <span 
                    v-if="project.color_label" 
                    :class="['w-2.5 h-2.5 rounded-full flex-shrink-0', 'bg-' + project.color_label + '-500']"
                    :style="project.color_label ? `background-color: ${getColorHex(project.color_label)}` : ''"
                  ></span>
                  <router-link :to="'/projects/' + project.id" class="font-semibold text-slate-800 hover:text-indigo-500 transition-colors">
                    {{ project.name }}
                  </router-link>
                </div>
              </td>
              <td class="p-4 text-slate-600 font-medium">{{ project.client?.name }}</td>
              <td class="p-4 text-slate-600">{{ project.manager?.name }}</td>
              <td class="p-4">
                <span 
                  :class="[
                    'px-2 py-0.5 rounded text-[10px] font-semibold tracking-wider uppercase border',
                    getPriorityColorClass(project.priority)
                  ]"
                >
                  {{ project.priority }}
                </span>
              </td>
              <td class="p-4">
                <div class="flex items-center space-x-2 w-28">
                  <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full" :style="{ width: project.progress + '%' }"></div>
                  </div>
                  <span class="text-[10px] text-slate-500 w-6 text-right">{{ project.progress }}%</span>
                </div>
              </td>
              <td class="p-4 text-right font-semibold text-slate-700">{{ sym }}{{ project.budget.toLocaleString() }}</td>
              <td class="p-4">
                <span 
                  :class="[
                    'inline-block px-2.5 py-0.5 rounded-full text-[10px] font-medium border',
                    getStatusColorClass(project.status)
                  ]"
                >
                  {{ project.status }}
                </span>
              </td>
              <!-- Sticky actions column -->
              <td class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-center space-x-1.5">
                  <router-link 
                    :to="'/projects/' + project.id"
                    class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-slate-50"
                    title="View Details"
                  >
                    <Eye class="w-4 h-4" />
                  </router-link>
                  <button 
                    v-if="authStore.hasPermission('edit_projects')"
                    @click="openEditDrawer(project)"
                    class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Edit"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button 
                    v-if="authStore.hasPermission('delete_projects')"
                    @click="handleDelete(project.id)"
                    class="p-1 rounded text-slate-400 hover:text-rose-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Delete"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="projectsList.length === 0">
              <td colspan="9" class="p-8 text-center text-slate-400">
                <FolderOpen class="w-12 h-12 mx-auto text-slate-600 mb-2" />
                <p class="font-medium text-slate-500 text-sm">No Projects Found</p>
                <p class="text-[10px] mt-0.5">Try modifying filters or add a new project to get started.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="projectsData.last_page > 1" class="px-4 py-3 bg-slate-50/50 border-t border-slate-50 flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
          <button 
            @click="changePage(projectsData.current_page - 1)"
            :disabled="projectsData.current_page === 1"
            class="relative inline-flex items-center px-4 py-2 border border-slate-200 text-xs font-semibold rounded-lg bg-white text-slate-700 disabled:opacity-50"
          >
            Previous
          </button>
          <button 
            @click="changePage(projectsData.current_page + 1)"
            :disabled="projectsData.current_page === projectsData.last_page"
            class="ml-3 relative inline-flex items-center px-4 py-2 border border-slate-200 text-xs font-semibold rounded-lg bg-white text-slate-700 disabled:opacity-50"
          >
            Next
          </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-[11px] text-slate-500">
              Showing page <span class="font-semibold">{{ projectsData.current_page }}</span> of <span class="font-semibold">{{ projectsData.last_page }}</span> pages
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button 
                @click="changePage(1)"
                :disabled="projectsData.current_page === 1"
                class="relative inline-flex items-center px-2 py-1.5 rounded-l-md border border-slate-200 bg-white text-xs text-slate-500 hover:bg-slate-50 disabled:opacity-50"
              >
                First
              </button>
              <button 
                v-for="page in getPageRange()" 
                :key="page"
                @click="changePage(page)"
                :class="[
                  'relative inline-flex items-center px-3 py-1.5 border text-xs font-medium',
                  projectsData.current_page === page
                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600 font-bold'
                    : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50'
                ]"
              >
                {{ page }}
              </button>
              <button 
                @click="changePage(projectsData.last_page)"
                :disabled="projectsData.current_page === projectsData.last_page"
                class="relative inline-flex items-center px-2 py-1.5 rounded-r-md border border-slate-200 bg-white text-xs text-slate-500 hover:bg-slate-50 disabled:opacity-50"
              >
                Last
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Form Drawer -->
    <Drawer 
      :is-open="drawerOpen" 
      :title="isEditing ? 'Edit Project' : 'Create New Project'" 
      subtitle="Complete project financial allocations."
      @close="closeDrawer"
    >
      <form @submit.prevent="saveProject" class="space-y-4">
        <!-- Project Name -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Project Name</label>
          <input 
            type="text" 
            required 
            v-model="form.name"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500"
            placeholder="e.g. Apollo Web App"
          />
        </div>

        <!-- Client Dropdown -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Client</label>
          <select 
            required
            v-model="form.client_id"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select Client</option>
            <option v-for="client in clientsList" :key="client.id" :value="client.id">{{ client.name }} ({{ client.company || 'Private' }})</option>
          </select>
        </div>

        <!-- Manager Dropdown -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Project Manager</label>
          <select 
            required
            v-model="form.manager_id"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select Manager</option>
            <option v-for="user in managersList" :key="user.id" :value="user.id">{{ user.name }} ({{ user.role?.name }})</option>
          </select>
        </div>

        <!-- Dates Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Start Date</label>
            <FlatPickr 
              required
              v-model="form.start_date"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">End Date</label>
            <FlatPickr 
              required
              v-model="form.end_date"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Budget & Est Profit Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Total Budget ($)</label>
            <input 
              type="number" 
              step="0.01" 
              required
              v-model="form.budget"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="e.g. 50000"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Estimated Profit ($)</label>
            <input 
              type="number" 
              step="0.01" 
              required
              v-model="form.estimated_profit"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="e.g. 15000"
            />
          </div>
        </div>

        <!-- Status & Priority Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Status</label>
            <select 
              v-model="form.status"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
            >
              <option value="planning">Planning</option>
              <option value="running">Running</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Priority</label>
            <select 
              v-model="form.priority"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>
        </div>

        <!-- Progress bar and Color Label -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Progress (0-100%)</label>
            <input 
              type="number" 
              min="0" 
              max="100" 
              required
              v-model="form.progress"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Color Label</label>
            <select 
              v-model="form.color_label"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
            >
              <option value="">None</option>
              <option value="indigo">Indigo</option>
              <option value="emerald">Emerald</option>
              <option value="amber">Amber</option>
              <option value="rose">Rose</option>
              <option value="cyan">Cyan</option>
            </select>
          </div>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Description</label>
          <textarea 
            rows="3"
            v-model="form.description"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="Describe project deliverables..."
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
            @click="saveProject"
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
import {
  Plus,
  Search,
  Download,
  Eye,
  Edit,
  Trash2,
  FolderOpen
} from 'lucide-vue-next';
import axios from 'axios';
import Drawer from '@/components/ui/Drawer.vue';
import FlatPickr from '@/components/FlatPickr.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue-sonner';

const authStore = useAuthStore();
const settingStore = useSettingStore();
const sym = computed(() => settingStore.settings.currency_symbol || '৳');

const loading = ref(true);
const saving = ref(false);
const projectsList = ref([]);
const projectsData = ref({});
const clientsList = ref([]);
const managersList = ref([]);

const filters = reactive({
  search: '',
  status: '',
  priority: '',
  client_id: '',
  page: 1
});

// Form state
const drawerOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = reactive({
  name: '',
  client_id: '',
  manager_id: '',
  start_date: '',
  end_date: '',
  budget: 0,
  estimated_profit: 0,
  status: 'planning',
  priority: 'medium',
  progress: 0,
  color_label: '',
  description: ''
});

async function fetchProjects() {
  loading.value = true;
  try {
    let url = `/projects?page=${filters.page}`;
    if (filters.search) url += `&search=${filters.search}`;
    if (filters.status) url += `&status=${filters.status}`;
    if (filters.priority) url += `&priority=${filters.priority}`;
    if (filters.client_id) url += `&client_id=${filters.client_id}`;

    const response = await axios.get(url);
    projectsData.value = response.data.data;
    projectsList.value = response.data.data.data;
  } catch (error) {
    console.error('Failed to load projects:', error);
    toast.error('Failed to load projects list.');
  } finally {
    loading.value = false;
  }
}

async function fetchDropdowns() {
  try {
    const [clientsRes, usersRes] = await Promise.all([
      axios.get('/clients?per_page=100'),
      axios.get('/users?per_page=100')
    ]);
    clientsList.value = clientsRes.data.data.data || [];
    managersList.value = usersRes.data.data.data || [];
  } catch (e) {
    console.error('Failed to load selection dropdowns:', e);
  }
}

let debounceTimer = null;
function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.page = 1;
    fetchProjects();
  }, 350);
}

function changePage(page) {
  filters.page = page;
  fetchProjects();
}

function getPageRange() {
  const current = projectsData.value.current_page;
  const last = projectsData.value.last_page;
  const range = [];
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    range.push(i);
  }
  return range;
}

// Drawer controls
function openCreateDrawer() {
  isEditing.value = false;
  editingId.value = null;
  
  // Reset form
  form.name = '';
  form.client_id = '';
  form.manager_id = '';
  form.start_date = new Date().toISOString().split('T')[0];
  form.end_date = new Date().toISOString().split('T')[0];
  form.budget = 0;
  form.estimated_profit = 0;
  form.status = 'planning';
  form.priority = 'medium';
  form.progress = 0;
  form.color_label = '';
  form.description = '';

  drawerOpen.value = true;
}

function openEditDrawer(project) {
  isEditing.value = true;
  editingId.value = project.id;
  
  // Fill form
  form.name = project.name;
  form.client_id = project.client_id;
  form.manager_id = project.manager_id;
  form.start_date = project.start_date;
  form.end_date = project.end_date;
  form.budget = parseFloat(project.budget);
  form.estimated_profit = parseFloat(project.estimated_profit);
  form.status = project.status;
  form.priority = project.priority;
  form.progress = project.progress;
  form.color_label = project.color_label || '';
  form.description = project.description || '';

  drawerOpen.value = true;
}

function closeDrawer() {
  drawerOpen.value = false;
}

async function saveProject() {
  saving.value = true;
  try {
    if (isEditing.value) {
      await axios.put(`/projects/${editingId.value}`, form);
      toast.success('Project updated successfully.');
    } else {
      await axios.post('/projects', form);
      toast.success('Project created successfully.');
    }
    closeDrawer();
    fetchProjects();
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
    text: "Deleting this project soft-deletes its relational transaction summaries.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    cancelButtonText: 'No, cancel'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/projects/${id}`);
      toast.success('Project soft-deleted successfully.');
      fetchProjects();
    } catch (error) {
      console.error(error);
      toast.error('Failed to delete project.');
    }
  }
}

// Styling utilities
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
    case 'archived':
      return 'bg-amber-50 text-amber-600 border-amber-100';
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
      return 'bg-slate-50 text-slate-600 border-slate-200';
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

function exportCSV() {
  let headers = 'Code,Name,Client,Manager,Priority,Progress,Budget,Status,Start Date,End Date\n';
  let rows = projectsList.value.map(p => {
    return `"${p.code}","${p.name}","${p.client?.name}","${p.manager?.name}","${p.priority}",${p.progress},${p.budget},"${p.status}","${p.start_date}","${p.end_date}"`;
  }).join('\n');
  
  const blob = new Blob([headers + rows], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', 'projectflow_projects_list.csv');
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

onMounted(() => {
  fetchProjects();
  fetchDropdowns();
});
</script>
