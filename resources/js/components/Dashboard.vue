<template>
  <sc-layout title="Dashboard">
    <div class="app-page-shell">
      <div v-if="!token" class="app-panel app-panel--padded" style="padding: 2rem; text-align: center;">
        <h2 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--app-text);">Authentication required</h2>
        <p style="margin-top: 0.75rem; color: var(--app-text-muted);">Log in with an approved sorting center account to view the dashboard.</p>
        <a href="/login" class="app-button-primary" style="margin-top: 1.25rem;">Go to login</a>
      </div>

      <template v-else>
        <div v-if="feedback.message" class="app-panel app-panel--padded" style="padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;" role="status">
          <span>{{ feedback.message }}</span>
          <button type="button" class="app-button-secondary" style="padding: 0.5rem 0.75rem;" @click="feedback.message = ''">Close</button>
        </div>

        <div class="app-panel app-panel--padded" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
          <div>
            <h2 style="margin: 0; font-size: 1.2rem; font-weight: 700; color: var(--app-text);">Overview & Operations</h2>
            <p style="margin: 0.5rem 0 0; color: var(--app-text-muted); font-size: 0.92rem;">A real-time snapshot of work waiting for your team.</p>
          </div>
          <button type="button" :disabled="loading" class="app-button-primary" @click="loadDashboard">
            {{ loading ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>

        <div v-if="loading && !hasLoaded" class="app-panel app-panel--padded" style="padding: 3rem 1.5rem; text-align: center;">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-[#0D7377]" style="margin: 0 auto;"></div>
          <p style="margin-top: 1rem; color: var(--app-text-muted);">Loading dashboard...</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
          <a v-for="card in cards" :key="card.key" :href="card.href" class="app-card group" style="display: block; text-decoration: none; color: inherit;">
            <div style="min-width: 0;">
              <span class="app-card__label">{{ card.label }}</span>
              <span class="app-card__value">{{ counts[card.key] }}</span>
            </div>
            <div class="app-card__foot">
              <span>Open {{ card.destination }}</span>
              <span aria-hidden="true">→</span>
            </div>
          </a>
        </div>
      </template>
    </div>
  </sc-layout>
</template>

<script>
import { onMounted, reactive, ref } from 'vue';
import ScLayout from './ScLayout.vue';

export default {
  name: 'Dashboard',
  components: {
    ScLayout,
  },
  setup() {
    const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'));
    const loading = ref(false);
    const hasLoaded = ref(false);
    const feedback = reactive({ message: '', type: 'success' });
    const counts = reactive({
      riders_pending_approval: 0,
      pickup_requests_pending: 0,
      parcels_awaiting_sorting: 0,
      parcels_awaiting_rider_assignment: 0,
      parcels_out_for_delivery: 0,
      active_approved_riders: 0,
    });

    const cards = [
      { key: 'parcels_awaiting_sorting', label: 'Parcels awaiting sorting', destination: 'delivery management', href: '/sorting-center/delivery-management' },
      { key: 'parcels_awaiting_rider_assignment', label: 'Parcels awaiting rider assignment', destination: 'rider assignment', href: '/sorting-center/rider-assignment' },
      { key: 'parcels_out_for_delivery', label: 'Parcels out for delivery', destination: 'delivery monitoring', href: '/sorting-center/delivery-monitoring' },
    ];

    async function loadDashboard() {
      loading.value = true;
      try {
        const response = await fetch('/api/sorting-center/dashboard', {
          headers: {
            Accept: 'application/json',
            Authorization: 'Bearer ' + (localStorage.getItem('auth_token') || localStorage.getItem('token') || ''),
          },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Dashboard could not be loaded.');
        Object.assign(counts, data.data || {});
        hasLoaded.value = true;
        feedback.message = '';
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loading.value = false;
      }
    }

    onMounted(() => {
      loadDashboard();
    });

    return {
      token,
      loading,
      hasLoaded,
      feedback,
      counts,
      cards,
      loadDashboard,
    };
  },
};
</script>
