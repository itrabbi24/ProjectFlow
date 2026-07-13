import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

// Configure Axios Defaults
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Accept'] = 'application/json';

// Axios Interceptor for Handling 401 Unauthorized Responses globally
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Clear storage and reload to login page
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('permissions');
            delete axios.defaults.headers.common['Authorization'];
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export const useAuthStore = defineStore('auth', () => {
    const user = ref(JSON.parse(localStorage.getItem('user')) || null);
    const token = ref(localStorage.getItem('token') || null);
    const permissions = ref(JSON.parse(localStorage.getItem('permissions')) || []);

    const isAuthenticated = computed(() => !!token.value);
    
    // Set axios auth header if token is present on boot
    if (token.value) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
    }

    async function login(username, password) {
        try {
            const response = await axios.post('/login', { username, password });
            const data = response.data.data;
            
            token.value = data.token;
            user.value = data.user;
            permissions.value = data.user.role?.permissions?.map(p => p.slug) || [];

            localStorage.setItem('token', token.value);
            localStorage.setItem('user', JSON.stringify(user.value));
            localStorage.setItem('permissions', JSON.stringify(permissions.value));

            axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    }

    async function fetchUser() {
        if (!token.value) return;
        try {
            const response = await axios.get('/me');
            const data = response.data.data;

            user.value = data.user;
            permissions.value = data.permissions || [];

            localStorage.setItem('user', JSON.stringify(user.value));
            localStorage.setItem('permissions', JSON.stringify(permissions.value));
        } catch (error) {
            console.error('Failed to refresh user/permissions:', error);
        }
    }

    async function logout() {
        try {
            await axios.post('/logout');
        } catch (error) {
            console.error('Logout API failed:', error);
        } finally {
            token.value = null;
            user.value = null;
            permissions.value = [];
            
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('permissions');
            
            delete axios.defaults.headers.common['Authorization'];
        }
    }

    function hasPermission(permission) {
        if (!user.value) return false;
        if (user.value?.role?.slug === 'administrator') return true;
        return permissions.value.includes(permission);
    }

    return {
        user,
        token,
        permissions,
        isAuthenticated,
        login,
        logout,
        fetchUser,
        hasPermission
    };
});
