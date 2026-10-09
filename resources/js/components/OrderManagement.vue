<template>
  <sc-layout title="Order Management">
    <div class="space-y-6 max-w-4xl">
      <!-- Unauthenticated State -->
      <div v-if="!token" class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
        <h2 class="text-lg font-bold text-amber-950">Authentication required</h2>
        <p class="mt-2 text-sm text-amber-800">Log in with an approved sorting center account to manage incoming orders.</p>
        <a href="/login" class="mt-5 inline-flex rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] shadow-sm">
          Go to login
        </a>
      </div>

      <template v-else>
        <!-- Search Parcel Card -->
        <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
          <div class="border-b border-[#DCE7E2] pb-4 mb-6">
            <h2 class="text-xl font-bold text-[#008B8B]">Search by Tracking Number</h2>
            <p class="mt-1 text-sm text-gray-600">Find an order and confirm when its parcel arrives at the sorting center.</p>
          </div>

          <!-- Feedback message -->
          <div
            v-if="feedback.message"
            :class="feedback.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'"
            class="mb-6 flex items-center justify-between rounded-lg border px-5 py-3 text-sm shadow-sm"
            role="status"
          >
            <span>{{ feedback.message }}</span>
            <button type="button" class="ml-4 font-bold hover:opacity-75" @click="feedback.message = ''">Close</button>
          </div>

          <form class="space-y-5" @submit.prevent="searchParcel">
            <div>
              <label for="order-id" class="block text-sm font-semibold text-gray-700">Tracking Number</label>
              <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                <input
                  id="order-id"
                  v-model.trim="orderId"
                  type="text"
                  maxlength="50"
                  required
                  autocomplete="off"
                  placeholder="Enter tracking number"
                  class="min-w-0 flex-1 rounded-lg border border-[#DCE7E2] bg-white px-4 py-2.5 text-sm text-[#333333] outline-none transition focus:border-[#008B8B] focus:ring-2 focus:ring-[#008B8B]/20"
                />
                <button
                  type="submit"
                  :disabled="loading || !orderId"
                  class="rounded-lg bg-[#FFA500] px-6 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:cursor-not-allowed disabled:opacity-50 shadow-sm active:scale-95"
                >
                  {{ loading ? 'Searching...' : 'Search' }}
                </button>
              </div>
            </div>

            <!-- Parcel Result Info -->
            <div v-if="parcel" class="mt-6 rounded-lg border border-[#DCE7E2] bg-gray-50 p-5 text-sm">
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                  <span class="text-xs uppercase font-bold text-gray-500">Tracking Number:</span>
                  <span class="font-mono font-bold text-[#008B8B] text-base">{{ parcel.order_id }}</span>
                </div>
                <span class="rounded-full bg-[#008B8B] px-3 py-1 text-xs font-bold text-white shadow-sm">{{ parcel.status }}</span>
              </div>
              <div class="mt-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Delivery Address</p>
                <p class="mt-1 text-gray-700">{{ formatAddress(parcel) || 'Not provided' }}</p>
              </div>
              <button
                v-if="parcel.status === 'PICKED_UP'"
                type="button"
                :disabled="loading"
                class="mt-5 rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:cursor-not-allowed disabled:opacity-50 shadow-sm"
                @click="confirmArrival"
              >
                {{ loading ? 'Confirming...' : 'Confirm Arrival' }}
              </button>
              <p v-else class="mt-4 font-semibold text-emerald-700">
                Already at this sorting center.
                Proceed to <a href="/sorting-center/delivery-management" class="text-[#008B8B] hover:underline">Delivery Management</a>.
              </p>
            </div>
          </form>
        </div>

        <!-- Instructions & Tips Card -->
        <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
          <h3 class="text-base font-bold text-[#008B8B] mb-2">Operational Guide</h3>
          <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600">
            <li>Ensure the parcel's tracking number matches the order label.</li>
            <li>Once marked arrived, parcel status transitions from <code class="bg-gray-100 px-1 py-0.5 rounded text-xs text-[#008B8B]">PICKED_UP</code> to <code class="bg-gray-100 px-1 py-0.5 rounded text-xs text-[#008B8B]">AT_SORTING_CENTER</code>.</li>
            <li>Staff can manage arrived parcels from Delivery Management.</li>
          </ul>
        </div>
      </template>
    </div>
  </sc-layout>
</template>

<script>
import { reactive, ref } from 'vue';
import ScLayout from './ScLayout.vue';

export default {
  name: 'OrderManagement',
  components: {
    ScLayout,
  },
  setup() {
    const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'));
    const orderId = ref('');
    const loading = ref(false);
    const parcel = ref(null);
    const feedback = reactive({ message: '', type: 'success' });

    async function searchParcel() {
      if (!orderId.value.trim()) {
        feedback.message = 'Enter a tracking number.';
        feedback.type = 'error';
        return;
      }

      loading.value = true;
      parcel.value = null;
      feedback.message = '';

      try {
        const response = await fetch(`/api/sorting-center/parcels/lookup/${encodeURIComponent(orderId.value.trim())}`, {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token.value}`,
          },
        });
        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || 'The order could not be found.');
        }

        parcel.value = data.data;
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loading.value = false;
      }
    }

    async function confirmArrival() {
      loading.value = true;
      parcel.value = null;
      feedback.message = '';

      try {
        const response = await fetch('/api/sorting-center/parcels/mark-arrived', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token.value}`,
          },
          body: JSON.stringify({ order_id: orderId.value }),
        });
        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || 'The parcel could not be marked as arrived.');
        }

        parcel.value = data.data;
        feedback.message = data.message;
        feedback.type = 'success';
        orderId.value = '';
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loading.value = false;
      }
    }

    function formatAddress(item) {
      return [
        [item.delivery_house_number, item.delivery_street].filter(Boolean).join(' '),
        item.delivery_barangay,
        item.delivery_municipality,
        item.delivery_province,
      ].filter(Boolean).join(', ');
    }

    return {
      token,
      orderId,
      loading,
      parcel,
      feedback,
      searchParcel,
      confirmArrival,
      formatAddress,
    };
  },
};
</script>
