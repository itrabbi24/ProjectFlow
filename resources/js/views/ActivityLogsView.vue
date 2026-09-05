<template>
  <div class="space-y-6 pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Activity Audit Log</h1>
        <p class="text-xs text-slate-500 mt-1">Real-time audit trail of actions, system logins, asset operations and finances.</p>
      </div>

      <button
        @click="fetchLogs"
        class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition cursor-pointer"
      >
        <RefreshCw :class="['w-3.5 h-3.5 text-slate-500', loading ? 'animate-spin' : '']" />
        <span>Refresh Logs</span>
      </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
      <div class="relative w-full sm:w-72">
        <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
        <input
          type="text"
          v-model="searchQuery"
          @input="debounceSearch"
          placeholder="Search activity description or IP..."
          class="pl-9 pr-4 py-1.5 w-full rounded-lg border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-indigo-500"
        />
      </div>

      <div class="flex items-center space-x-2 w-full sm:w-auto">
        <select
          v-model="filterAction"
          @change="fetchLogs"
          class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 bg-white focus:outline-none focus:border-indigo-500"
        >
          <option value="">All Actions</option>
          <option value="created">Created</option>
          <option value="updated">Updated</option>
          <option value="deleted">Deleted</option>
          <option value="disposed">Disposed / Write-Off</option>
          <option value="login">Login</option>
          <option value="logout">Logout</option>
        </select>
      </div>
    </div>

    <!-- Activity Log List / Table -->
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div v-if="loading" class="p-8 space-y-3">
        <div class="h-12 bg-slate-100 rounded animate-pulse" v-for="i in 5" :key="i"></div>
      </div>

      <div v-else-if="logs.length === 0" class="p-12 text-center text-slate-400 text-xs">
        <ScrollText class="w-12 h-12 mx-auto text-slate-300 mb-2" />
        <p class="font-medium text-slate-600">No activity logs found</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
              <th class="p-3.5">User</th>
              <th class="p-3.5">Action</th>
              <th class="p-3.5">Description</th>
              <th class="p-3.5">IP Address</th>
              <th class="p-3.5">Date &amp; Time</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/50 transition">
              <td class="p-3.5">
                <div v-if="log.user" class="flex items-center space-x-2">
                  <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 font-bold flex items-center justify-center text-xs">
                    {{ log.user.name.charAt(0) }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-800 block">{{ log.user.name }}</span>
                    <span class="text-[10px] text-slate-400">@{{ log.user.username }}</span>
                  </div>
                </div>
                <span v-else class="text-slate-400 italic">System</span>
              </td>
              <td class="p-3.5">
                <span
                  :class="[
                    'inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                    getActionBadgeClass(log.action)
                  ]"
                >
                  {{ log.action }}
                </span>
              </td>
              <td class="p-3.5 font-medium text-slate-700 max-w-md">
                {{ log.description }}
              </td>
              <td class="p-3.5 font-mono text-[11px] text-slate-500">
                {{ log.ip_address || '—' }}
              </td>
              <td class="p-3.5 text-slate-500 whitespace-nowrap">
                {{ formatDateTime(log.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="p-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
        <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries</span>
        <div class="flex items-center space-x-1">
          <button
            :disabled="!pagination.prev_page_url"
            @click="changePage(pagination.current_page - 1)"
            class="px-2.5 py-1 rounded border border-slate-200 disabled:opacity-40 hover:bg-slate-50 cursor-pointer"
          >
            Prev
          </button>
          <span class="px-2 font-semibold text-slate-800">{{ pagination.current_page }}</span>
          <button
            :disabled="!pagination.next_page_url"
            @click="changePage(pagination.current_page + 1)"
            class="px-2.5 py-1 rounded border border-slate-200 disabled:opacity-40 hover:bg-slate-50 cursor-pointer"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { formatDateTime } from '@/utils/date';
import { ScrollText, Search, RefreshCw } from 'lucide-vue-next';

const loading = ref(false);
const logs = ref([]);
const searchQuery = ref('');
const filterAction = ref('');

const pagination = ref({
  current_page: 1,
  total: 0,
  per_page: 20,
  from: 0,
  to: 0,
  prev_page_url: null,
  next_page_url: null
});

let debounceTimer = null;
function debounceSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchLogs();
  }, 350);
}

async function fetchLogs(page = 1) {
  loading.value = true;
  try {
    const params = {
      search: searchQuery.value,
      action: filterAction.value,
      page: page
    };
    const response = await axios.get('/activity-logs', { params });
    logs.value = response.data.data.data;
    pagination.value = {
      current_page: response.data.data.current_page,
      total: response.data.data.total,
      per_page: response.data.data.per_page,
      from: response.data.data.from,
      to: response.data.data.to,
      prev_page_url: response.data.data.prev_page_url,
      next_page_url: response.data.data.next_page_url
    };
  } catch (err) {
    console.error('Failed to load activity logs:', err);
  } finally {
    loading.value = false;
  }
}

function changePage(page) {
  if (page < 1) return;
  fetchLogs(page);
}

function getActionBadgeClass(action) {
  switch (action) {
    case 'created':
      return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'updated':
      return 'bg-blue-50 text-blue-600 border-blue-100';
    case 'deleted':
      return 'bg-rose-50 text-rose-600 border-rose-100';
    case 'disposed':
      return 'bg-amber-50 text-amber-600 border-amber-100';
    case 'login':
      return 'bg-purple-50 text-purple-600 border-purple-100';
    case 'logout':
      return 'bg-slate-50 text-slate-500 border-slate-200';
    default:
      return 'bg-slate-50 text-slate-600 border-slate-200';
  }
}

onMounted(() => {
  fetchLogs();
});
</script>
