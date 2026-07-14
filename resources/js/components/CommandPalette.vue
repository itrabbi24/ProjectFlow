<template>
  <transition name="overlay">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 md:p-20 bg-slate-900/50 backdrop-blur-sm flex justify-center items-start"
      @click.self="close"
    >
      <div 
        class="mx-auto max-w-2xl w-full transform rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 divide-y divide-slate-100 transition-all duration-300 mt-10"
        v-outside-click="close"
      >
        <!-- Search Input -->
        <div class="relative flex items-center px-4">
          <Search class="w-5 h-5 text-slate-400 flex-shrink-0" />
          <input 
            type="text" 
            v-model="query" 
            @input="handleSearch"
            ref="inputRef"
            class="h-12 w-full border-0 bg-transparent pl-3 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:ring-0 outline-none" 
            placeholder="Type a command or search keywords (e.g. projects, Acme)..."
            @keydown.down.prevent="navigateDown"
            @keydown.up.prevent="navigateUp"
            @keydown.enter.prevent="selectCurrent"
          />
          <button 
            @click="close" 
            class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded"
          >
            ESC
          </button>
        </div>

        <!-- Results / Actions List -->
        <div 
          v-if="filteredActions.length > 0 || results.projects.length > 0 || results.clients.length > 0 || results.expenses.length > 0"
          class="max-h-80 scroll-py-2 overflow-y-auto py-2 text-sm text-slate-800"
        >
          <!-- Live Data Results -->
          <div v-if="loading" class="px-4 py-3 text-xs text-slate-400 flex items-center space-x-2">
            <svg class="animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Fetching data records...</span>
          </div>

          <!-- Live Projects Section -->
          <div v-if="results.projects.length > 0" class="px-2 py-1">
            <h3 class="text-[10px] font-bold text-slate-400 px-3 py-1 uppercase tracking-wider">Matched Projects</h3>
            <div 
              v-for="(item, idx) in results.projects" 
              :key="'proj_'+item.id"
              :class="[
                'flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition-colors',
                isSelected('proj', item.id) ? 'bg-indigo-50 text-indigo-600 font-medium' : 'hover:bg-slate-50'
              ]"
              @click="goToRoute({ name: 'project-details', params: { id: item.id } })"
              @mouseenter="setSelectedIndex('proj', item.id)"
            >
              <div class="flex items-center space-x-2.5">
                <FolderOpen class="w-4 h-4 text-slate-400 flex-shrink-0" />
                <span>{{ item.name }}</span>
              </div>
              <span class="text-[10px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded">{{ item.code }}</span>
            </div>
          </div>

          <!-- Live Clients Section -->
          <div v-if="results.clients.length > 0" class="px-2 py-1">
            <h3 class="text-[10px] font-bold text-slate-400 px-3 py-1 uppercase tracking-wider">Matched Clients</h3>
            <div 
              v-for="item in results.clients" 
              :key="'client_'+item.id"
              :class="[
                'flex items-center px-3 py-2 rounded-lg cursor-pointer transition-colors',
                isSelected('client', item.id) ? 'bg-indigo-50 text-indigo-600 font-medium' : 'hover:bg-slate-50'
              ]"
              @click="goToRoute({ name: 'clients' })"
              @mouseenter="setSelectedIndex('client', item.id)"
            >
              <Users class="w-4 h-4 text-slate-400 mr-2.5 flex-shrink-0" />
              <span>{{ item.name }} ({{ item.company || 'Private' }})</span>
            </div>
          </div>

          <!-- Live Expenses Section -->
          <div v-if="results.expenses.length > 0" class="px-2 py-1">
            <h3 class="text-[10px] font-bold text-slate-400 px-3 py-1 uppercase tracking-wider">Matched Expenses</h3>
            <div 
              v-for="item in results.expenses" 
              :key="'exp_'+item.id"
              :class="[
                'flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer transition-colors',
                isSelected('exp', item.id) ? 'bg-indigo-50 text-indigo-600 font-medium' : 'hover:bg-slate-50'
              ]"
              @click="goToRoute({ name: 'expenses' })"
              @mouseenter="setSelectedIndex('exp', item.id)"
            >
              <div class="flex items-center space-x-2.5">
                <Receipt class="w-4 h-4 text-slate-400 flex-shrink-0" />
                <span>{{ item.category }} - {{ item.description || 'No description' }}</span>
              </div>
              <span class="text-xs font-semibold text-rose-500">{{ sym }}{{ item.amount.toLocaleString() }}</span>
            </div>
          </div>

          <!-- Navigation Shortcut Actions -->
          <div class="px-2 py-1">
            <h3 class="text-[10px] font-bold text-slate-400 px-3 py-1 uppercase tracking-wider">Navigation Commands</h3>
            <div 
              v-for="action in filteredActions" 
              :key="'action_'+action.name"
              :class="[
                'flex items-center px-3 py-2 rounded-lg cursor-pointer transition-colors',
                isSelected('action', action.name) ? 'bg-indigo-50 text-indigo-600 font-medium' : 'hover:bg-slate-50'
              ]"
              @click="goToRoute(action.to)"
              @mouseenter="setSelectedIndex('action', action.name)"
            >
              <component :is="action.icon" class="w-4 h-4 text-slate-400 mr-2.5 flex-shrink-0" />
              <span>Go to {{ action.name }}</span>
            </div>
          </div>
        </div>

        <div v-else class="px-4 py-8 text-center text-sm text-slate-400">
          No results or commands found.
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch, nextTick, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useSettingStore } from '@/stores/settings';
import {
  Search,
  FolderOpen,
  Users,
  Receipt,
  LayoutDashboard,
  Wallet,
  BarChart3,
  Settings,
  ShieldCheck,
  Tag
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  isOpen: Boolean
});

