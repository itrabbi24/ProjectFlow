<template>
  <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white/95 backdrop-blur-md border-t border-slate-100 flex items-center justify-around px-2 pb-safe z-10 shadow-lg">
    <router-link 
      to="/dashboard" 
      v-slot="{ isActive }" 
      class="flex flex-col items-center justify-center w-16 h-full text-slate-500"
    >
      <LayoutDashboard :class="['w-5 h-5', isActive ? 'text-indigo-600' : '']" />
      <span :class="['text-[10px] mt-1', isActive ? 'text-indigo-600 font-semibold' : '']">Home</span>
    </router-link>

    <router-link 
      to="/projects" 
      v-slot="{ isActive }" 
      class="flex flex-col items-center justify-center w-16 h-full text-slate-500"
    >
      <FolderOpen :class="['w-5 h-5', isActive ? 'text-indigo-600' : '']" />
      <span :class="['text-[10px] mt-1', isActive ? 'text-indigo-600 font-semibold' : '']">Projects</span>
    </router-link>

    <router-link 
      to="/expenses" 
      v-slot="{ isActive }" 
      class="flex flex-col items-center justify-center w-16 h-full text-slate-500"
    >
      <Receipt :class="['w-5 h-5', isActive ? 'text-indigo-600' : '']" />
      <span :class="['text-[10px] mt-1', isActive ? 'text-indigo-600 font-semibold' : '']">Expenses</span>
    </router-link>

    <button 
      @click="$emit('open-mobile-menu')" 
      class="flex flex-col items-center justify-center w-16 h-full text-slate-500 focus:outline-none"
    >
      <Menu class="w-5 h-5 text-slate-500" />
      <span class="text-[10px] mt-1">Menu</span>
    </button>
  </nav>
</template>

<script setup>
import { LayoutDashboard, FolderOpen, Receipt, Menu } from 'lucide-vue-next';

defineEmits(['open-mobile-menu']);
</script>
