<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">User Accounts</h1>
        <p class="text-xs text-slate-500 mt-1">Configure user profiles, assign roles, toggle statuses and audit last login dates.</p>
      </div>

      <button
        v-if="activeTab === 'users' && authStore.hasPermission('create_users')"
        @click="openCreateDrawer"
        class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm cursor-pointer"
      >
        <Plus class="w-4 h-4" />
        <span>Add User</span>
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex items-center space-x-1 border-b border-slate-100">
      <button
        @click="activeTab = 'users'"
        :class="[
          'px-4 py-2.5 text-xs font-semibold border-b-2 transition cursor-pointer',
          activeTab === 'users' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'
        ]"
      >
        Users
      </button>
      <button
        v-if="authStore.hasPermission('assign_roles')"
        @click="openRolesTab"
        :class="[
          'px-4 py-2.5 text-xs font-semibold border-b-2 transition cursor-pointer',
          activeTab === 'roles' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'
        ]"
      >
        Roles &amp; Menu Permissions
      </button>
    </div>

    <!-- Filters & Search -->
    <div v-if="activeTab === 'users'" class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm">
      <div class="relative w-64">
        <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
        <input
          type="text"
          v-model="filters.search"
          @input="debouncedSearch"
          class="pl-9 pr-4 py-1.5 w-full rounded-lg border border-slate-200 bg-transparent text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500"
          placeholder="Search name or username..."
        />
      </div>
    </div>

    <!-- Table Loading skeleton -->
    <div v-if="activeTab === 'users' && loading" class="bg-white border border-slate-100 rounded-2xl p-6 space-y-4">
      <div class="h-10 w-full bg-slate-100 rounded animate-pulse" v-for="i in 4" :key="i"></div>
    </div>

    <!-- Users Table -->
    <div v-else-if="activeTab === 'users'" class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
              <th class="p-4">Name</th>
              <th class="p-4">Username</th>
              <th class="p-4">Email</th>
              <th class="p-4">Role</th>
              <th class="p-4">Status</th>
              <th class="p-4">Last Login</th>
              <th class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr 
              v-for="user in usersList" 
              :key="user.id" 
              class="hover:bg-slate-50/50 transition"
            >
              <td class="p-4 font-bold text-slate-800 flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 font-bold flex items-center justify-center text-xs">
                  {{ user.name.charAt(0) }}
                </div>
                <span>{{ user.name }}</span>
              </td>
              <td class="p-4 font-mono font-medium text-slate-500">{{ user.username }}</td>
              <td class="p-4 text-slate-600">{{ user.email }}</td>
              <td class="p-4"><span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-700">{{ user.role?.name }}</span></td>
              <td class="p-4">
                <span 
                  :class="[
                    'inline-block px-2.5 py-0.5 rounded-full text-[10px] font-medium border',
                    user.status === 'active' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-50 text-slate-500 border-slate-200'
                  ]"
                >
                  {{ user.status }}
                </span>
              </td>
              <td class="p-4 text-slate-500">{{ formatTime(user.last_login_at) }}</td>
              <td class="p-4 text-center sticky right-0 bg-white shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.02)]">
                <div class="flex items-center justify-center space-x-1.5">
                  <button 
                    v-if="authStore.hasPermission('edit_users')"
                    @click="openEditDrawer(user)"
                    class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Edit"
                  >
                    <Edit class="w-4 h-4" />
                  </button>
                  <button 
                    v-if="authStore.hasPermission('delete_users') && user.id !== authStore.user?.id"
                    @click="handleDelete(user.id)"
                    class="p-1 rounded text-slate-400 hover:text-rose-500 hover:bg-slate-50 focus:outline-none cursor-pointer"
                    title="Delete"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="usersList.length === 0">
              <td colspan="7" class="p-8 text-center text-slate-400">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="usersData.last_page > 1" class="px-4 py-3 bg-slate-50/50 border-t border-slate-50 flex items-center justify-between text-xs">
        <span class="text-slate-500">Page {{ usersData.current_page }} of {{ usersData.last_page }}</span>
        <div class="flex space-x-1">
          <button 
            @click="changePage(usersData.current_page - 1)" 
            :disabled="usersData.current_page === 1"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Prev
          </button>
          <button 
            @click="changePage(usersData.current_page + 1)" 
            :disabled="usersData.current_page === usersData.last_page"
            class="px-3 py-1.5 border border-slate-200 rounded bg-white text-slate-500 disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Roles & Permissions Tab -->
    <div v-if="activeTab === 'roles'" class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div v-if="rolesLoading" class="p-6 space-y-4">
        <div class="h-10 w-full bg-slate-100 rounded animate-pulse" v-for="i in 3" :key="i"></div>
      </div>
      <table v-else class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
            <th class="p-4">Role</th>
            <th class="p-4">Permissions Granted</th>
            <th class="p-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="role in rolesList" :key="role.id" class="hover:bg-slate-50/50 transition">
            <td class="p-4 font-bold text-slate-800">
              {{ role.name }}
              <span v-if="role.slug === 'administrator'" class="ml-1.5 text-[10px] font-medium text-indigo-500">(full access)</span>
            </td>
            <td class="p-4 text-slate-500">{{ role.permissions?.length || 0 }} permission(s)</td>
            <td class="p-4 text-center">
              <button
                v-if="role.slug !== 'administrator'"
                @click="openPermissionsDrawer(role)"
                class="px-3 py-1.5 rounded-lg text-[11px] font-semibold border border-slate-200 text-slate-600 hover:bg-slate-50 transition cursor-pointer"
              >
                Manage Menu Access
              </button>
              <span v-else class="text-[11px] text-slate-300">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Role Permissions Drawer -->
    <Drawer
      :is-open="permissionsDrawerOpen"
      :title="`Menu Access — ${editingRole?.name || ''}`"
      subtitle="Choose which menus and actions this role's users can see."
      @close="permissionsDrawerOpen = false"
    >
      <div class="space-y-5">
        <div v-for="(perms, category) in permissionsByCategory" :key="category">
          <h3 class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">{{ category }}</h3>
          <div class="space-y-1.5">
            <label
              v-for="perm in perms"
              :key="perm.id"
              class="flex items-center space-x-2.5 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer"
            >
              <input
                type="checkbox"
                :value="perm.id"
                v-model="selectedPermissionIds"
                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
              />
              <span class="text-xs text-slate-700">{{ perm.name }}</span>
            </label>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-2">
          <button
            type="button"
            @click="permissionsDrawerOpen = false"
            class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="savePermissions"
            :disabled="savingPermissions"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 transition disabled:opacity-50 cursor-pointer"
          >
            {{ savingPermissions ? 'Saving...' : 'Save Menu Access' }}
          </button>
        </div>
      </template>
    </Drawer>

    <!-- User account Form Drawer -->
    <Drawer 
      :is-open="drawerOpen" 
      :title="isEditing ? 'Edit User Profile' : 'Add User profile'" 
      subtitle="Complete profile parameters and security credentials."
      @close="closeDrawer"
    >
      <form @submit.prevent="saveUser" class="space-y-4">
        <!-- Display Name -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Display Name</label>
          <input 
            type="text" 
            required 
            v-model="form.name"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500"
            placeholder="e.g. John Doe"
          />
        </div>

        <!-- Username -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Username</label>
          <input 
            type="text" 
            required 
            v-model="form.username"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500"
            placeholder="e.g. john.doe"
          />
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Email Address</label>
          <input 
            type="email" 
            required 
            v-model="form.email"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500"
            placeholder="john.doe@company.com"
          />
        </div>

        <!-- Password -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Password {{ isEditing ? '(Leave blank to keep current)' : '' }}</label>
          <input 
            type="password" 
            :required="!isEditing"
            v-model="form.password"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none focus:border-indigo-500"
            placeholder="••••••••"
          />
        </div>

        <!-- Role Dropdown -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Assign Role</label>
          <select 
            required
            v-model="form.role_id"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select System Role</option>
            <option v-for="role in rolesList" :key="role.id" :value="role.id">{{ role.name }}</option>
          </select>
        </div>

        <!-- Status Toggle -->
        <div>
          <label class="block text-xs font-semibold text-slate-700">Active Status</label>
          <select 
            v-model="form.status"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none focus:border-indigo-500"
          >
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
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
            @click="saveUser"
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
import { Plus, Search, Edit, Trash2 } from 'lucide-vue-next';
import axios from 'axios';
import Drawer from '@/components/ui/Drawer.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue-sonner';
import { formatDateTime } from '@/utils/date';

