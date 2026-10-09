<template>
  <sc-layout title="Delivery Monitoring">
    <div class="space-y-6">
      <!-- Unauthenticated State -->
      <div v-if="!token" class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
        <h2 class="text-lg font-bold text-amber-950">Authentication required</h2>
        <p class="mt-2 text-sm text-amber-800">Log in with an approved sorting center account to monitor parcels.</p>
        <a href="/login" class="mt-5 inline-flex rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] shadow-sm">
          Go to login
        </a>
      </div>

      <template v-else>
        <!-- Feedback Banner -->
        <div
          v-if="feedback.message"
          :class="feedback.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'"
          class="flex items-center justify-between rounded-lg border px-5 py-3 text-sm shadow-sm"
          role="status"
        >
          <span>{{ feedback.message }}</span>
          <button type="button" class="ml-4 font-bold hover:opacity-75" @click="feedback.message = ''">Close</button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="flex flex-col gap-4 rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-semibold text-gray-600">Tracked Parcels in Workflow</p>
            <div class="mt-1 flex items-baseline gap-2">
              <span class="text-3xl font-bold text-[#008B8B]">{{ pagination.total }}</span>
              <span class="text-xs text-gray-500 font-medium">total matching filters</span>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
              <label for="status-filter" class="text-sm font-semibold text-gray-700">Status:</label>
              <select
                id="status-filter"
                v-model="selectedStatus"
                class="rounded-lg border border-[#DCE7E2] bg-white px-3 py-2 text-sm text-[#333333] outline-none transition focus:border-[#008B8B] focus:ring-2 focus:ring-[#008B8B]/20"
                @change="loadParcels(1)"
              >
                <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </div>
            <button
              type="button"
              :disabled="loading"
              class="rounded-lg bg-[#FFA500] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:opacity-50 shadow-sm active:scale-95"
              @click="loadParcels(pagination.current_page)"
            >
              {{ loading ? 'Refreshing...' : 'Refresh' }}
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="rounded-xl border border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-[#008B8B]"></div>
          <p class="mt-4 text-sm text-gray-500">Loading monitoring data...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="parcels.length === 0" class="rounded-xl border border-dashed border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-600">
            None
          </div>
          <h2 class="mt-4 text-lg font-bold text-[#008B8B]">No Parcels Match This Filter</h2>
          <p class="mt-2 text-sm text-gray-500">Try choosing another status filter or check back later.</p>
        </div>

        <!-- Parcels Table Card -->
        <div v-else class="overflow-hidden rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-[#DCE7E2] text-left text-sm">
              <thead class="bg-[#008B8B] text-xs font-semibold uppercase tracking-wider text-white">
                <tr>
                  <th class="px-5 py-3.5">Order ID</th>
                  <th class="px-5 py-3.5">Status</th>
                  <th class="px-5 py-3.5">Delivery Destination</th>
                  <th class="px-5 py-3.5">Sorting Area</th>
                  <th class="px-5 py-3.5">Assigned Rider</th>
                  <th class="px-5 py-3.5">Last Updated</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#DCE7E2] bg-white">
                <tr
                  v-for="(parcel, index) in parcels"
                  :key="parcel.id"
                  :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/70'"
                  class="transition hover:bg-teal-50/40"
                >
                  <td class="whitespace-nowrap px-5 py-4 font-mono font-bold text-[#008B8B]">{{ parcel.order_id }}</td>
                  <td class="whitespace-nowrap px-5 py-4">
                    <span :class="statusBadgeClass(parcel.status)" class="rounded-full px-3 py-1 text-xs font-bold shadow-xs">
                      {{ formatStatus(parcel.status) }}
                    </span>
                  </td>
                  <td class="px-5 py-4 text-gray-700 font-medium">
                    {{ parcel.delivery_municipality }} / {{ parcel.delivery_barangay }}
                  </td>
                  <td class="whitespace-nowrap px-5 py-4 text-gray-700">
                    <span v-if="parcel.sorted_area" class="font-semibold text-[#008B8B]">{{ parcel.sorted_area.name }}</span>
                    <span v-else class="text-gray-400 italic">Not sorted</span>
                  </td>
                  <td class="whitespace-nowrap px-5 py-4 text-gray-700">
                    <span v-if="parcel.assigned_rider" class="font-semibold text-gray-900">{{ parcel.assigned_rider.name }}</span>
                    <span v-else class="text-gray-400 italic">Unassigned</span>
                  </td>
                  <td class="whitespace-nowrap px-5 py-4 text-xs text-gray-500 font-medium">{{ formatDate(parcel.updated_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Bar -->
          <div class="flex flex-col sm:flex-row items-center justify-between border-t border-[#DCE7E2] px-6 py-4 text-sm bg-gray-50">
            <p class="text-gray-600 font-medium">Page {{ pagination.current_page }} of {{ pagination.last_page }}</p>
            <div class="mt-2 sm:mt-0 flex gap-2">
              <button
                type="button"
                :disabled="!pagination.prev_page_url || loading"
                class="rounded-lg border border-[#DCE7E2] bg-white px-4 py-2 font-semibold text-gray-700 hover:border-[#008B8B] hover:text-[#008B8B] disabled:cursor-not-allowed disabled:opacity-40 transition"
                @click="loadParcels(pagination.current_page - 1)"
              >
                Previous
              </button>
              <button
                type="button"
                :disabled="!pagination.next_page_url || loading"
                class="rounded-lg border border-[#DCE7E2] bg-white px-4 py-2 font-semibold text-gray-700 hover:border-[#008B8B] hover:text-[#008B8B] disabled:cursor-not-allowed disabled:opacity-40 transition"
                @click="loadParcels(pagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </div>
        </div>
      </template>
    </div>
  </sc-layout>
</template>

<script>
import { onMounted, reactive, ref } from 'vue';
import ScLayout from './ScLayout.vue';

export default {
  name: 'DeliveryMonitoring',
  components: {
    ScLayout,
  },
  setup() {
    const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'));
    const parcels = ref([]);
    const selectedStatus = ref('');
    const loading = ref(false);
    const statusOptions = [
      { value: '', label: 'All monitorable parcels' },
      { value: 'PLACED', label: 'Placed' },
      { value: 'PREPARING', label: 'Preparing' },
      { value: 'PICKED_UP', label: 'Picked up' },
      { value: 'AT_SORTING_CENTER', label: 'At sorting center' },
      { value: 'OUT_FOR_DELIVERY', label: 'Out for delivery' },
      { value: 'DELIVERED', label: 'Delivered' },
      { value: 'FAILED_RETURNED', label: 'Failed / returned' },
    ];
    const pagination = reactive({ current_page: 1, last_page: 1, total: 0, prev_page_url: null, next_page_url: null });
    const feedback = reactive({ message: '', type: 'success' });

    async function loadParcels(page = 1) {
      loading.value = true;
      const params = new URLSearchParams({ page: String(page) });
      if (selectedStatus.value) params.set('status', selectedStatus.value);

      try {
        const response = await fetch(`/api/sorting-center/parcels/monitoring?${params}`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${token.value}` },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Monitoring data could not be loaded.');
        parcels.value = data.data.data || [];
        Object.assign(pagination, {
          current_page: data.data.current_page,
          last_page: data.data.last_page,
          total: data.data.total,
          prev_page_url: data.data.prev_page_url,
          next_page_url: data.data.next_page_url,
        });
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loading.value = false;
      }
    }

    function formatStatus(status) {
      return status ? status.replaceAll('_', ' ') : 'UNKNOWN';
    }

    function statusBadgeClass(status) {
      return {
        AT_SORTING_CENTER: 'bg-teal-100 text-[#008B8B]',
        OUT_FOR_DELIVERY: 'bg-orange-100 text-[#FFA500]',
        DELIVERED: 'bg-green-100 text-green-800',
        FAILED_RETURNED: 'bg-red-100 text-red-800',
        PICKED_UP: 'bg-blue-100 text-blue-800',
      }[status] || 'bg-gray-100 text-gray-700';
    }

    function formatDate(value) {
      return value ? new Date(value).toLocaleString() : 'Unknown';
    }

    onMounted(() => loadParcels());

    return {
      token,
      parcels,
      selectedStatus,
      loading,
      statusOptions,
      pagination,
      feedback,
      loadParcels,
      formatStatus,
      statusBadgeClass,
      formatDate,
    };
  },
};
</script>
