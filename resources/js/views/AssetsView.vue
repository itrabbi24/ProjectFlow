<template>
  <div class="space-y-6 pb-12">
    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Fixed Assets Management</h1>
        <p class="text-xs text-slate-500 mt-1">Track physical office equipment, assignment, write-offs and asset valuation.</p>
      </div>

      <div class="flex items-center space-x-2">
        <button
          @click="activeTab = activeTab === 'list' ? 'report' : 'list'"
          class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition cursor-pointer"
        >
          <BarChart3 class="w-4 h-4 text-slate-500" />
          <span>{{ activeTab === 'list' ? 'Asset Valuation Report' : 'Assets Directory' }}</span>
        </button>

        <button
          @click="openCreateModal"
          class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-sm cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>New Asset Entry</span>
        </button>
      </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Active Assets</span>
        <h2 class="text-xl font-bold text-slate-800 mt-1">{{ summary.active_assets || 0 }}</h2>
        <p class="text-[10px] text-slate-400 mt-1">Total in directory: {{ summary.total_assets || 0 }}</p>
      </div>

      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Original Purchase Cost</span>
        <h2 class="text-xl font-bold text-slate-800 mt-1">{{ sym }}{{ (summary.total_purchase_cost || 0).toLocaleString() }}</h2>
        <p class="text-[10px] text-slate-400 mt-1">Gross investment</p>
      </div>

      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Current Active Value</span>
        <h2 class="text-xl font-bold text-emerald-600 mt-1">{{ sym }}{{ (summary.active_current_value || 0).toLocaleString() }}</h2>
        <p class="text-[10px] text-slate-400 mt-1">Present asset valuation</p>
      </div>

      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm">
        <span class="text-[10px] uppercase font-bold text-rose-500 tracking-wider">Write-Off / Disposal Loss</span>
        <h2 class="text-xl font-bold text-rose-600 mt-1">{{ sym }}{{ (summary.disposal_loss || 0).toLocaleString() }}</h2>
        <div class="flex items-center space-x-1.5 text-[10px] text-slate-400 mt-1">
          <span>Scrap Recovered:</span>
          <span class="font-semibold text-emerald-600">{{ sym }}{{ (summary.total_scrap_recovered || 0).toLocaleString() }}</span>
        </div>
      </div>
    </div>

    <!-- MAIN VIEW: ASSET DIRECTORY -->
    <div v-if="activeTab === 'list'" class="space-y-4">
      <!-- Search & Filters -->
      <div class="bg-white border border-slate-100 p-4 rounded-2xl shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="relative w-full sm:w-72">
          <Search class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
          <input
            type="text"
            v-model="searchQuery"
            @input="debounceSearch"
            placeholder="Search name, code, serial number..."
            class="pl-9 pr-4 py-1.5 w-full rounded-lg border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-indigo-500"
          />
        </div>

        <div class="flex items-center space-x-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
          <select
            v-model="filterStatus"
            @change="fetchAssets"
            class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 bg-white focus:outline-none focus:border-indigo-500"
          >
            <option value="">All Statuses</option>
            <option value="in_use">In Use</option>
            <option value="in_stock">In Stock</option>
            <option value="maintenance">Under Maintenance</option>
            <option value="disposed">Disposed / Written-Off</option>
          </select>

          <select
            v-model="filterCategory"
            @change="fetchAssets"
            class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 bg-white focus:outline-none focus:border-indigo-500"
          >
            <option value="">All Categories</option>
            <option value="IT Equipment">IT Equipment</option>
            <option value="Office Furniture">Office Furniture</option>
            <option value="Machinery">Machinery</option>
            <option value="Vehicles">Vehicles</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>

      <!-- Assets Table -->
      <div class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 space-y-3">
          <div class="h-10 bg-slate-100 rounded animate-pulse" v-for="i in 5" :key="i"></div>
        </div>

        <div v-else-if="assets.length === 0" class="p-12 text-center text-slate-400 text-xs">
          <Boxes class="w-12 h-12 mx-auto text-slate-300 mb-2" />
          <p class="font-medium text-slate-600">No assets recorded yet</p>
          <p class="text-[11px] mt-0.5">Click "New Asset Entry" above to add fixed assets or equipment.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-slate-50/70 text-slate-400 font-semibold border-b border-slate-100">
                <th class="p-3.5">Asset Code &amp; Name</th>
                <th class="p-3.5">Category</th>
                <th class="p-3.5">Purchase Cost</th>
                <th class="p-3.5">Current Value</th>
                <th class="p-3.5">Assigned To</th>
                <th class="p-3.5">Status</th>
                <th class="p-3.5 text-center">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
              <tr v-for="asset in assets" :key="asset.id" class="hover:bg-slate-50/50 transition">
                <td class="p-3.5">
                  <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                      <Boxes class="w-4 h-4" />
                    </div>
                    <div>
                      <span class="font-bold text-slate-800 block">{{ asset.name }}</span>
                      <span class="text-[10px] font-mono text-indigo-600">{{ asset.asset_code }}</span>
                      <span v-if="asset.serial_number" class="text-[10px] text-slate-400 ml-1.5 font-mono">SN: {{ asset.serial_number }}</span>
                    </div>
                  </div>
                </td>
                <td class="p-3.5">
                  <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700">
                    {{ asset.category }}
                  </span>
                </td>
                <td class="p-3.5 font-semibold text-slate-800">
                  {{ sym }}{{ Number(asset.purchase_cost).toLocaleString() }}
                  <span class="block text-[10px] text-slate-400 font-normal">{{ formatDate(asset.purchase_date) }}</span>
                </td>
                <td class="p-3.5 font-bold">
                  <span :class="asset.status === 'disposed' ? 'text-slate-400 line-through' : 'text-emerald-600'">
                    {{ sym }}{{ Number(asset.current_value || 0).toLocaleString() }}
                  </span>
                  <span v-if="asset.status === 'disposed' && asset.scrap_value > 0" class="block text-[10px] text-emerald-600 font-medium">
                    Scrap: {{ sym }}{{ Number(asset.scrap_value).toLocaleString() }}
                  </span>
                </td>
                <td class="p-3.5">
                  <div v-if="asset.assigned_user" class="flex items-center space-x-1.5">
                    <span class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center text-[10px] font-bold text-slate-700">
                      {{ asset.assigned_user.name.charAt(0) }}
                    </span>
                    <span class="text-slate-700 font-medium">{{ asset.assigned_user.name }}</span>
                  </div>
                  <span v-else-if="asset.project" class="text-slate-600">
                    Project: <span class="font-medium text-slate-800">{{ asset.project.name }}</span>
                  </span>
                  <span v-else class="text-slate-400 italic">Unassigned</span>
                </td>
                <td class="p-3.5">
                  <span
                    :class="[
                      'inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider border',
                      getStatusBadgeClass(asset.status)
                    ]"
                  >
                    {{ formatStatus(asset.status) }}
                  </span>
                  <span v-if="asset.status === 'disposed'" class="block text-[10px] text-rose-500 mt-0.5">
                    Reason: {{ asset.disposal_reason || 'N/A' }}
                  </span>
                </td>
                <td class="p-3.5 text-center">
                  <div class="flex items-center justify-center space-x-1.5">
                    <button
                      @click="openEditModal(asset)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-100 transition cursor-pointer"
                      title="Edit Asset Details"
                    >
                      <Pencil class="w-3.5 h-3.5" />
                    </button>

                    <button
                      v-if="asset.status !== 'disposed'"
                      @click="openDisposeModal(asset)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition cursor-pointer"
                      title="Write-off / Dispose Broken Asset"
                    >
                      <AlertTriangle class="w-3.5 h-3.5" />
                    </button>

                    <button
                      @click="deleteAsset(asset)"
                      class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                      title="Delete Asset"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- VALUATION & WRITE-OFF REPORT VIEW -->
    <div v-else class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Category Breakdown -->
        <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
          <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center space-x-2">
            <Boxes class="w-4 h-4 text-indigo-600" />
            <span>Valuation by Asset Category</span>
          </h3>
          <div class="space-y-3">
            <div v-for="cat in summary.category_breakdown" :key="cat.category" class="border-b border-slate-50 pb-2">
              <div class="flex justify-between text-xs mb-1">
                <span class="font-medium text-slate-700">{{ cat.category }} ({{ cat.count }} items)</span>
                <span class="font-bold text-slate-800">{{ sym }}{{ Number(cat.total_value || 0).toLocaleString() }}</span>
              </div>
              <div class="w-full bg-slate-100 rounded-full h-1.5">
                <div 
                  class="bg-indigo-600 h-1.5 rounded-full" 
                  :style="{ width: `${Math.min(100, (cat.total_value / (summary.active_current_value || 1)) * 100)}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Disposed / Broken Assets History -->
        <div class="bg-white border border-slate-100 p-5 rounded-2xl shadow-sm">
          <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center space-x-2">
            <AlertTriangle class="w-4 h-4 text-rose-500" />
            <span>Write-off / Disposed Assets History</span>
          </h3>
          
          <div v-if="!summary.disposed_list || summary.disposed_list.length === 0" class="text-center py-8 text-xs text-slate-400">
            No assets have been written off or damaged.
          </div>

          <div v-else class="space-y-3 max-h-72 overflow-y-auto divide-y divide-slate-50">
            <div v-for="disp in summary.disposed_list" :key="disp.id" class="pt-2 text-xs">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800">{{ disp.name }}</span>
                <span class="text-rose-500 font-semibold uppercase text-[10px]">{{ disp.disposal_reason }}</span>
              </div>
              <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                <span>Original: {{ sym }}{{ Number(disp.purchase_cost).toLocaleString() }}</span>
                <span>Scrap: <strong class="text-emerald-600">{{ sym }}{{ Number(disp.scrap_value).toLocaleString() }}</strong></span>
                <span>Date: {{ formatDate(disp.disposal_date) }}</span>
              </div>
              <p v-if="disp.disposal_notes" class="text-[10px] text-slate-500 italic mt-0.5">
                Note: {{ disp.disposal_notes }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ENTRY / EDIT ASSET MODAL -->
    <div v-if="formModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-base font-bold text-slate-800">
            {{ isEditing ? 'Edit Fixed Asset' : 'New Fixed Asset Entry' }}
          </h3>
          <button @click="formModalOpen = false" class="text-slate-400 hover:text-slate-600">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveAsset" class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
              <label class="block font-semibold text-slate-700 mb-1">Asset Name *</label>
              <input
                type="text"
                v-model="formData.name"
                required
                placeholder="e.g. Ergonomic Office Chair / MacBook Pro 14"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500"
              />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="block font-semibold text-slate-700">Asset Code</label>
                <span v-if="!isEditing" class="text-[10px] text-indigo-600 font-semibold bg-indigo-50 px-1.5 py-0.5 rounded">Auto Generated (AST-{{ new Date().getFullYear() }}-0001)</span>
              </div>
              <input
                type="text"
                v-model="formData.asset_code"
                :placeholder="isEditing ? 'Asset Code' : 'Auto Generated if left blank'"
                :readonly="isEditing"
                :class="[
                  'w-full px-3 py-2 border rounded-lg font-mono text-xs focus:outline-none',
                  isEditing ? 'bg-slate-50 border-slate-200 text-slate-500 cursor-not-allowed' : 'border-slate-200 focus:border-indigo-500'
                ]"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Category *</label>
              <select
                v-model="formData.category"
                required
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 bg-white"
              >
                <option value="IT Equipment">IT Equipment</option>
                <option value="Office Furniture">Office Furniture</option>
                <option value="Machinery">Machinery</option>
                <option value="Vehicles">Vehicles</option>
                <option value="Electronics">Electronics</option>
                <option value="General">General / Other</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Purchase Date *</label>
              <input
                type="date"
                v-model="formData.purchase_date"
                required
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Purchase Cost ({{ sym }}) *</label>
              <input
                type="number"
                step="0.01"
                min="0"
                v-model.number="formData.purchase_cost"
                required
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Current Value ({{ sym }})</label>
              <input
                type="number"
                step="0.01"
                min="0"
                v-model.number="formData.current_value"
                placeholder="Defaults to purchase cost"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Status *</label>
              <select
                v-model="formData.status"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 bg-white"
              >
                <option value="in_use">In Use</option>
                <option value="in_stock">In Stock</option>
                <option value="maintenance">Under Maintenance</option>
                <option value="disposed">Disposed / Written Off</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Assign to Project</label>
              <select
                v-model="formData.project_id"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 bg-white"
              >
                <option :value="null">None (General Office Asset)</option>
                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Assign to User / Employee</label>
              <select
                v-model="formData.assigned_to"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 bg-white"
              >
                <option :value="null">Unassigned</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} (@{{ u.username }})</option>
              </select>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Serial Number</label>
              <input
                type="text"
                v-model="formData.serial_number"
                placeholder="Serial / IMEI / Tag"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 font-mono"
              />
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Physical Location</label>
              <input
                type="text"
                v-model="formData.location"
                placeholder="e.g. Floor 2, Server Room, Desk 4"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500"
              />
            </div>

            <div class="col-span-2">
              <label class="block font-semibold text-slate-700 mb-1">Notes &amp; Warranty info</label>
              <textarea
                v-model="formData.notes"
                rows="2"
                placeholder="Additional remarks..."
                class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500"
              ></textarea>
            </div>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="formModalOpen = false"
              class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold cursor-pointer disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : (isEditing ? 'Update Asset' : 'Save Asset') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- DISPOSE / WRITE-OFF MODAL -->
    <div v-if="disposeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center space-x-3 text-rose-600 border-b border-slate-100 pb-3">
          <div class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-slate-800">Dispose / Write-Off Asset</h3>
            <p class="text-[11px] text-slate-500">Asset: {{ selectedAsset?.name }} ({{ selectedAsset?.asset_code }})</p>
          </div>
        </div>

        <form @submit.prevent="submitDisposal" class="space-y-3 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Reason for Disposal / Write-Off *</label>
            <select
              v-model="disposeData.disposal_reason"
              required
              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-rose-500 bg-white"
            >
              <option value="damaged">Damaged / Broken (নষ্ট হয়ে গেছে)</option>
              <option value="obsolete">Obsolete / Outdated (মেয়াদোত্তীর্ণ)</option>
              <option value="sold">Sold as Scrap / Used (ভাঙারি বা স্ক্র্যাপ বিক্রি)</option>
              <option value="lost">Lost / Stolen (হারিয়ে গেছে)</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Disposal Date *</label>
            <input
              type="date"
              v-model="disposeData.disposal_date"
              required
              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-rose-500"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Scrap / Sale Recovery Amount ({{ sym }})</label>
            <input
              type="number"
              step="0.01"
              min="0"
              v-model.number="disposeData.scrap_value"
              placeholder="0 if zero value write-off"
              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-rose-500"
            />
            <p class="text-[10px] text-slate-400 mt-1">If the broken asset was sold for scrap money, enter the amount here.</p>
          </div>

          <div v-if="disposeData.scrap_value > 0 && selectedAsset?.project_id" class="flex items-center space-x-2 pt-1">
            <input
              type="checkbox"
              id="record_income"
              v-model="disposeData.record_as_income"
              class="rounded text-indigo-600 focus:ring-indigo-500"
            />
            <label for="record_income" class="text-[11px] text-slate-700 font-medium">
              Automatically record this scrap sale into Project Incomes
            </label>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Disposal Notes</label>
            <textarea
              v-model="disposeData.disposal_notes"
              rows="2"
              placeholder="Details about damage or reason for write-off..."
              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-rose-500"
            ></textarea>
          </div>

          <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="disposeModalOpen = false"
              class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 font-semibold cursor-pointer disabled:opacity-50"
            >
              {{ saving ? 'Processing...' : 'Confirm Disposal / Write-Off' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { formatDate } from '@/utils/date';
import { useSettingStore } from '@/stores/settings';
import {
  Boxes,
  Plus,
  Search,
  Pencil,
  Trash2,
  AlertTriangle,
  BarChart3,
  X
} from 'lucide-vue-next';

const settingStore = useSettingStore();
const sym = computed(() => settingStore.settings.currency_symbol || '৳');

const activeTab = ref('list');
const loading = ref(false);
const saving = ref(false);

const assets = ref([]);
const projects = ref([]);
const users = ref([]);
const summary = ref({});

const searchQuery = ref('');
const filterStatus = ref('');
const filterCategory = ref('');

// Form modal state
const formModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const formData = ref({
  name: '',
  asset_code: '',
  category: 'IT Equipment',
  purchase_date: new Date().toISOString().split('T')[0],
  purchase_cost: 0,
  current_value: 0,
  status: 'in_use',
  project_id: null,
  assigned_to: null,
  serial_number: '',
  location: '',
  notes: ''
});

// Dispose modal state
const disposeModalOpen = ref(false);
const selectedAsset = ref(null);
const disposeData = ref({
  disposal_reason: 'damaged',
  disposal_date: new Date().toISOString().split('T')[0],
  scrap_value: 0,
  record_as_income: true,
  disposal_notes: ''
});

let debounceTimer = null;
function debounceSearch() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchAssets();
  }, 350);
}

