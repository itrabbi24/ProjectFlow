<template>
  <div class="space-y-6 max-w-4xl pb-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Categories</h1>
        <p class="text-xs text-slate-500 mt-1">Manage the income and expense categories used across the system.</p>
      </div>
    </div>

    <!-- Add new category -->
    <div v-if="authStore.hasPermission('edit_categories')" class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-4">
      <h2 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-50 pb-2">Add New Category</h2>

      <form @submit.prevent="addCategory" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[160px]">
          <label class="block text-xs font-semibold text-slate-700">Category Name</label>
          <input
            type="text"
            required
            v-model="categoryForm.name"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-transparent text-slate-800 focus:outline-none"
            placeholder="e.g. Software License"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700">Applies To</label>
          <select
            v-model="categoryForm.type"
            class="mt-1 block w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-800 focus:outline-none"
          >
            <option value="income">Income</option>
            <option value="expense">Expense</option>
          </select>
        </div>
        <button
          type="submit"
          :disabled="addingCategory"
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition disabled:opacity-50 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Add</span>
        </button>
      </form>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center space-x-1 border-b border-slate-100">
      <button
        v-for="tab in ['all', 'income', 'expense']"
        :key="tab"
        @click="activeType = tab"
        :class="[
          'px-4 py-2.5 text-xs font-semibold border-b-2 transition cursor-pointer capitalize',
          activeType === tab ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'
        ]"
      >
        {{ tab === 'all' ? 'All Categories' : tab }}
      </button>
    </div>

    <!-- Category list -->
    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
      <div v-if="loading" class="p-6 space-y-4">
        <div class="h-10 w-full bg-slate-100 rounded animate-pulse" v-for="i in 4" :key="i"></div>
      </div>
      <table v-else class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
            <th class="p-4">Name</th>
            <th class="p-4">Applies To</th>
            <th class="p-4">Status</th>
            <th v-if="authStore.hasPermission('edit_categories')" class="p-4 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
          <tr v-for="cat in filteredCategories" :key="cat.id" class="hover:bg-slate-50/50 transition">
            <td class="p-4 font-semibold text-slate-800">
              <input
                v-if="editingCategoryId === cat.id"
                v-model="categoryEditForm.name"
                class="w-full px-2 py-1 border border-slate-200 rounded text-xs focus:outline-none"
              />
              <span v-else>{{ cat.name }}</span>
            </td>
            <td class="p-4">
              <select
                v-if="editingCategoryId === cat.id"
                v-model="categoryEditForm.type"
                class="px-2 py-1 border border-slate-200 rounded text-xs bg-white focus:outline-none"
              >
                <option value="income">Income</option>
                <option value="expense">Expense</option>
              </select>
              <span
                v-else
                :class="[
                  'px-2 py-0.5 rounded text-[10px] capitalize',
                  cat.type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'
                ]"
              >{{ cat.type }}</span>
            </td>
            <td class="p-4">
              <select
                v-if="editingCategoryId === cat.id"
                v-model="categoryEditForm.status"
                class="px-2 py-1 border border-slate-200 rounded text-xs bg-white focus:outline-none"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
              <span
                v-else
                :class="[
                  'px-2 py-0.5 rounded-full text-[10px] font-medium border',
                  cat.status === 'active' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-50 text-slate-500 border-slate-200'
                ]"
              >
                {{ cat.status }}
              </span>
            </td>
            <td v-if="authStore.hasPermission('edit_categories')" class="p-4 text-center">
              <div class="flex items-center justify-center space-x-1.5">
                <template v-if="editingCategoryId === cat.id">
                  <button type="button" @click="saveCategoryEdit(cat.id)" class="p-1 rounded text-emerald-500 hover:bg-slate-50 cursor-pointer" title="Save">
                    <Check class="w-4 h-4" />
                  </button>
                  <button type="button" @click="cancelCategoryEdit" class="p-1 rounded text-slate-400 hover:bg-slate-50 cursor-pointer" title="Cancel">
                    <X class="w-4 h-4" />
                  </button>
                </template>
                <template v-else>
                  <button type="button" @click="startCategoryEdit(cat)" class="p-1 rounded text-slate-400 hover:text-indigo-500 hover:bg-slate-50 cursor-pointer" title="Edit">
                    <Edit class="w-4 h-4" />
                  </button>
                  <button type="button" @click="deleteCategory(cat.id)" class="p-1 rounded text-slate-400 hover:text-rose-500 hover:bg-slate-50 cursor-pointer" title="Delete">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </template>
              </div>
            </td>
          </tr>
          <tr v-if="filteredCategories.length === 0">
            <td :colspan="authStore.hasPermission('edit_categories') ? 4 : 3" class="p-8 text-center text-slate-400">No categories found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { Plus, Edit, Trash2, Check, X } from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';
import { toast } from 'vue-sonner';

const authStore = useAuthStore();

const loading = ref(true);
const categoriesList = ref([]);
const activeType = ref('all');

const addingCategory = ref(false);
const categoryForm = reactive({ name: '', type: 'expense' });

const editingCategoryId = ref(null);
const categoryEditForm = reactive({ name: '', type: 'expense', status: 'active' });

const filteredCategories = computed(() => {
  if (activeType.value === 'all') return categoriesList.value;
  return categoriesList.value.filter(c => c.type === activeType.value);
});

async function fetchCategories() {
  loading.value = true;
  try {
    const response = await axios.get('/categories');
    categoriesList.value = response.data.data || [];
  } catch (error) {
    console.error(error);
  } finally {
    loading.value = false;
  }
}

async function addCategory() {
  addingCategory.value = true;
  try {
    await axios.post('/categories', categoryForm);
    toast.success('Category added successfully.');
    categoryForm.name = '';
    fetchCategories();
  } catch (error) {
    console.error(error);
    toast.error(error.response?.data?.message || 'Failed to add category.');
  } finally {
    addingCategory.value = false;
  }
}

function startCategoryEdit(cat) {
  editingCategoryId.value = cat.id;
  categoryEditForm.name = cat.name;
  categoryEditForm.type = cat.type;
  categoryEditForm.status = cat.status;
}

function cancelCategoryEdit() {
  editingCategoryId.value = null;
}

async function saveCategoryEdit(id) {
  try {
    await axios.put(`/categories/${id}`, categoryEditForm);
    toast.success('Category updated successfully.');
    editingCategoryId.value = null;
    fetchCategories();
  } catch (error) {
    console.error(error);
    toast.error(error.response?.data?.message || 'Failed to update category.');
  }
}

async function deleteCategory(id) {
  const result = await Swal.fire({
    title: 'Are you sure?',
    text: 'This category will be removed from the selection lists.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete category'
  });

  if (result.isConfirmed) {
    try {
      await axios.delete(`/categories/${id}`);
      toast.success('Category deleted.');
      fetchCategories();
    } catch (error) {
      console.error(error);
      toast.error('Failed to delete category.');
    }
  }
}

onMounted(() => {
  fetchCategories();
});
</script>
