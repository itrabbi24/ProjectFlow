<template>
  <header class="sticky top-0 z-10 h-16 bg-white/80 backdrop-blur-md border-b border-slate-100 flex items-center justify-between px-4 sm:px-6">
    <!-- Left: Collapse toggle and page name/search -->
    <div class="flex items-center space-x-3">
      <button 
        @click="$emit('toggle-mobile-sidebar')" 
        class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-50 focus:outline-none"
      >
        <!-- Mobile Menu Icon -->
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <!-- Global Search Command Trigger Button -->
      <button 
        @click="$emit('open-search')"
        class="hidden sm:flex items-center space-x-2 text-sm text-slate-400 hover:text-slate-600 bg-slate-50 border border-slate-200/50 px-3 py-1.5 rounded-lg w-64 text-left transition"
      >
        <Search class="w-4 h-4 text-slate-400" />
        <span class="flex-1">Search anything...</span>
        <kbd class="text-[10px] bg-white border border-slate-200 px-1.5 py-0.5 rounded text-slate-400">Ctrl K</kbd>
      </button>
    </div>

    <!-- Right: Quick actions, notifications, dark mode, profile -->
    <div class="flex items-center space-x-3">
      <!-- Mobile Search Icon -->
      <button 
        @click="$emit('open-search')" 
        class="sm:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-50 focus:outline-none"
      >
        <Search class="w-5 h-5" />
      </button>


      <!-- Notifications Popover -->
      <div class="relative">
        <button 
          @click="showNotifications = !showNotifications" 
          v-outside-click="closeNotifications"
          class="p-2 rounded-lg text-slate-500 hover:bg-slate-50 focus:outline-none relative"
        >
          <Bell class="w-5 h-5" />
          <span 
            v-if="unreadCount > 0" 
            class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white"
          ></span>
        </button>

        <!-- Dropdown Notifications Menu -->
        <div 
          v-if="showNotifications"
          class="absolute right-0 mt-2 w-80 bg-white border border-slate-100 shadow-xl rounded-xl py-2 z-30"
        >
          <div class="px-4 py-2 border-b border-slate-50 flex justify-between items-center">
            <span class="text-xs font-bold text-slate-800">System Logs & Activities</span>
            <button @click="clearNotifications" class="text-[10px] text-indigo-500 hover:underline">Clear</button>
          </div>
          <div class="max-h-64 overflow-y-auto divide-y divide-slate-50">
            <div 
              v-for="log in recentActivities" 
              :key="log.id" 
              class="px-4 py-3 text-xs hover:bg-slate-50"
            >
              <p class="text-slate-700">{{ log.description }}</p>
              <p class="text-[10px] text-slate-400 mt-1">{{ formatTime(log.created_at) }}</p>
            </div>
            <div v-if="recentActivities.length === 0" class="px-4 py-6 text-center text-xs text-slate-400">
              No recent notifications
            </div>
          </div>
        </div>
      </div>

      <!-- User Profile Menu -->
      <div class="relative">
        <button 
          @click="showProfile = !showProfile" 
          v-outside-click="closeProfile"
          class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-slate-50 focus:outline-none transition"
        >
          <div class="w-8 h-8 rounded-lg bg-indigo-600/10 text-indigo-600 flex items-center justify-center font-bold text-sm">
            {{ authStore.user?.name.charAt(0) }}
          </div>
          <ChevronDown class="w-4 h-4 text-slate-400 hidden sm:block" />
        </button>

        <!-- Dropdown profile menu -->
        <div 
          v-if="showProfile"
          class="absolute right-0 mt-2 w-48 bg-white border border-slate-100 shadow-xl rounded-xl py-1 z-30"
        >
          <div class="px-4 py-2 border-b border-slate-50">
            <p class="text-xs font-semibold text-slate-800 truncate">{{ authStore.user?.name }}</p>
            <p class="text-[10px] text-slate-400 truncate">{{ authStore.user?.email }}</p>
          </div>
          <router-link 
            to="/settings" 
            @click="showProfile = false"
            class="flex items-center space-x-2 px-4 py-2 text-xs text-slate-600 hover:bg-slate-50 hover:text-slate-900"
          >
            <User class="w-4 h-4" />
            <span>Profile settings</span>
          </router-link>
          <button 
            @click="handleLogout"
            class="flex items-center space-x-2 w-full text-left px-4 py-2 text-xs text-rose-500 hover:bg-rose-50"
          >
            <LogOut class="w-4 h-4" />
            <span>Sign out</span>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSettingStore } from '@/stores/settings';
import { useRouter } from 'vue-router';
import { 
  Search, 
  Bell, 
  ChevronDown, 
  User, 
  LogOut 
} from 'lucide-vue-next';
import axios from 'axios';
import { formatDateTime } from '@/utils/date';

defineEmits(['toggle-mobile-sidebar', 'open-search']);

const authStore = useAuthStore();
const settingStore = useSettingStore();
const router = useRouter();

const showNotifications = ref(false);
const showProfile = ref(false);

const unreadCount = ref(0);
const recentActivities = ref([]);

function closeNotifications() {
  showNotifications.value = false;
}

function closeProfile() {
  showProfile.value = false;
}


async function loadNotifications() {
  try {
    const response = await axios.get('/dashboard-stats');
    // We can extract activity logs or use a subset of recent logs as notifications
    const recent = response.data.data.recent_transactions || [];
    recentActivities.value = recent.map(t => ({
      id: t.id + '_' + t.type,
      description: `New ${t.type} of $${t.amount.toLocaleString()} logged in project: ${t.project?.name || 'N/A'}`,
      created_at: t.date
    }));
    unreadCount.value = recent.length > 0 ? 1 : 0;
  } catch (error) {
    console.error('Failed to load notifications:', error);
  }
}

function clearNotifications() {
  unreadCount.value = 0;
  recentActivities.value = [];
}

async function handleLogout() {
  await authStore.logout();
  router.push({ name: 'login' });
}

function formatTime(dateStr) {
  if (!dateStr) return '';
  return formatDateTime(dateStr);
}

// Custom directive for clicking outside popovers to close them
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
  loadNotifications();
});
</script>
