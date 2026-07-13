<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Clients</h1>
        <p class="text-xs text-slate-500 mt-1">Manage project clients, company contacts and relationship profiles.</p>
      </div>

      <button 
        v-if="authStore.hasPermission('create_clients')"
        @click="openCreateDrawer" 
        class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Add Client</span>
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm">
      <div class="relative w-64">
        <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
        <input 
          type="text" 
          v-model="filters.search"
          @input="debouncedSearch"
          class="pl-9 pr-4 py-1.5 w-full rounded-lg border border-slate-200 bg-transparent text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500"
          placeholder="Search client or company..."
        />
      </div>
    </div>

    <!-- Table Loading skeleton -->
    <div v-if="loading" class="bg-white border border-slate-100 rounded-2xl p-6 space-y-4">
      <div class="h-10 w-full bg-slate-100 rounded animate-pulse" v-for="i in 4" :key="i"></div>
    </div>

    <!-- Clients Table -->
    <div v-else class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
              <th class="p-4">Name</th>
              <th class="p-4">Company</th>
              <th class="p-4">Email</th>
              <th class="p-4">Phone</th>
              <th class="p-4">Address</th>
              <th class="p-4">Remarks</th>
              <th class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr 
              v-for="client in clientsList" 
              :key="client.id" 
              class="hover:bg-slate-50/50 transition"
            >
              <td class="p-4 font-bold text-slate-800">{{ client.name }}</td>
              <td class="p-4 text-slate-600">{{ client.company || 'Private Client' }}</td>
              <td class="p-4 text-slate-600">{{ client.email || 'N/A' }}</td>
              <td class="p-4 text-slate-600">{{ client.phone || 'N/A' }}</td>
              <td class="p-4 text-slate-500 truncate max-w-xs">{{ client.address || 'N/A' }}</td>
              <td class="p-4 text-slate-500 truncate max-w-xs">{{ client.remarks || 'No remarks' }}</td>
              <td class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-center space-x-1.5">
                  <button 
                    v-if="authStore.hasPermission('edit_clients')"
                    @click="openEditDrawer(client)"
                    class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Edit"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button 
                    v-if="authStore.hasPermission('delete_clients')"
                    @click="handleDelete(client.id)"
                    class="p-1 rounded text-slate-400 hover:text-rose-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Delete"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="clientsList.length === 0">
              <td colspan="7" class="p-8 text-center text-slate-400">
                <Users class="w-12 h-12 mx-auto text-slate-600 mb-2" />
                <p class="font-medium text-slate-500">No Clients Found</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="clientsData.last_page > 1" class="px-4 py-3 bg-slate-50/50 border-t border-slate-50 flex items-center justify-between text-xs">
        <span class="text-slate-500">Page {{ clientsData.current_page }} of {{ clientsData.last_page }}</span>
        <div class="flex space-x-1">
          <button 
            @click="changePage(clientsData.current_page - 1)" 
            :disabled="clientsData.current_page === 1"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Prev
          </button>
          <button 
            @click="changePage(clientsData.current_page + 1)" 
            :disabled="clientsData.current_page === clientsData.last_page"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Client Drawer -->
    <Drawer 
      :is-open="drawerOpen" 
      :title="isEditing ? 'Edit Client Profile' : 'Add Client profile'" 
      subtitle="Complete information for contact invoicing."
      @close="closeDrawer"
    >
      <form @submit.prevent="saveClient" class="space-y-4">
        <!-- Client Name -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Client Name</label>
          <input 
            type="text" 
            required 
            v-model="form.name"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500"
            placeholder="e.g. Pepper Potts"
          />
        </div>

        <!-- Company -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Company Name</label>
          <input 
            type="text" 
            v-model="form.company"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500"
            placeholder="e.g. Stark Industries LLC"
          />
        </div>

        <!-- Email & Phone Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Email Address</label>
            <input 
              type="email" 
              v-model="form.email"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="pepper@stark.com"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Phone Number</label>
            <input 
              type="text" 
              v-model="form.phone"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="+1 (555) 999-8888"
            />
          </div>
        </div>

        <!-- Address -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Billing Address</label>
          <textarea 
            rows="2"
            v-model="form.address"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="10880 Malibu Point, Malibu, CA"
          ></textarea>
        </div>

        <!-- Remarks -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Internal Remarks</label>
          <textarea 
            rows="2"
            v-model="form.remarks"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="Important invoicing preferences..."
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
            @click="saveClient"
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
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { Plus, Search, Edit, Trash2, Users } from 'lucide-vue-next';
import axios from 'axios';
import Drawer from '@/components/ui/Drawer.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue-sonner';

const authStore = useAuthStore();

const loading = ref(true);
const saving = ref(false);
const clientsList = ref([]);
const clientsData = ref({});

const filters = reactive({
  search: '',
  page: 1
});

// Drawer state
const drawerOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = reactive({
  name: '',
  company: '',
  email: '',
  phone: '',
  address: '',
  remarks: ''
});

async function fetchClients() {
  loading.value = true;
  try {
    let url = `/clients?page=${filters.page}`;
    if (filters.search) url += `&search=${filters.search}`;

    const response = await axios.get(url);
    clientsData.value = response.data.data;
    clientsList.value = response.data.data.data;
  } catch (error) {
    console.error(error);
    toast.error('Failed to fetch clients.');
  } finally {
    loading.value = false;
  }
}

let debounceTimer = null;
function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.page = 1;
    fetchClients();
  }, 350);
}

function changePage(page) {
  filters.page = page;
  fetchClients();
}

function openCreateDrawer() {
  isEditing.value = false;
  editingId.value = null;
  form.name = '';
  form.company = '';
  form.email = '';
  form.phone = '';
  form.address = '';
  form.remarks = '';
  drawerOpen.value = true;
}

function openEditDrawer(client) {
  isEditing.value = true;
  editingId.value = client.id;
  form.name = client.name;
  form.company = client.company || '';
  form.email = client.email || '';
  form.phone = client.phone || '';
  form.address = client.address || '';
  form.remarks = client.remarks || '';
  drawerOpen.value = true;
}

function closeDrawer() {
  drawerOpen.value = false;
}

async function saveClient() {
  saving.value = true;
  try {
    if (isEditing.value) {
      await axios.put(`/clients/${editingId.value}`, form);
      toast.success('Client profile updated.');
    } else {
      await axios.post('/clients', form);
      toast.success('Client profile added.');
    }
    closeDrawer();
    fetchClients();
  } catch (error) {
    console.error(error);
    toast.error('Failed to save client.');
  } finally {
    saving.value = false;
  }
}

async function handleDelete(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: "This client and all of their active projects will be soft-deleted.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete client'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/clients/${id}`);
      toast.success('Client profile deleted.');
      fetchClients();
    } catch (error) {
      console.error(error);
      toast.error('Failed to delete client.');
    }
  }
}

onMounted(() => {
  fetchClients();
});
</script>
