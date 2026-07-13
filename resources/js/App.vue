<template>
  <div class="h-full">
    <router-view v-slot="{ Component }">
      <transition name="page" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
    <!-- Premium Sonner Toast Notifications Container -->
    <Toaster
      position="top-right"
      :closeButton="true"
      :richColors="true"
      :offset="16"
      :mobile-offset="16"
      style="--width: 356px;"
    />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { Toaster } from 'vue-sonner';
import 'vue-sonner/style.css';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

onMounted(() => {
  authStore.fetchUser();
});
</script>
