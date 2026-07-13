<template>
  <div class="space-y-6 max-w-4xl pb-8">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-slate-800">Settings</h1>
      <p class="text-xs text-slate-500 mt-1">Configure company profiles, currency symbols, and system themes.</p>
    </div>

    <!-- Forms Sections -->
    <div class="space-y-6">
      <!-- 1. Company Information -->
      <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-50 pb-2">Company Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Company Name</label>
            <input 
              type="text" 
              v-model="form.company_name"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="e.g. ProjectFlow Corp"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Company Email</label>
            <input 
              type="email" 
              v-model="form.company_email"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="finance@projectflow.com"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Company Phone</label>
            <input 
              type="text" 
              v-model="form.company_phone"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="+1 (555) 000-1111"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Billing Address</label>
            <input 
              type="text" 
              v-model="form.company_address"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="123 Corporate Ave, New York"
            />
          </div>
        </div>
      </div>

      <!-- 2. Financials & Formatting -->
      <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-4">
        <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-50 pb-2">Financials & Localizations</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700">Default Currency</label>
            <select 
              v-model="form.currency"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
            >
              <option value="USD">USD ($)</option>
              <option value="EUR">EUR (€)</option>
              <option value="GBP">GBP (£)</option>
              <option value="BDT">BDT (৳)</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Currency Symbol</label>
            <input 
              type="text" 
              v-model="form.currency_symbol"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
              placeholder="$"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700">Default Timezone</label>
            <select 
              v-model="form.timezone"
              class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
            >
              <option value="UTC">UTC</option>
              <option value="America/New_York">EST (New York)</option>
              <option value="Asia/Dhaka">GMT+6 (Dhaka)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="flex justify-end">
        <button
          v-if="authStore.hasPermission('edit_settings')"
          @click="saveSettings"
          :disabled="saving"
          class="flex items-center space-x-1.5 px-4 py-2.5 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition disabled:opacity-50 cursor-pointer shadow-sm shadow-indigo-100"
        >
          <Save class="w-4 h-4" />
          <span>{{ saving ? 'Saving configurations...' : 'Save Settings' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useSettingStore } from '@/stores/settings';
import { Save } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const authStore = useAuthStore();
const settingStore = useSettingStore();

const saving = ref(false);

const form = reactive({
  company_name: '',
  company_email: '',
  company_phone: '',
  company_address: '',
  currency: 'USD',
  currency_symbol: '$',
  timezone: 'UTC'
});

async function loadSettings() {
  await settingStore.fetchSettings();
  
  // Populate local form state
  form.company_name = settingStore.settings.company_name || '';
  form.company_email = settingStore.settings.company_email || '';
  form.company_phone = settingStore.settings.company_phone || '';
  form.company_address = settingStore.settings.company_address || '';
  form.currency = settingStore.settings.currency || 'USD';
  form.currency_symbol = settingStore.settings.currency_symbol || '$';
  form.timezone = settingStore.settings.timezone || 'UTC';
}

async function saveSettings() {
  saving.value = true;
  try {
    await settingStore.saveSettings(form);
    toast.success('System configuration updated successfully.');
  } catch (error) {
    console.error(error);
    toast.error('Failed to update configurations.');
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  loadSettings();
});
</script>
