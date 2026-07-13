<template>
  <teleport to="body">
    <!-- Backdrop Overlay -->
    <transition name="overlay">
      <div 
        v-if="isOpen" 
        class="fixed inset-0 z-40 bg-slate-900/20 backdrop-blur-sm transition-opacity duration-300"
        @click="close"
      ></div>
    </transition>

    <!-- Slide-over Drawer Panel -->
    <transition name="drawer">
      <div 
        v-if="isOpen"
        class="fixed inset-y-0 right-0 max-w-lg w-full bg-white shadow-2xl border-l border-slate-100 flex flex-col z-50 transition-all duration-300"
      >
        <!-- Header -->
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-100 flex-shrink-0">
          <div>
            <h2 class="text-sm font-bold text-slate-800">{{ title }}</h2>
            <p v-if="subtitle" class="text-[10px] text-slate-400 mt-0.5">{{ subtitle }}</p>
          </div>
          <button 
            @click="close" 
            class="p-2 rounded-lg hover:bg-slate-50 text-slate-400 hover:text-slate-600 focus:outline-none"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Scrollable Body -->
        <div class="flex-1 overflow-y-auto p-6">
          <slot></slot>
        </div>

        <!-- Footer (optional) -->
        <div 
          v-if="$slots.footer"
          class="px-6 py-4 border-t border-slate-50 bg-slate-50/50 flex-shrink-0"
        >
          <slot name="footer"></slot>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { X } from 'lucide-vue-next';

defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close']);

function close() {
  emit('close');
}
</script>
