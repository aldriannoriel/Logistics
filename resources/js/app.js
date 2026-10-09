import './bootstrap';
import { createApp } from 'vue';
import SortingCenterRegistration from './components/SortingCenterRegistration.vue';
import SortingCenterLogin from './components/SortingCenterLogin.vue';
import OrderManagement from './components/OrderManagement.vue';
import DeliveryManagement from './components/DeliveryManagement.vue';
import RiderAssignment from './components/RiderAssignment.vue';
import DeliveryMonitoring from './components/DeliveryMonitoring.vue';
import Dashboard from './components/Dashboard.vue';
import DeliveryReports from './components/DeliveryReports.vue';
import DeliveryProofReview from './components/DeliveryProofReview.vue';
import Messages from './components/Messages.vue';

const components = {
    'sorting-center-registration': SortingCenterRegistration,
    'sorting-center-login': SortingCenterLogin,
    'order-management': OrderManagement,
    'delivery-management': DeliveryManagement,
    'rider-assignment': RiderAssignment,
    'delivery-monitoring': DeliveryMonitoring,
    dashboard: Dashboard,
    'delivery-reports': DeliveryReports,
    'delivery-proof-review': DeliveryProofReview,
    messages: Messages,
};

const root = document.querySelector('#app');
const componentName = root?.firstElementChild?.tagName.toLowerCase();
const component = componentName ? components[componentName] : undefined;

if (!root || !component) {
    throw new Error(`No Vue page component found for #app${componentName ? ` (${componentName})` : ''}`);
}

createApp(component).mount(root);