const settingStore = useSettingStore();
const sym = computed(() => settingStore.settings.currency_symbol || '৳');

const emit = defineEmits(['close']);

const router = useRouter();
const authStore = useAuthStore();

const query = ref('');
const loading = ref(false);
const inputRef = ref(null);

const results = ref({
  projects: [],
  clients: [],
  expenses: []
});

// All commands selection state
const selectType = ref('action'); // action, proj, client, exp
const selectKey = ref('Dashboard');

const staticActions = [
  { name: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { name: 'Projects', to: '/projects', icon: FolderOpen, permission: 'view_projects' },
  { name: 'Clients', to: '/clients', icon: Users, permission: 'view_clients' },
  { name: 'Incomes', to: '/incomes', icon: Wallet, permission: 'view_incomes' },
  { name: 'Expenses', to: '/expenses', icon: Receipt, permission: 'view_expenses' },
  { name: 'Categories', to: '/categories', icon: Tag, permission: 'view_categories' },
  { name: 'Reports', to: '/reports', icon: BarChart3, permission: 'view_reports' },
  { name: 'User Management', to: '/users', icon: ShieldCheck, permission: 'view_users' },
  { name: 'Settings', to: '/settings', icon: Settings, permission: 'view_settings' }
];

const filteredActions = computed(() => {
  return staticActions.filter(action => {
    // Filter by user permissions
    if (action.permission && !authStore.hasPermission(action.permission)) return false;
    
    // Filter by query string
    if (!query.value) return true;
    return action.name.toLowerCase().includes(query.value.toLowerCase());
  });
});

// Flatten all items currently visible to simplify cursor navigation
const flatItems = computed(() => {
  const list = [];
  results.value.projects.forEach(p => list.push({ type: 'proj', key: p.id, payload: { name: 'project-details', params: { id: p.id } } }));
  results.value.clients.forEach(c => list.push({ type: 'client', key: c.id, payload: { name: 'clients' } }));
  results.value.expenses.forEach(e => list.push({ type: 'exp', key: e.id, payload: { name: 'expenses' } }));
  filteredActions.value.forEach(a => list.push({ type: 'action', key: a.name, payload: a.to }));
  return list;
});

function isSelected(type, key) {
  return selectType.value === type && selectKey.value === key;
}

function setSelectedIndex(type, key) {
  selectType.value = type;
  selectKey.value = key;
}

function navigateDown() {
  const items = flatItems.value;
  if (items.length === 0) return;
  const currentIdx = items.findIndex(i => i.type === selectType.value && i.key === selectKey.value);
  const nextIdx = (currentIdx + 1) % items.length;
  selectType.value = items[nextIdx].type;
  selectKey.value = items[nextIdx].key;
}

function navigateUp() {
  const items = flatItems.value;
  if (items.length === 0) return;
  const currentIdx = items.findIndex(i => i.type === selectType.value && i.key === selectKey.value);
  const prevIdx = (currentIdx - 1 + items.length) % items.length;
  selectType.value = items[prevIdx].type;
  selectKey.value = items[prevIdx].key;
}

function selectCurrent() {
  const items = flatItems.value;
  const match = items.find(i => i.type === selectType.value && i.key === selectKey.value);
  if (match) {
    goToRoute(match.payload);
  }
}

let searchTimeout = null;
function handleSearch() {
  if (searchTimeout) clearTimeout(searchTimeout);

  if (query.value.trim().length < 2) {
    results.value.projects = [];
    results.value.clients = [];
    results.value.expenses = [];
    // Reset selection to first action
    if (filteredActions.value.length > 0) {
      setSelectedIndex('action', filteredActions.value[0].name);
    }
    return;
  }

  loading.value = true;
  searchTimeout = setTimeout(async () => {
    try {
      // Query projects and clients parallel
      const q = query.value.trim();
      const [projRes, clientRes, expRes] = await Promise.all([
        authStore.hasPermission('view_projects') ? axios.get(`/projects?search=${q}`) : Promise.resolve({ data: { data: { data: [] } } }),
        authStore.hasPermission('view_clients') ? axios.get(`/clients?search=${q}`) : Promise.resolve({ data: { data: { data: [] } } }),
        authStore.hasPermission('view_expenses') ? axios.get(`/expenses?search=${q}`) : Promise.resolve({ data: { data: { data: [] } } })
      ]);

      results.value.projects = projRes.data.data.data.slice(0, 3) || [];
      results.value.clients = clientRes.data.data.data.slice(0, 3) || [];
      results.value.expenses = expRes.data.data.data.slice(0, 3) || [];

      // Set default cursor to first item
      const items = flatItems.value;
      if (items.length > 0) {
        setSelectedIndex(items[0].type, items[0].key);
      }
    } catch (e) {
      console.error(e);
    } finally {
      loading.value = false;
    }
  }, 350);
}

function goToRoute(routeData) {
  close();
  router.push(routeData);
}

function close() {
  query.value = '';
  results.value.projects = [];
  results.value.clients = [];
  results.value.expenses = [];
  emit('close');
}

// Watch global key events for Ctrl+K
function globalKeyHandler(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    if (props.isOpen) {
      close();
    } else {
      inputRef.value?.focus();
      // Emitted by parent to toggle isOpen
      // We will let LayoutView manage it
    }
  }
}

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    nextTick(() => {
      inputRef.value?.focus();
    });
  }
});

const vOutsideClick = {
  mounted(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.body.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el) {
    document.body.removeEventListener('click', el.clickOutsideEvent);
  }
};

onMounted(() => {
  window.addEventListener('keydown', globalKeyHandler);
});

onUnmounted(() => {
  window.removeEventListener('keydown', globalKeyHandler);
});
</script>