const authStore = useAuthStore();

const loading = ref(true);
const saving = ref(false);
const usersList = ref([]);
const usersData = ref({});
const rolesList = ref([]);

// Roles & Permissions tab
const activeTab = ref('users');
const rolesLoading = ref(false);
const permissionsByCategory = ref({});
const permissionsDrawerOpen = ref(false);
const editingRole = ref(null);
const selectedPermissionIds = ref([]);
const savingPermissions = ref(false);

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
  username: '',
  email: '',
  password: '',
  role_id: '',
  status: 'active'
});

async function fetchUsers() {
  loading.value = true;
  try {
    let url = `/users?page=${filters.page}`;
    if (filters.search) url += `&search=${filters.search}`;

    const response = await axios.get(url);
    usersData.value = response.data.data;
    usersList.value = response.data.data.data;
  } catch (error) {
    console.error(error);
    toast.error('Failed to load users.');
  } finally {
    loading.value = false;
  }
}

async function fetchRoles() {
  try {
    const response = await axios.get('/roles');
    rolesList.value = response.data.data || [];
  } catch (e) {
    console.error(e);
  }
}

function openRolesTab() {
  activeTab.value = 'roles';
  fetchRolesTabData();
}

async function fetchRolesTabData() {
  rolesLoading.value = true;
  try {
    await fetchRoles();
  } finally {
    rolesLoading.value = false;
  }
}

