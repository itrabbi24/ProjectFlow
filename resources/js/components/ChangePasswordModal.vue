<template>
  <teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="close"></div>

      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div 
          class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-100 z-10"
        >
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
          <div class="flex items-center space-x-2.5">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
              <KeyRound class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-800" id="modal-title">Change Password</h3>
              <p class="text-[11px] text-slate-400">Update your account password</p>
            </div>
          </div>
          <button 
            @click="close" 
            class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
          <div v-if="errorMessage" class="p-3 text-xs bg-rose-50 border border-rose-100 text-rose-600 rounded-xl flex items-start space-x-2">
            <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
            <span>{{ errorMessage }}</span>
          </div>

          <div v-if="successMessage" class="p-3 text-xs bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-xl flex items-start space-x-2">
            <CheckCircle2 class="w-4 h-4 shrink-0 mt-0.5" />
            <span>{{ successMessage }}</span>
          </div>

          <!-- Current Password -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Current Password</label>
            <div class="relative">
              <input 
                :type="showCurrent ? 'text' : 'password'" 
                v-model="form.current_password"
                required
                placeholder="Enter current password"
                class="w-full pl-3 pr-10 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition"
              />
              <button 
                type="button" 
                @click="showCurrent = !showCurrent" 
                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
              >
                <EyeOff v-if="showCurrent" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
            <div class="relative">
              <input 
                :type="showNew ? 'text' : 'password'" 
                v-model="form.password"
                required
                minlength="6"
                placeholder="At least 6 characters"
                class="w-full pl-3 pr-10 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition"
              />
              <button 
                type="button" 
                @click="showNew = !showNew" 
                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
              >
                <EyeOff v-if="showNew" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Confirm New Password -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
            <div class="relative">
              <input 
                :type="showConfirm ? 'text' : 'password'" 
                v-model="form.password_confirmation"
                required
                minlength="6"
                placeholder="Confirm your new password"
                class="w-full pl-3 pr-10 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 transition"
              />
              <button 
                type="button" 
                @click="showConfirm = !showConfirm" 
                class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600"
              >
                <EyeOff v-if="showConfirm" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Footer Buttons -->
          <div class="pt-3 flex items-center justify-end space-x-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="close"
              class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="loading"
              class="inline-flex items-center space-x-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-semibold rounded-xl shadow-sm shadow-indigo-200 transition"
            >
              <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
              <span>{{ loading ? 'Updating...' : 'Update Password' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</teleport>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { KeyRound, X, Eye, EyeOff, AlertCircle, CheckCircle2, Loader2 } from 'lucide-vue-next';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close']);

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
});

function resetForm() {
  form.current_password = '';
  form.password = '';
  form.password_confirmation = '';
  errorMessage.value = '';
  successMessage.value = '';
}

function close() {
  resetForm();
  emit('close');
}

async function handleSubmit() {
  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'New passwords do not match.';
    return;
  }

  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const res = await axios.post('/change-password', form);
    successMessage.value = res.data.message || 'Password changed successfully!';
    setTimeout(() => {
      close();
    }, 1500);
  } catch (err) {
    if (err.response?.data?.errors) {
      const firstKey = Object.keys(err.response.data.errors)[0];
      errorMessage.value = err.response.data.errors[firstKey][0];
    } else {
      errorMessage.value = err.response?.data?.message || 'Failed to update password. Please check your inputs.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
