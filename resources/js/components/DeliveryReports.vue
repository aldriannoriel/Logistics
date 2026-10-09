<template>
  <sc-layout title="Delivery Reports">
    <div class="space-y-6">
      <!-- Unauthenticated State -->
      <div v-if="!token" class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center shadow-sm">
        <h2 class="text-lg font-bold text-amber-950">Authentication required</h2>
        <p class="mt-2 text-sm text-amber-800">Log in with an approved sorting center account to generate reports.</p>
        <a href="/login" class="mt-5 inline-flex rounded-lg bg-[#FFA500] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] shadow-sm">
          Go to login
        </a>
      </div>

      <template v-else>
        <!-- Filter Card -->
        <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
          <div class="border-b border-[#DCE7E2] pb-4 mb-6">
            <h2 class="text-xl font-bold text-[#008B8B]">Performance &amp; Analytics</h2>
            <p class="mt-1 text-sm text-gray-600">Analyze parcel throughput, delivery status distribution, and average turnaround time.</p>
          </div>

          <form class="space-y-4" @submit.prevent="generateReport">
            <div class="grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
              <div>
                <label for="report-from" class="block text-sm font-semibold text-gray-700">From Date</label>
                <input
                  id="report-from"
                  v-model="form.from"
                  type="date"
                  required
                  class="mt-2 w-full rounded-lg border border-[#DCE7E2] bg-white px-3 py-2.5 text-sm text-[#333333] outline-none transition focus:border-[#008B8B] focus:ring-2 focus:ring-[#008B8B]/20"
                />
              </div>
              <div>
                <label for="report-to" class="block text-sm font-semibold text-gray-700">To Date</label>
                <input
                  id="report-to"
                  v-model="form.to"
                  type="date"
                  required
                  class="mt-2 w-full rounded-lg border border-[#DCE7E2] bg-white px-3 py-2.5 text-sm text-[#333333] outline-none transition focus:border-[#008B8B] focus:ring-2 focus:ring-[#008B8B]/20"
                />
              </div>
              <button
                type="submit"
                :disabled="loading || !form.from || !form.to"
                class="rounded-lg bg-[#FFA500] px-6 py-2.5 text-sm font-bold text-white transition hover:bg-[#E69500] disabled:cursor-not-allowed disabled:opacity-50 shadow-sm active:scale-95"
              >
                {{ loading ? 'Generating…' : 'Generate Report' }}
              </button>
            </div>
            <p class="text-xs text-gray-500">The date filter selects parcels sorted within the specified interval.</p>
          </form>
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

        <!-- Report Results Section -->
        <section v-if="report" class="space-y-6">
          <!-- Summary Stat Cards -->
          <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
              <p class="text-sm font-semibold text-gray-600">Total Parcels</p>
              <p class="mt-2 text-4xl font-bold text-[#008B8B]">{{ report.total_parcels_processed }}</p>
              <p class="mt-2 text-xs text-gray-500 font-medium">{{ report.from }} to {{ report.to }}</p>
            </div>

            <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#28A745] bg-white p-6 shadow-sm">
              <p class="text-sm font-semibold text-gray-600">Delivered</p>
              <p class="mt-2 text-4xl font-bold text-[#28A745]">{{ report.status_breakdown?.DELIVERED || 0 }}</p>
              <p class="mt-2 text-xs text-gray-500 font-medium">Successfully completed</p>
            </div>

            <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#DC3545] bg-white p-6 shadow-sm">
              <p class="text-sm font-semibold text-gray-600">Failed / Returned</p>
              <p class="mt-2 text-4xl font-bold text-[#DC3545]">{{ report.status_breakdown?.FAILED_RETURNED || 0 }}</p>
              <p class="mt-2 text-xs text-gray-500 font-medium">Undeliverable parcels</p>
            </div>

            <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#FFA500] bg-white p-6 shadow-sm">
              <p class="text-sm font-semibold text-gray-600">Avg. Turnaround</p>
              <p class="mt-2 text-2xl font-bold text-[#FFA500] leading-tight">{{ report.average_turnaround || '—' }}</p>
              <p class="mt-2 text-xs text-gray-500 font-medium">Sort to Dispatch time</p>
            </div>
          </div>

          <!-- Charts Row -->
          <div class="grid gap-6 lg:grid-cols-2">
            <!-- Doughnut: Status Distribution -->
            <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white p-6 shadow-sm">
              <h3 class="text-lg font-bold text-[#008B8B] mb-4">Status Distribution</h3>
              <div class="relative flex items-center justify-center" style="height:280px;">
                <canvas ref="doughnutCanvas"></canvas>
              </div>
            </div>

            <!-- Bar: Status Count Comparison -->
            <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#FFA500] bg-white p-6 shadow-sm">
              <h3 class="text-lg font-bold text-[#008B8B] mb-4">Parcel Status Breakdown</h3>
              <div style="height:280px;">
                <canvas ref="barCanvas"></canvas>
              </div>
            </div>
          </div>

          <!-- Status Breakdown Table -->
          <div class="rounded-xl border border-[#DCE7E2] border-l-4 border-l-[#008B8B] bg-white shadow-sm overflow-hidden">
            <div class="p-6 border-b border-[#DCE7E2]">
              <h3 class="text-lg font-bold text-[#008B8B]">Parcel Workflow Status Breakdown</h3>
            </div>
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-[#008B8B] text-white">
                  <th class="px-6 py-3 text-left font-semibold">Status</th>
                  <th class="px-6 py-3 text-right font-semibold">Count</th>
                  <th class="px-6 py-3 text-right font-semibold">% of Total</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(status, idx) in statusOrder"
                  :key="status"
                  :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                  class="border-b border-[#DCE7E2] last:border-0"
                >
                  <td class="px-6 py-3.5 font-medium text-gray-700 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full flex-shrink-0" :style="{ backgroundColor: statusColors[status] }"></span>
                    {{ formatStatus(status) }}
                  </td>
                  <td class="px-6 py-3.5 text-right font-bold text-[#008B8B]">
                    {{ report.status_breakdown[status] || 0 }}
                  </td>
                  <td class="px-6 py-3.5 text-right text-gray-600">
                    {{ report.total_parcels_processed > 0
                        ? Math.round(((report.status_breakdown[status] || 0) / report.total_parcels_processed) * 100)
                        : 0 }}%
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Empty Results -->
        <div v-else-if="hasSearched && !loading" class="rounded-xl border border-dashed border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-cyan-50 text-sm font-semibold text-[#008B8B]">None</div>
          <h2 class="mt-4 text-lg font-bold text-[#008B8B]">No Parcels Found in Date Range</h2>
          <p class="mt-2 text-sm text-gray-500">Try selecting a different date range to generate the report.</p>
        </div>

        <!-- Initial Prompt -->
        <div v-else-if="!hasSearched && !loading" class="rounded-xl border border-dashed border-[#DCE7E2] bg-white px-6 py-16 text-center shadow-sm">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-teal-50 text-sm font-semibold">Date Range</div>
          <h2 class="mt-4 text-lg font-bold text-[#008B8B]">Select a Date Range</h2>
          <p class="mt-2 text-sm text-gray-500">Choose start and end dates above, then click "Generate Report" to view analytics.</p>
        </div>
      </template>
    </div>
  </sc-layout>
</template>

<script>
import { reactive, ref, nextTick, onBeforeUnmount } from 'vue';
import Chart from 'chart.js/auto';
import ScLayout from './ScLayout.vue';

export default {
  name: 'DeliveryReports',
  components: { ScLayout },
  setup() {
    const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'));

    // Default date range: last 7 days
    const today = new Date();
    const sevenDaysAgo = new Date(today);
    sevenDaysAgo.setDate(today.getDate() - 7);
    const toDateStr = today.toISOString().slice(0, 10);
    const fromDateStr = sevenDaysAgo.toISOString().slice(0, 10);

    const form = reactive({ from: fromDateStr, to: toDateStr });
    const report = ref(null);
    const loading = ref(false);
    const hasSearched = ref(false);
    const feedback = reactive({ message: '', type: 'success' });

    const statusOrder = ['AT_SORTING_CENTER', 'OUT_FOR_DELIVERY', 'DELIVERED', 'FAILED_RETURNED'];
    const statusColors = {
      AT_SORTING_CENTER: '#008B8B',
      OUT_FOR_DELIVERY: '#FFA500',
      DELIVERED: '#28A745',
      FAILED_RETURNED: '#DC3545',
    };

    // Chart canvas refs
    const doughnutCanvas = ref(null);
    const barCanvas = ref(null);

    // Chart instances (for cleanup)
    let doughnutChart = null;
    let barChart = null;

    function destroyCharts() {
      if (doughnutChart) { doughnutChart.destroy(); doughnutChart = null; }
      if (barChart) { barChart.destroy(); barChart = null; }
    }

    function renderCharts(data) {
      destroyCharts();

      const labels = statusOrder.map(formatStatus);
      const counts = statusOrder.map(s => data.status_breakdown[s] || 0);
      const colors = statusOrder.map(s => statusColors[s]);

      // Doughnut — Status Distribution
      if (doughnutCanvas.value) {
        doughnutChart = new Chart(doughnutCanvas.value, {
          type: 'doughnut',
          data: {
            labels,
            datasets: [{
              data: counts,
              backgroundColor: colors,
              borderColor: '#ffffff',
              borderWidth: 3,
              hoverOffset: 8,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 16,
                  font: { size: 12, weight: 'bold' },
                  color: '#333333',
                  usePointStyle: true,
                  pointStyleWidth: 10,
                },
              },
              tooltip: {
                callbacks: {
                  label: (ctx) => {
                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    const pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                    return ` ${ctx.label}: ${ctx.parsed} (${pct}%)`;
                  },
                },
              },
            },
          },
        });
      }

      // Bar — Status Breakdown
      if (barCanvas.value) {
        barChart = new Chart(barCanvas.value, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              label: 'Parcel Count',
              data: counts,
              backgroundColor: colors.map(c => c + 'CC'),   // slight transparency
              borderColor: colors,
              borderWidth: 2,
              borderRadius: 6,
              borderSkipped: false,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: (ctx) => ` Count: ${ctx.parsed.y}`,
                },
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1,
                  color: '#666666',
                  font: { size: 12 },
                },
                grid: { color: '#DCE7E2' },
              },
              x: {
                ticks: {
                  color: '#333333',
                  font: { size: 11, weight: 'bold' },
                  maxRotation: 20,
                },
                grid: { display: false },
              },
            },
          },
        });
      }
    }

    async function generateReport() {
      loading.value = true;
      report.value = null;
      hasSearched.value = true;
      feedback.message = '';
      destroyCharts();

      try {
        const params = new URLSearchParams({ from: form.from, to: form.to });
        const response = await fetch(`/api/sorting-center/reports?${params}`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${token.value}` },
        });
        const json = await response.json();
        if (!response.ok) throw new Error(json.message || 'The report could not be generated.');

        report.value = json.data;
        feedback.message = json.message || 'Report generated successfully.';
        feedback.type = 'success';

        // Wait for Vue to render chart canvases before drawing
        await nextTick();
        if (json.data && json.data.total_parcels_processed > 0) {
          renderCharts(json.data);
        }
      } catch (error) {
        feedback.message = error.message;
        feedback.type = 'error';
      } finally {
        loading.value = false;
      }
    }

    function formatStatus(status) {
      const map = {
        AT_SORTING_CENTER: 'At Sorting Center',
        OUT_FOR_DELIVERY: 'Out for Delivery',
        DELIVERED: 'Delivered',
        FAILED_RETURNED: 'Failed / Returned',
      };
      return map[status] || (status ? status.replaceAll('_', ' ') : 'Unknown');
    }

    // Clean up Chart.js instances when component unmounts
    onBeforeUnmount(destroyCharts);

    return {
      token,
      form,
      report,
      loading,
      hasSearched,
      feedback,
      statusOrder,
      statusColors,
      doughnutCanvas,
      barCanvas,
      generateReport,
      formatStatus,
    };
  },
};
</script>