async function openPermissionsDrawer(role) {
  editingRole.value = role;
  selectedPermissionIds.value = (role.permissions || []).map(p => p.id);
  permissionsDrawerOpen.value = true;

  if (Object.keys(permissionsByCategory.value).length === 0) {
    try {
      const response = await axios.get('/permissions');
      permissionsByCategory.value = response.data.data || {};
    } catch (e) {
      console.error(e);
      toast.error('Failed to load permission list.');
    }
  }
}

async function savePermissions() {
  if (!editingRole.value) return;
  savingPermissions.value = true;
  try {
    await axios.post(`/roles/${editingRole.value.id}/permissions`, {
      permissions: selectedPermissionIds.value
    });
    toast.success('Menu access updated successfully.');
    permissionsDrawerOpen.value = false;
    fetchRoles();
  } catch (error) {
    console.error(error);
    toast.error(error.response?.data?.message || 'Failed to update menu access.');
  } finally {
    savingPermissions.value = false;
  }
}

let debounceTimer = null;
function debouncedSearch() {
  if (debounceTimer) clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    filters.page = 1;
    fetchUsers();
  }, 350);
}

function changePage(page) {
  filters.page = page;
  fetchUsers();
}

function openCreateDrawer() {
  isEditing.value = false;
  editingId.value = null;
  form.name = '';
  form.username = '';
  form.email = '';
  form.password = '';
  form.role_id = '';
  form.status = 'active';
  drawerOpen.value = true;
}

function openEditDrawer(user) {
  isEditing.value = true;
  editingId.value = user.id;
  form.name = user.name;
  form.username = user.username;
  form.email = user.email;
  form.password = ''; // Keep blank
  form.role_id = user.role_id;
  form.status = user.status;
  drawerOpen.value = true;
}

function closeDrawer() {
  drawerOpen.value = false;
}

async function saveUser() {
  saving.value = true;
  try {
    if (isEditing.value) {
      await axios.put(`/users/${editingId.value}`, form);
      toast.success('User profile updated successfully.');
    } else {
      await axios.post('/users', form);
      toast.success('User account created successfully.');
    }
    closeDrawer();
    fetchUsers();
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
    text: "This soft-deletes the user's system profile. They will lose access to all modules.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete user'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/users/${id}`);
      toast.success('User soft-deleted.');
      fetchUsers();
    } catch (error) {
      console.error(error);
      toast.error('Failed to delete user.');
    }
  }
}

function formatTime(dateStr) {
  if (!dateStr) return 'Never';
  return formatDateTime(dateStr);
}

onMounted(() => {
  fetchUsers();
  fetchRoles();
});
</script>
