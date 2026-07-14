<template>
  <div class="h-full flex overflow-hidden bg-slate-50 transition-colors duration-300">
    <!-- Collapsible Sidebar (Desktop) -->
    <Sidebar :collapsed="sidebarCollapsed" @toggle-collapse="toggleSidebar" />

    <!-- Slide-over Drawer Mobile Sidebar Menu Overlay -->
    <transition name="overlay">
      <div 
        v-if="mobileMenuOpen" 
        class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm md:hidden"
        @click="mobileMenuOpen = false"
      ></div>
    </transition>
    
    <!-- Mobile Sidebar Drawer Content -->
    <transition name="drawer">
      <aside 
        v-if="mobileMenuOpen"
        class="fixed top-0 bottom-0 left-0 w-64 z-50 bg-white border-r border-slate-100 md:hidden flex flex-col"
      >
        <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold">
              $
            </div>
            <span class="text-lg font-bold text-slate-800">ProjectFlow</span>
          </div>
          <button @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-50">
            <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
          <router-link
            v-for="item in filteredNavItems"
            :key="item.name"
            :to="item.to"
            v-slot="{ isActive }"
            class="block"
            @click="mobileMenuOpen = false"
          >
            <div
              :class="[
                'flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150',
                isActive 
                  ? 'bg-indigo-50/50 text-indigo-600 font-semibold' 
                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
              ]"
            >
              <component :is="item.icon" class="w-5 h-5 flex-shrink-0 mr-3 text-slate-400" />
              <span>{{ item.name }}</span>
            </div>
          </router-link>
        </nav>
      </aside>
    </transition>

    <!-- Main Content Area -->
    <div 
      :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-300 ease-in-out pb-16 md:pb-0',
        sidebarCollapsed ? 'md:pl-16' : 'md:pl-64'
      ]"
    >
      <!-- Top header -->
      <Header 
        @toggle-mobile-sidebar="mobileMenuOpen = true" 
        @open-search="searchOpen = true" 
      />

      <!-- Main scrollable layout -->
      <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 flex flex-col">
        <router-view v-slot="{ Component }">
          <transition name="page" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>

        <footer class="mt-auto pt-6 text-center text-xs text-slate-400">
          Developed by <a href="https://rotexit.com" target="_blank" rel="noopener noreferrer" class="text-slate-500 hover:text-indigo-600 transition-colors">RotexIT.Com</a>
        </footer>
      </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <BottomNav @open-mobile-menu="mobileMenuOpen = true" />

    <!-- Global Command Palette -->
    <CommandPalette :is-open="searchOpen" @close="searchOpen = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSettingStore } from '@/stores/settings';
import Sidebar from '@/components/Sidebar.vue';
import Header from '@/components/Header.vue';
import BottomNav from '@/components/BottomNav.vue';
import CommandPalette from '@/components/CommandPalette.vue';
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

const authStore = useAuthStore();
const settingStore = useSettingStore();

const sidebarCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true');
const mobileMenuOpen = ref(false);
const searchOpen = ref(false);

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  localStorage.setItem('sidebar_collapsed', sidebarCollapsed.value);
}

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

onMounted(() => {
  settingStore.fetchSettings();
});
</script>
