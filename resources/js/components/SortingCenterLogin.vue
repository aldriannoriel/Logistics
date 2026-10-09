<template>
    <div class="min-h-screen bg-[#F5F5F5] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-[#008B8B] px-8 py-7">
                <img :src="logoUrl" alt="Alona" class="mx-auto mb-3 h-10 w-10 object-contain" />
                <h1 class="text-2xl font-bold text-white text-center">Sorting Center Login</h1>
                <p class="text-indigo-100 text-center mt-2 text-sm">Access your sorting center portal and shipments</p>
            </div>

            <!-- Login Form (Visible when not logged in) -->
            <form v-if="!authData" @submit.prevent="handleLogin" class="p-8 space-y-6">
                <!-- General Error Banner -->
                <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 flex items-start">
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-gray-900 placeholder-gray-400"
                        placeholder="you@sortingcenter.com"
                        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': fieldErrors.email }"
                        @input="clearFieldError('email')"
                    />
                    <p v-if="fieldErrors.email" class="mt-1 text-sm text-red-600">{{ fieldErrors.email[0] }}</p>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-gray-900 placeholder-gray-400"
                        placeholder="Enter your password"
                        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': fieldErrors.password }"
                        @input="clearFieldError('password')"
                    />
                    <p v-if="fieldErrors.password" class="mt-1 text-sm text-red-600">{{ fieldErrors.password[0] }}</p>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-[#FFA500] hover:bg-[#E69500] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <span v-if="loading" class="flex items-center">
                        Signing in...
                    </span>
                    <span v-else>Sign In</span>
                </button>

                <!-- Navigation link to Register -->
                <div class="pt-2 text-center text-sm text-gray-600">
                    Don't have an account yet?
                    <a href="/register" class="font-medium text-indigo-600 hover:text-indigo-500 ml-1 transition">
                        Register your sorting center
                    </a>
                </div>
            </form>

            <!-- Status State 1: PENDING -->
            <div v-else-if="authData.approval_status === 'pending'" class="p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 mb-4">
                    <span class="text-sm font-semibold text-amber-600">Pending</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Awaiting Approval</h2>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 mb-4">
                    Status: Pending Review
                </div>
                <p class="text-gray-600 mb-6">
                    Your registration is still awaiting administrator approval. You will receive full access to the sorting center dashboard once your documents and business credentials are verified.
                </p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Business:</span>
                        <span class="font-medium text-gray-900">{{ authData.sorting_center?.business_name || authData.user?.name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Email:</span>
                        <span class="font-medium text-gray-900">{{ authData.user?.email }}</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <button
                        @click="handleLogout"
                        class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition"
                    >
                        Sign Out
                    </button>
                </div>
            </div>

            <!-- Status State 3: REJECTED -->
            <div v-else-if="authData.approval_status === 'rejected'" class="p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <span class="text-sm font-semibold text-red-600">Rejected</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Application Not Approved</h2>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 mb-4">
                    Status: Rejected
                </div>
                <p class="text-gray-600 mb-6">
                    We regret to inform you that your sorting center application was not approved during the administrative verification process.
                </p>

                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-sm text-gray-600 text-left">
                    <p class="font-medium text-gray-900 mb-1">Need assistance?</p>
                    <p>Please contact our support team at <strong class="text-indigo-600">support@logistics.com</strong> with your business registration details.</p>
                </div>

                <button
                    @click="handleLogout"
                    class="w-full py-2.5 px-4 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition"
                >
                    Sign Out
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';

const logoUrl = '/images/alona/alonalogo1.png';
const form = reactive({
    email: '',
    password: '',
});

const fieldErrors = reactive({});
const errorMessage = ref('');
const loading = ref(false);
const authData = ref(null);

onMounted(() => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_session');
});

function clearFieldError(field) {
    if (fieldErrors[field]) {
        delete fieldErrors[field];
    }
    errorMessage.value = '';
}

async function handleLogin() {
    loading.value = true;
    errorMessage.value = '';
    Object.keys(fieldErrors).forEach(k => delete fieldErrors[k]);

    try {
        const response = await fetch('/api/sorting-center/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                email: form.email,
                password: form.password,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422) {
                // Validation errors
                Object.assign(fieldErrors, data.errors || {});
                errorMessage.value = data.message || 'Please correct the validation errors.';
            } else if (response.status === 401) {
                // Invalid credentials
                errorMessage.value = data.message || 'Invalid email or password.';
            } else {
                errorMessage.value = data.message || 'An unexpected error occurred. Please try again.';
            }
            return;
        }

        // Store token and session data in localStorage
        if (data.token) {
            localStorage.setItem('auth_token', data.token);
            localStorage.setItem('auth_session', JSON.stringify(data));
        }

        if (data.approval_status === 'approved') {
            window.location.assign('/sorting-center/dashboard');
            return;
        }

        authData.value = data;
    } catch (err) {
        errorMessage.value = 'Network error. Please check your connection and try again.';
    } finally {
        loading.value = false;
    }
}

function handleLogout() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_session');
    authData.value = null;
    form.password = '';
    errorMessage.value = '';
}
</script>
