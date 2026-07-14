<template>
  <div class="min-h-screen flex bg-slate-50 transition-colors duration-300">
    <!-- Left panel: Login form -->
    <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
      <div class="mx-auto w-full max-w-sm lg:w-96 bg-white sm:p-8 rounded-2xl sm:shadow-xl sm:border border-slate-100">
        <!-- Logo Header -->
        <div class="flex items-center space-x-3 mb-8">
          <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <span class="text-2xl font-bold tracking-tight text-slate-800">ProjectFlow</span>
        </div>

        <div>
          <h2 class="text-xl font-bold text-slate-900 tracking-tight">Sign in to your account</h2>
          <p class="mt-1.5 text-xs text-slate-400">Enter your credentials below to access ProjectFlow.</p>
        </div>

        <div class="mt-6">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Username -->
            <div>
              <label for="username" class="block text-xs font-semibold text-slate-700">Username</label>
              <div class="mt-1">
                <input 
                  id="username" 
                  name="username" 
                  type="text" 
                  required 
                  v-model="form.username"
                  class="appearance-none block w-full px-3 py-2 border border-slate-200 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm bg-transparent text-slate-900"
                  placeholder="e.g. admin"
                />
              </div>
            </div>

            <!-- Password -->
            <div>
              <div class="flex justify-between items-center">
                <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
                <a href="#" class="text-[10px] text-indigo-600 hover:text-indigo-500">Forgot password?</a>
              </div>
              <div class="mt-1 relative rounded-md shadow-sm">
                <input 
                  id="password" 
                  name="password" 
                  :type="showPassword ? 'text' : 'password'" 
                  required 
                  v-model="form.password"
                  class="appearance-none block w-full px-3 py-2 border border-slate-200 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 text-sm bg-transparent pr-10 text-slate-900"
                  placeholder="••••••••"
                />
                <button 
                  type="button" 
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                >
                  <!-- Eye Open / Closed Icons -->
                  <Eye v-if="!showPassword" class="w-4 h-4" />
                  <EyeOff v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Remember me -->
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <input 
                  id="remember-me" 
                  name="remember-me" 
                  type="checkbox" 
                  v-model="form.remember"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-200 rounded"
                />
                <label for="remember-me" class="ml-2 block text-xs text-slate-600">Remember me</label>
              </div>
            </div>

            <!-- Error message -->
            <div v-if="error" class="bg-rose-50 text-rose-500 border border-rose-100 p-3 rounded-lg text-xs font-medium">
              {{ error }}
            </div>

            <!-- Submit Button -->
            <div>
              <button 
                type="submit" 
                :disabled="loading"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer"
              >
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ loading ? 'Signing in...' : 'Sign in' }}</span>
              </button>
            </div>
          </form>

          <!-- Quick access hint for testing -->
          <div class="mt-6 border-t border-slate-100 pt-4 text-center">
            <p class="text-[10px] text-slate-400">Quick Access (Admin seeder):</p>
            <p class="text-[10px] font-mono text-slate-500 mt-1">Username: <span class="font-bold text-indigo-500">admin</span> / Password: <span class="font-bold text-indigo-500">password</span></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right panel: Light-mode branded illustration -->
    <div class="hidden lg:flex flex-1 relative bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 overflow-hidden items-center justify-center">
      <!-- Decorative circles -->
      <div class="absolute -top-20 -right-20 w-96 h-96 bg-white/5 rounded-full"></div>
      <div class="absolute -bottom-32 -left-16 w-80 h-80 bg-white/5 rounded-full"></div>

      <div class="relative w-full max-w-lg text-center z-10 px-8">
        <div class="inline-flex items-center space-x-2 bg-white/15 backdrop-blur-sm px-4 py-2 rounded-full mb-6">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span class="text-xs font-semibold text-white">Live Financial Dashboard</span>
        </div>

        <h1 class="text-3xl font-extrabold text-white tracking-tight sm:text-4xl leading-tight">Simplify Project Financials</h1>
        <p class="mt-4 text-sm text-indigo-200 leading-relaxed">
          Track income, approve expenses, monitor budget utilization, and trace profit margins in real-time.
        </p>

        <!-- Light-mode card mockup -->
        <div class="mt-10 bg-white rounded-2xl shadow-2xl p-6 text-left">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <div class="flex items-center space-x-2">
              <div class="w-6 h-6 rounded-md bg-indigo-600 flex items-center justify-center">
                <span class="text-white text-[10px] font-bold">৳</span>
              </div>
              <span class="text-xs font-bold text-slate-700">PRJ-2026-0001</span>
            </div>
            <span class="text-[10px] bg-emerald-50 text-emerald-600 border border-emerald-100 font-semibold px-2 py-0.5 rounded-full">Running</span>
          </div>

          <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-3">
              <span class="text-[9px] uppercase font-bold text-slate-400 block">Budget</span>
              <p class="text-sm font-bold text-slate-800 mt-1">৳125k</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 rounded-xl p-3">
              <span class="text-[9px] uppercase font-bold text-slate-400 block">Expenses</span>
              <p class="text-sm font-bold text-rose-600 mt-1">৳8,500</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3">
              <span class="text-[9px] uppercase font-bold text-slate-400 block">Income</span>
              <p class="text-sm font-bold text-emerald-600 mt-1">৳70k</p>
            </div>
          </div>

          <div>
            <div class="flex justify-between text-[10px] text-slate-500 mb-1.5">
              <span class="font-semibold">Budget Utilized</span>
              <span>45% · ৳56,250 of ৳125,000</span>
            </div>
            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
              <div class="w-[45%] h-full bg-indigo-500 rounded-full"></div>
            </div>
          </div>

          <div class="mt-4 pt-3 border-t border-slate-100 grid grid-cols-2 gap-2 text-[10px]">
            <div class="flex items-center space-x-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
              <span class="text-slate-500">Net Profit: <span class="font-bold text-slate-700">৳61,500</span></span>
            </div>
            <div class="flex items-center space-x-1.5">
              <span class="w-2 h-2 rounded-full bg-indigo-400"></span>
              <span class="text-slate-500">Margin: <span class="font-bold text-indigo-600">87.8%</span></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { Eye, EyeOff } from 'lucide-vue-next';

const authStore = useAuthStore();
const router = useRouter();

const showPassword = ref(false);
const loading = ref(false);
const error = ref(null);

const form = reactive({
  username: '',
  password: '',
  remember: false
});

async function handleSubmit() {
  loading.value = true;
  error.value = null;
  
  try {
    await authStore.login(form.username, form.password);
    router.push({ name: 'dashboard' });
  } catch (err) {
    error.value = err.message || 'Validation error. Please verify credentials.';
  } finally {
    loading.value = false;
  }
}
</script>