async function fetchAssets() {
  loading.value = true;
  try {
    const params = {
      search: searchQuery.value,
      status: filterStatus.value,
      category: filterCategory.value
    };
    const response = await axios.get('/assets', { params });
    assets.value = response.data.data.data;
  } catch (err) {
    console.error('Failed to load assets:', err);
  } finally {
    loading.value = false;
  }
}

async function fetchSummary() {
  try {
    const response = await axios.get('/reports/assets');
    summary.value = response.data.data;
  } catch (err) {
    console.error('Failed to load asset report summary:', err);
  }
}

async function fetchDependencies() {
  try {
    const [projRes, userRes] = await Promise.all([
      axios.get('/projects?per_page=100'),
      axios.get('/users?per_page=100')
    ]);
    projects.value = projRes.data.data.data || [];
    users.value = userRes.data.data.data || [];
  } catch (err) {
    console.error('Failed to load projects/users for asset assignment:', err);
  }
}

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  formData.value = {
    name: '',
    asset_code: '',
    category: 'IT Equipment',
    purchase_date: new Date().toISOString().split('T')[0],
    purchase_cost: 0,
    current_value: 0,
    status: 'in_use',
    project_id: null,
    assigned_to: null,
    serial_number: '',
    location: '',
    notes: ''
  };
  formModalOpen.value = true;
}

