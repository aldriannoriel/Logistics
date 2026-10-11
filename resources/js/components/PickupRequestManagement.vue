<template>
  <sc-layout title="Pickup Request Management">
    <div class="space-y-6">
      <!-- Unauthenticated State -->
      <div v-if="!token" class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
        <h2 class="text-lg font-bold text-amber-950">Authentication required</h2>
        <p class="mt-2 text-sm text-amber-800">Log in with an approved sorting center account to manage pickup requests.</p>
        <a href="/login" class="mt-5 inline-flex rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] shadow-sm">
          Go to login
        </a>
      </div>

      <template v-else>
        <!-- Top Status & Actions Card -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
          <div>
            <p class="text-sm font-semibold text-gray-600">Pending Pickup Requests</p>
            <div class="mt-1 flex items-baseline gap-2">
              <span class="text-3xl font-bold text-[#008B8B]">{{ pagination.total }}</span>
              <span class="text-xs text-gray-500 font-medium">requests waiting for approval</span>
            </div>
          </div>
          <button
            type="button"
            :disabled="loading || processingId !== null"
            class="inline-flex items-center justify-center rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:opacity-50 shadow-sm active:scale-95"
            @click="loadData(pagination.current_page)"
          >
            {{ loading ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>

        <!-- Feedback Notification -->
        <div
          v-if="feedback.message"
          :class="feedback.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'"
          class="flex items-center justify-between rounded-lg border px-5 py-3 text-sm shadow-sm"
          role="status"
        >
          <span>{{ feedback.message }}</span>
          <button type="button" class="ml-4 font-bold hover:opacity-75" @click="feedback.message = ''">Close</button>
        </div>

        <!-- Loading State -->
        <div v-if="loading && pickupRequests.length === 0" class="rounded-xl border border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-[#008B8B]"></div>
          <p class="mt-4 text-sm text-gray-500">Loading pickup requests...</p>
        </div>

        <!-- Empty State -->
        <p v-else-if="loadFailed" role="alert">Pickup requests could not be loaded. Please refresh to try again.</p>
        <div v-else-if="pickupRequests.length === 0" class="rounded-xl border border-dashed border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-600">
            Clear
          </div>
          <h2 class="mt-4 text-lg font-bold text-[#008B8B]">No Pending Requests</h2>
          <p class="mt-2 text-sm text-gray-500">All pickup requests have been processed.</p>
        </div>
        <!-- Pickup Requests Grid -->
        <div v-else class="grid gap-6 lg:grid-cols-2">
          <article
            v-for="request in pickupRequests"
            :key="request.id"
            class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm flex flex-col justify-between"
          >
            <div>
              <div class="flex items-start justify-between">
                <h3 class="font-bold text-gray-900">Parcel: {{ request.parcel?.order_id ?? 'Unavailable' }}</h3>
                <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                  {{ request.status }}
                </span>
              </div>
              <div class="mt-4 text-sm text-gray-600 space-y-1">
                <p><span class="font-semibold">Seller:</span> {{ request.seller?.name ?? 'Unknown' }}</p>
                <p><span class="font-semibold">Address:</span> {{ formatAddress(request.parcel) }}</p>
              </div>
            </div>
            <div class="mt-6 flex gap-3">
              <button
                type="button"
                :disabled="!request.parcel || processingId !== null || loading"
                class="flex-1 rounded-lg bg-[#008B8B] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#006666] disabled:opacity-50 shadow-sm"
                @click="confirmPickup(request.id)"
              >
                {{ processingId === request.id ? 'Processing...' : 'Confirm Pickup' }}
              </button>
            </div>
          </article>
        </div>
        <div class="flex items-center justify-between gap-4 text-sm">
          <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
          <div class="flex gap-3">
            <button type="button" :disabled="loading || processingId !== null || pagination.current_page <= 1" @click="loadData(pagination.current_page - 1)">Previous</button>
            <button type="button" :disabled="loading || processingId !== null || pagination.current_page >= pagination.last_page" @click="loadData(pagination.current_page + 1)">Next</button>
          </div>
        </div>
      </template>
    </div>
  </sc-layout>
</template>

<script>
import { onMounted, onUnmounted, reactive, ref } from 'vue';
import ScLayout from './ScLayout.vue';

export default {
  name: 'PickupRequestManagement',
  components: {
    ScLayout,
  },
  setup() {
    const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'));
    const pickupRequests = ref([]);
    const loading = ref(false);
    const loadFailed = ref(false);
    const processingId = ref(null);
    const feedback = reactive({ message: '', type: 'success' });
    const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
    let latestRequest = 0;
    onUnmounted(() => { latestRequest++; });

    async function requestJson(url, options = {}) {
      const response = await fetch(url, {
        ...options,
        headers: {
          Accept: 'application/json',
          ...(options.body ? { 'Content-Type': 'application/json' } : {}),
          Authorization: `Bearer ${token.value}`,
          ...(options.headers || {}),
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'The request could not be completed.');
      return data;
    }

    async function loadData(page = 1, afterConfirmation = false) {
      if (!token.value || (processingId.value !== null && !afterConfirmation)) return;
      const requestId = ++latestRequest;
      loading.value = true;
      feedback.message = '';
      try {
        const data = await requestJson(`/api/sorting-center/pickup-requests?status=pending&page=${page}`);
        if (requestId !== latestRequest) return;
        const result = data.data;
        if (!Array.isArray(result?.data)) throw new Error('Invalid pickup response.');
        if (page > result.last_page) return await loadData(result.last_page, afterConfirmation);
        pickupRequests.value = result.data;
        loadFailed.value = false;
        Object.assign(pagination, {
          current_page: result.current_page, last_page: result.last_page, total: result.total,
        });
      } catch (error) {
        if (requestId !== latestRequest) return;
        loadFailed.value = true;
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        if (requestId === latestRequest) loading.value = false;
      }
    }

    async function confirmPickup(id) {
      if (!token.value || processingId.value !== null || loading.value) return;
      processingId.value = id;
      feedback.message = '';
      try {
        const data = await requestJson(`/api/sorting-center/pickup-requests/${id}/confirm`, {
          method: 'POST',
        });
        await loadData(pagination.current_page, true);
        if (feedback.type === 'error' && feedback.message) return;
        feedback.message = data.message;
        feedback.type = 'success';
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        processingId.value = null;
      }
    }

    function formatAddress(parcel) {
      if (!parcel) return 'Address unavailable';
      return [
        [parcel.delivery_house_number, parcel.delivery_street].filter(Boolean).join(' '),
        parcel.delivery_barangay,
        parcel.delivery_municipality,
        parcel.delivery_province,
      ].filter(Boolean).join(', ') || 'Address unavailable';
    }

    onMounted(() => loadData());

    return {
      token,
      pickupRequests,
      pagination,
      loading,
      loadFailed,
      processingId,
      feedback,
      loadData,
      confirmPickup,
      formatAddress,
    };
  },
};
</script>

