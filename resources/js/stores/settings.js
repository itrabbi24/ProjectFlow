import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useSettingStore = defineStore('settings', () => {
    const settings = ref({
        company_name: 'ProjectFlow Corp',
        currency: 'BDT',
        currency_symbol: '৳',
        timezone: 'Asia/Dhaka',
        date_format: 'Y-m-d',
        theme: 'light'
    });

    const isLoaded = ref(false);

    async function fetchSettings() {
        try {
            const response = await axios.get('/settings');
            settings.value = { ...settings.value, ...response.data.data };
            isLoaded.value = true;
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    }

    async function saveSettings(data) {
        try {
            const response = await axios.post('/settings', data);
            settings.value = { ...settings.value, ...response.data.data };
            return response.data;
        } catch (error) {
            throw error.response?.data || error;
        }
    }

    return {
        settings,
        isLoaded,
        fetchSettings,
        saveSettings
    };
});
