<template>
  <sc-layout title="Delivery Management">
    <div class="space-y-6">
      <!-- Unauthenticated State -->
      <div v-if="!token" class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
        <h2 class="text-lg font-bold text-amber-950">Authentication required</h2>
        <p class="mt-2 text-sm text-amber-800">Log in with an approved sorting center account to sort parcels.</p>
        <a href="/login" class="mt-5 inline-flex rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] shadow-sm">
          Go to login
        </a>
      </div>

      <template v-else>
        <!-- Top Status & Actions Card -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
          <div>
            <p class="text-sm font-semibold text-gray-600">Arrived & Unsorted Queue</p>
            <div class="mt-1 flex items-baseline gap-2">
              <span class="text-3xl font-bold text-[#008B8B]">{{ parcels.length }}</span>
              <span class="text-xs text-gray-500 font-medium">parcels waiting for city service-area assignment</span>
            </div>
          </div>
          <button
            type="button"
            :disabled="loading"
            class="inline-flex items-center justify-center rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:opacity-50 shadow-sm active:scale-95"
            @click="loadData"
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
        <div v-if="loading && parcels.length === 0" class="rounded-xl border border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-[#008B8B]"></div>
          <p class="mt-4 text-sm text-gray-500">Loading parcels and areas...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="parcels.length === 0" class="rounded-xl border border-dashed border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-600">
            Clear
          </div>
          <h2 class="mt-4 text-lg font-bold text-[#008B8B]">Nothing Needs Sorting</h2>
          <p class="mt-2 text-sm text-gray-500">All arrived parcels have been sorted to delivery areas.</p>
        </div>

        <!-- Parcel Sorting Grid -->
        <div v-else class="grid gap-6 lg:grid-cols-2">
          <article
            v-for="parcel in parcels"
            :key="parcel.id"
            class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm flex flex-col justify-between"
          >
            <div>
              <div class="flex items-start justify-between border-b border-[#DCE7E2] pb-4">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Order ID</p>
                  <h3 class="mt-1 font-mono text-lg font-bold text-[#008B8B]">{{ parcel.order_id }}</h3>
                </div>
                <span class="rounded-full bg-[#008B8B] px-3 py-1 text-xs font-bold uppercase tracking-wide text-white shadow-sm">
                  Arrived
                </span>
              </div>

              <div class="py-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Delivery Address</p>
                <p class="mt-1 text-sm leading-6 text-gray-700 bg-gray-50 p-3 rounded-lg border border-[#DCE7E2]">
                  {{ formatAddress(parcel) }}
                </p>
              </div>
            </div>

            <div class="pt-2 border-t border-[#DCE7E2]">
              <label :for="`area-${parcel.id}`" class="block text-sm font-semibold text-gray-700 mb-1">
                Assign City Service Area
              </label>
              <div class="flex flex-col gap-3 sm:flex-row">
                <select
                  :id="`area-${parcel.id}`"
                  v-model="selectedAreas[parcel.id]"
                  class="min-w-0 flex-1 rounded-lg border border-[#DCE7E2] bg-white px-3 py-2 text-sm text-[#333333] outline-none transition focus:border-[#008B8B] focus:ring-2 focus:ring-[#008B8B]/20"
                >
                  <option value="">Choose {{ parcel.delivery_municipality }}</option>
                  <option v-for="area in areasForParcel(parcel)" :key="area.id" :value="area.id">{{ area.name }}</option>
                </select>
                <button
                  type="button"
                  :disabled="!selectedAreas[parcel.id] || sortingId === parcel.id"
                  class="rounded-lg bg-[#FFA500] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:cursor-not-allowed disabled:opacity-50 shadow-sm active:scale-95"
                  @click="sortParcel(parcel.id)"
                >
                  {{ sortingId === parcel.id ? 'Assigning...' : 'Sort to Area' }}
                </button>
              </div>
              <p v-if="areasForParcel(parcel).length === 0" class="mt-2 text-xs text-amber-700 font-medium">
                No service area yet for {{ parcel.delivery_municipality }}.
                <button
                  type="button"
                  class="font-bold text-[#008B8B] underline"
                  :disabled="creatingArea"
                  @click="createCityArea(parcel)"
                >
                  {{ creatingArea ? 'Creating...' : 'Add this city' }}
                </button>
              </p>
            </div>
          </article>
        </div>
      </template>
    </div>
  </sc-layout>
</template>

<script>
import { onMounted, reactive, ref } from 'vue';
import ScLayout from './ScLayout.vue';

export default {
  name: 'DeliveryManagement',
  components: {
    ScLayout,
  },
  setup() {
    const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'));
    const parcels = ref([]);
    const areas = ref([]);
    const selectedAreas = reactive({});
    const loading = ref(false);
    const sortingId = ref(null);
    const creatingArea = ref(false);
    const feedback = reactive({ message: '', type: 'success' });

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

    async function loadData() {
      loading.value = true;
      try {
        const [parcelData, areaData] = await Promise.all([
          requestJson('/api/sorting-center/parcels'),
          requestJson('/api/sorting-center/areas'),
        ]);
        parcels.value = parcelData.data || [];
        areas.value = areaData.data || [];
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loading.value = false;
      }
    }

    async function sortParcel(id) {
      sortingId.value = id;
      feedback.message = '';
      try {
        const data = await requestJson(`/api/sorting-center/parcels/${id}/sort`, {
          method: 'POST',
          body: JSON.stringify({ area_id: selectedAreas[id] }),
        });
        parcels.value = parcels.value.filter((parcel) => parcel.id !== id);
        delete selectedAreas[id];
        feedback.message = data.message;
        feedback.type = 'success';
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        sortingId.value = null;
      }
    }

    function areasForParcel(parcel) {
      const municipality = String(parcel.delivery_municipality || '').trim().toLocaleLowerCase();
      return areas.value.filter((area) => area.name.trim().toLocaleLowerCase() === municipality);
    }

    async function createCityArea(parcel) {
      creatingArea.value = true;
      feedback.message = '';
      try {
        const data = await requestJson('/api/sorting-center/areas', {
          method: 'POST',
          body: JSON.stringify({ name: parcel.delivery_municipality }),
        });
        areas.value.push(data.data);
        selectedAreas[parcel.id] = data.data.id;
        feedback.message = data.message;
        feedback.type = 'success';
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        creatingArea.value = false;
      }
    }

    function formatAddress(parcel) {
      return [
        [parcel.delivery_house_number, parcel.delivery_street].filter(Boolean).join(' '),
        parcel.delivery_barangay,
        parcel.delivery_municipality,
        parcel.delivery_province,
      ].filter(Boolean).join(', ');
    }

    onMounted(loadData);

    return {
      token,
      parcels,
      areas,
      selectedAreas,
      loading,
      sortingId,
      creatingArea,
      feedback,
      loadData,
      sortParcel,
      areasForParcel,
      createCityArea,
      formatAddress,
    };
  },
};
</script>
