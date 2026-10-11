import './bootstrap';
import { createApp } from 'vue';

const components = {
    'sorting-center-registration': () => import('./components/SortingCenterRegistration.vue'),
    'sorting-center-login': () => import('./components/SortingCenterLogin.vue'),
    'order-management': () => import('./components/OrderManagement.vue'),
    'delivery-management': () => import('./components/DeliveryManagement.vue'),
    'rider-assignment': () => import('./components/RiderAssignment.vue'),
    'delivery-monitoring': () => import('./components/DeliveryMonitoring.vue'),
    dashboard: () => import('./components/Dashboard.vue'),
    'delivery-reports': () => import('./components/DeliveryReports.vue'),
    'delivery-proof-review': () => import('./components/DeliveryProofReview.vue'),
    messages: () => import('./components/Messages.vue'),
    'pickup-request-management': () => import('./components/PickupRequestManagement.vue'),
};

async function mountPage() {
    const root = document.querySelector('#app');
    if (!root) return;

    try {
        const page = root.firstElementChild;
        const componentName = page?.localName;
        if (!Object.hasOwn(components, componentName ?? '')) {
            throw new Error('Unknown or missing Vue page component.');
        }

        const props = {};
        if (componentName === 'delivery-proof-review') {
            // Validate the required props and keep API requests same-origin.
            const parcelId = Number(page.getAttribute('parcel-id') ?? page.dataset.parcelId);
            if (!Number.isSafeInteger(parcelId) || parcelId <= 0) {
                throw new Error('A valid parcel ID is required for delivery proof review.');
            }
            props.parcelId = parcelId;
            props.apiUrl = '/api';
            props.token = localStorage.getItem('auth_token') || localStorage.getItem('token');
            if (!props.token) {
                throw new Error('Please log in to review delivery proofs.');
            }
        }

        const { default: component } = await components[componentName]();
        const app = createApp(component, props);
        let failed = false;
        app.config.errorHandler = (error) => {
            console.error('Vue page error:', error);
            if (failed) return;
            failed = true;
            queueMicrotask(() => {
                app.unmount();
                const message = document.createElement('p');
                message.setAttribute('role', 'alert');
                message.textContent = 'Something went wrong. Please refresh the page.';
                root.replaceChildren(message);
            });
        };
        app.mount(root);
    } catch (error) {
        console.error('Unable to start the Vue page:', error);
        const message = document.createElement('p');
        message.setAttribute('role', 'alert');
        message.textContent = 'Unable to load this page. Please refresh or log in again.';
        root.replaceChildren(message);
    }
}

void mountPage();