function openEditModal(asset) {
  isEditing.value = true;
  editingId.value = asset.id;
  formData.value = {
    name: asset.name,
    asset_code: asset.asset_code,
    category: asset.category,
    purchase_date: asset.purchase_date,
    purchase_cost: asset.purchase_cost,
    current_value: asset.current_value,
    status: asset.status,
    project_id: asset.project_id,
    assigned_to: asset.assigned_to,
    serial_number: asset.serial_number || '',
    location: asset.location || '',
    notes: asset.notes || ''
  };
  formModalOpen.value = true;
}

async function saveAsset() {
  saving.value = true;
  try {
    if (isEditing.value) {
      await axios.put(`/assets/${editingId.value}`, formData.value);
    } else {
      await axios.post('/assets', formData.value);
    }
    formModalOpen.value = false;
    fetchAssets();
    fetchSummary();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to save asset');
  } finally {
    saving.value = false;
  }
}

function openDisposeModal(asset) {
  selectedAsset.value = asset;
  disposeData.value = {
    disposal_reason: 'damaged',
    disposal_date: new Date().toISOString().split('T')[0],
    scrap_value: 0,
    record_as_income: true,
    disposal_notes: ''
  };
  disposeModalOpen.value = true;
}

async function submitDisposal() {
  if (!selectedAsset.value) return;
  saving.value = true;
  try {
    await axios.post(`/assets/${selectedAsset.value.id}/dispose`, disposeData.value);
    disposeModalOpen.value = false;
    fetchAssets();
    fetchSummary();
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to dispose asset');
  } finally {
    saving.value = false;
  }
}

async function deleteAsset(asset) {
  if (!confirm(`Are you sure you want to delete "${asset.name}"?`)) return;
  try {
    await axios.delete(`/assets/${asset.id}`);
    fetchAssets();
    fetchSummary();
  } catch (err) {
    alert('Failed to delete asset');
  }
}

function formatStatus(status) {
  switch (status) {
    case 'in_use': return 'In Use';
    case 'in_stock': return 'In Stock';
    case 'maintenance': return 'Maintenance';
    case 'disposed': return 'Disposed / Write-Off';
    default: return status;
  }
}

function getStatusBadgeClass(status) {
  switch (status) {
    case 'in_use':
      return 'bg-emerald-50 text-emerald-600 border-emerald-100';
    case 'in_stock':
      return 'bg-blue-50 text-blue-600 border-blue-100';
    case 'maintenance':
      return 'bg-amber-50 text-amber-600 border-amber-100';
    case 'disposed':
      return 'bg-rose-50 text-rose-600 border-rose-100';
    default:
      return 'bg-slate-50 text-slate-600 border-slate-200';
  }
}

onMounted(() => {
  fetchAssets();
  fetchSummary();
  fetchDependencies();
});
</script>
