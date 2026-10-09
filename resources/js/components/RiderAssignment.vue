<template>
  <sc-layout title="Rider Assignment">
    <div class="space-y-6">
      <!-- Unauthenticated State -->
      <div v-if="!token" class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
        <h2 class="text-lg font-bold text-amber-950">Authentication required</h2>
        <p class="mt-2 text-sm text-amber-800">Log in with an approved sorting center account to assign riders.</p>
        <a href="/login" class="mt-5 inline-flex rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] shadow-sm">
          Go to login
        </a>
      </div>

      <template v-else>
        <!-- Top Status & Actions Card -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
          <div>
            <p class="text-sm font-semibold text-gray-600">Sorted & Unassigned Queue</p>
            <div class="mt-1 flex items-baseline gap-2">
              <span class="text-3xl font-bold text-[#008B8B]">{{ parcels.length }}</span>
              <span class="text-xs text-gray-500 font-medium">parcels ready for rider dispatch</span>
            </div>
          </div>
          <button
            type="button"
            :disabled="loading"
            class="inline-flex items-center justify-center rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:opacity-50 shadow-sm active:scale-95"
            @click="loadParcels"
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
          <p class="mt-4 text-sm text-gray-500">Loading parcels and eligible riders...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="parcels.length === 0" class="rounded-xl border border-dashed border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-sm font-semibold text-emerald-600">
            Clear
          </div>
          <h2 class="mt-4 text-lg font-bold text-[#008B8B]">Nothing Needs Assignment</h2>
          <p class="mt-2 text-sm text-gray-500">All sorted parcels have been assigned to delivery riders.</p>
        </div>

        <!-- Parcels Assignment Grid -->
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
                  {{ parcel.sorted_area?.name || parcel.delivery_municipality || 'Area Assigned' }}
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
              <label :for="`rider-${parcel.id}`" class="block text-sm font-semibold text-gray-700 mb-1">
                Assign Rider
              </label>
              <div class="flex flex-col gap-3 sm:flex-row">
                <select
                  :id="`rider-${parcel.id}`"
                  v-model="selectedRiders[parcel.id]"
                  :disabled="loadingRiders[parcel.id]"
                  class="min-w-0 flex-1 rounded-lg border border-[#DCE7E2] bg-white px-3 py-2 text-sm text-[#333333] outline-none transition focus:border-[#008B8B] focus:ring-2 focus:ring-[#008B8B]/20 disabled:opacity-60"
                >
                  <option value="">
                    {{ loadingRiders[parcel.id] ? 'Loading riders...' : riders[parcel.id]?.length ? 'Choose a rider' : 'No eligible riders' }}
                  </option>
                  <option v-for="rider in riders[parcel.id] || []" :key="rider.id" :value="rider.id">
                    {{ rider.name }}{{ rider.vehicle_type ? ` - ${rider.vehicle_type}` : '' }}
                    · {{ rider.deliveries_in_progress_count > 0
                      ? `Delivering ${rider.deliveries_in_progress_count}`
                      : rider.active_assignments_count > 0
                        ? `${rider.active_assignments_count} assigned`
                        : 'Available' }}
                  </option>
                </select>
                <button
                  type="button"
                  :disabled="!selectedRiders[parcel.id] || assigningId === parcel.id"
                  class="rounded-lg bg-[#FFA500] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:cursor-not-allowed disabled:opacity-50 shadow-sm active:scale-95"
                  @click="assignRider(parcel.id)"
                >
                  {{ assigningId === parcel.id ? 'Assigning...' : 'Assign Delivery' }}
                </button>
              </div>
              <p
                v-for="rider in riders[parcel.id] || []"
                :key="`workload-${rider.id}`"
                class="mt-2 text-xs"
                :class="rider.deliveries_in_progress_count > 0 ? 'text-amber-700' : 'text-emerald-700'"
              >
                {{ rider.name }}:
                <strong v-if="rider.deliveries_in_progress_count > 0">
                  Delivering {{ rider.deliveries_in_progress_count }} parcel(s)
                </strong>
                <strong v-else-if="rider.active_assignments_count > 0">
                  {{ rider.active_assignments_count }} assigned parcel(s), not started
                </strong>
                <strong v-else>Available</strong>
              </p>
              <p v-if="!loadingRiders[parcel.id] && riders[parcel.id]?.length === 0" class="mt-2 text-xs text-amber-700 font-medium">
                No approved and active rider currently covers this area.
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
  name: 'RiderAssignment',
  components: {
    ScLayout,
  },
  setup() {
    const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'));
    const parcels = ref([]);
    const riders = reactive({});
    const selectedRiders = reactive({});
    const loadingRiders = reactive({});
    const loading = ref(false);
    const assigningId = ref(null);
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

    async function loadParcels() {
      loading.value = true;
      try {
        const data = await requestJson('/api/sorting-center/parcels?status=sorted-unassigned');
        parcels.value = data.data || [];
        await Promise.all(parcels.value.map((parcel) => loadEligibleRiders(parcel.id)));
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loading.value = false;
      }
    }

    async function loadEligibleRiders(parcelId) {
      loadingRiders[parcelId] = true;
      try {
        const data = await requestJson(`/api/sorting-center/parcels/${parcelId}/eligible-riders`);
        riders[parcelId] = data.data || [];
      } catch (error) {
        riders[parcelId] = [];
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loadingRiders[parcelId] = false;
      }
    }

    async function assignRider(parcelId) {
      assigningId.value = parcelId;
      feedback.message = '';
      try {
        const data = await requestJson(`/api/sorting-center/parcels/${parcelId}/assign`, {
          method: 'POST',
          body: JSON.stringify({ rider_id: selectedRiders[parcelId] }),
        });
        parcels.value = parcels.value.filter((parcel) => parcel.id !== parcelId);
        feedback.message = data.message;
        feedback.type = 'success';
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        assigningId.value = null;
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

    onMounted(loadParcels);

    return {
      token,
      parcels,
      riders,
      selectedRiders,
      loadingRiders,
      loading,
      assigningId,
      feedback,
      loadParcels,
      assignRider,
      formatAddress,
    };
  },
};
</script>
