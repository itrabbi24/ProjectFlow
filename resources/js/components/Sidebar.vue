<template>
  <!-- Desktop Sidebar -->
  <aside 
    :class="[
      'fixed top-0 bottom-0 left-0 z-20 flex flex-col bg-white border-r border-slate-100 transition-all duration-300 ease-in-out',
      collapsed ? 'w-16' : 'w-64',
      'hidden md:flex'
    ]"
  >
    <!-- Logo area -->
    <div class="h-16 flex items-center px-4 border-b border-slate-100 justify-between">
      <div class="flex items-center space-x-3 overflow-hidden">
        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <span v-if="!collapsed" class="text-lg font-bold tracking-tight text-slate-800 transition-opacity duration-200">ProjectFlow</span>
      </div>
      <button 
        @click="$emit('toggle-collapse')" 
        class="hidden lg:flex items-center justify-center p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-50 focus:outline-none"
      >
        <!-- Collapse chevron -->
        <svg v-if="collapsed" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7M19 19l-7-7 7-7" />
        </svg>
      </button>
    </div>

    <!-- Navigation links -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
      <router-link
        v-for="item in filteredNavItems"
        :key="item.name"
        :to="item.to"
        v-slot="{ isActive }"
        class="block"
      >
        <div
          :class="[
            'flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 group relative',
            isActive 
              ? 'bg-indigo-50/50 text-indigo-600 font-semibold' 
              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
          ]"
        >
          <component 
            :is="item.icon" 
            :class="[
              'w-5 h-5 flex-shrink-0 transition-colors',
              isActive 
                ? 'text-indigo-600' 
                : 'text-slate-400 group-hover:text-slate-600'
            ]" 
          />
          <span 
            v-if="!collapsed" 
            class="ml-3 transition-opacity duration-200"
          >
            {{ item.name }}
          </span>
          
          <!-- Tooltip when collapsed -->
          <div 
            v-if="collapsed" 
            class="absolute left-full ml-4 px-2 py-1 bg-white text-white text-xs font-semibold rounded opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity duration-150 whitespace-nowrap z-30"
          >
            {{ item.name }}
          </div>
        </div>
      </router-link>
    </nav>

    <!-- User Profile display bottom -->
    <div class="p-3 border-t border-slate-100 overflow-hidden flex-shrink-0">
      <router-link to="/settings" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-slate-50 group">
        <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold flex-shrink-0">
          {{ authStore.user?.name.charAt(0) }}
        </div>
        <div v-if="!collapsed" class="min-w-0 flex-1">
          <p class="text-xs font-semibold text-slate-800 truncate">{{ authStore.user?.name }}</p>
          <p class="text-[10px] text-slate-400 truncate">{{ authStore.user?.role?.name }}</p>
        </div>
      </router-link>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import {
  LayoutDashboard,
  FolderOpen,
  Users,
  Wallet,
  Receipt,
  BarChart3,
  ShieldCheck,
  Settings,
  Tag
} from 'lucide-vue-next';

defineProps({
  collapsed: {
    type: Boolean,
    default: false
  }
});

defineEmits(['toggle-collapse']);

const authStore = useAuthStore();

const navItems = [
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

const filteredNavItems = computed(() => {
  return navItems.filter(item => {
    if (!item.permission) return true;
    return authStore.hasPermission(item.permission);
  });
});
</script>
