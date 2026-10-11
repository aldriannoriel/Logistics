<template>
  <div class="app-shell">
    <aside class="app-sidebar">
      <div class="app-sidebar__brand">
        <img :src="logoUrl" alt="Alona Logo" />
        <div class="app-sidebar__brand-text">
          <span class="app-sidebar__brand-name">Alona</span>
          <span class="app-sidebar__brand-subtitle">Logistics</span>
        </div>
      </div>

      <nav class="app-sidebar__nav" aria-label="Sidebar navigation">
        <a
          v-for="item in navItems"
          :key="item.path"
          :href="item.path"
          :class="['app-nav-item', { 'is-active': isActive(item.path) }]"
        >
          <span>{{ item.label }}</span>
        </a>
      </nav>

      <div class="app-sidebar__user">
        <div class="app-user">
          <div class="app-user__avatar">{{ userInitial }}</div>
          <div class="app-user__meta">
            <span class="app-user__name">{{ userName }}</span>
            <span class="app-user__role">{{ userRole }}</span>
          </div>
        </div>

        <button type="button" class="app-logout-button" @click="logout">Logout</button>
      </div>
    </aside>

    <main class="app-main">
      <header class="app-header">
        <div class="app-header__title">
          <span class="app-header__accent"></span>
          <h1>{{ pageTitle }}</h1>
        </div>
        <div class="app-header__meta">{{ currentDateTime }}</div>
      </header>

      <div class="app-content">
        <slot></slot>
      </div>
    </main>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';

export default {
  name: 'ScLayout',
  props: {
    title: {
      type: String,
      required: true,
    },
  },
  setup(props) {
    const currentDateTime = ref('');

    const navItems = [
      { path: '/sorting-center/dashboard', label: 'Dashboard' },
      { path: '/sorting-center/order-management', label: 'Order Management' },
      { path: '/sorting-center/delivery-management', label: 'Delivery Management' },
      { path: '/sorting-center/pickup-requests', label: 'Pickup Requests' },
      { path: '/sorting-center/rider-assignment', label: 'Rider Assignment' },
      { path: '/sorting-center/delivery-monitoring', label: 'Delivery Monitoring' },
      { path: '/sorting-center/delivery-reports', label: 'Delivery Reports' },
      { path: '/sorting-center/messages', label: 'Messages' },
    ];

    const userName = ref('Sorting Center');
    const userRole = ref('Administrator');
    const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
    const pageTitle = computed(() => props.title);
    const logoUrl = '/images/alona/alonalogo1.png';

    const isActive = (path) => {
      const current = window.location.pathname;
      if (current === path) return true;
      return false;
    };

    const updateDateTime = () => {
      const now = new Date();
      currentDateTime.value = now.toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    };

    const logout = async () => {
      if (confirm('Are you sure you want to logout?')) {
        try {
          localStorage.removeItem('auth_token');
          localStorage.removeItem('auth_session');
          localStorage.removeItem('token');
          localStorage.removeItem('sortingCenterId');
          localStorage.removeItem('sortingCenterName');
          window.location.href = '/sorting-center/login';
        } catch (error) {
          console.error('Logout error:', error);
        }
      }
    };

    let interval;
    onUnmounted(() => clearInterval(interval));

    onMounted(() => {
      updateDateTime();
      interval = setInterval(updateDateTime, 60000);

      const sessionStr = localStorage.getItem('auth_session');
      if (sessionStr) {
        try {
          const session = JSON.parse(sessionStr);
          if (session.sorting_center?.business_name) {
            userName.value = session.sorting_center.business_name;
          } else if (session.user?.name) {
            userName.value = session.user.name;
          }
        } catch (e) {
          // ignore json parse error
        }
      }
      const storedName = localStorage.getItem('sortingCenterName');
      if (storedName) userName.value = storedName;

    });

    return {
      navItems,
      userName,
      userRole,
      userInitial,
      pageTitle,
      currentDateTime,
      isActive,
      logout,
      logoUrl,
    };
  },
};
</script>
