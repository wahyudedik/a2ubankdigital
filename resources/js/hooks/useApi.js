import { useState, useCallback } from 'react';
import { AppConfig } from '@/config';
import { convertEndpoint } from '@/utils/endpointMapping';

/**
 * useApi hook - Full monolith version
 * Uses session cookies for auth (no Bearer token needed)
 * CSRF token is sent automatically via cookie
 */
const useApi = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const callApi = useCallback(async (endpoint, method = 'GET', body = null) => {
        setLoading(true);
        setError(null);

        // Get CSRF token from meta tag or cookie
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || getCookie('XSRF-TOKEN');

        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        // Add CSRF token for non-GET requests
        if (csrfToken && method !== 'GET') {
            headers['X-XSRF-TOKEN'] = decodeURIComponent(csrfToken);
        }

        const config = {
            method,
            headers,
            credentials: 'same-origin', // Send session cookies
        };
        if (body) {
            config.body = JSON.stringify(body);
        }

        try {
            const convertedEndpoint = convertEndpoint(endpoint, method, body);
            const finalEndpoint = convertedEndpoint.startsWith('/') ? convertedEndpoint : `/${convertedEndpoint}`;
            const response = await fetch(`${AppConfig.api.baseUrl}${finalEndpoint}`, config);

            const responseData = await response.json().catch(() => ({
                message: `Server merespons dengan status ${response.status}, tetapi tidak ada pesan error yang bisa dibaca.`
            }));

            if (!response.ok) {
                if (response.status === 401 && window.location.pathname !== '/login') {
                    window.location.href = '/login';
                    return null;
                }

                // Handle Laravel validation errors (422)
                if (response.status === 422 && responseData?.errors) {
                    const firstError = Object.values(responseData.errors).flat()[0];
                    throw new Error(firstError || responseData?.message || 'Validasi gagal.');
                }

                const errorMessage = responseData?.message || `HTTP error! status: ${response.status}`;
                throw new Error(errorMessage);
            }

            return responseData;

        } catch (e) {
            const finalError = e.message.includes("Failed to fetch")
                ? "Gagal terhubung ke server. Periksa koneksi internet Anda atau hubungi administrator."
                : e.message;

            setError(finalError);
            console.error("API call failed:", finalError);
            return null;
        } finally {
            setLoading(false);
        }
    }, []);

    return { loading, error, callApi, setLoading, setError };
};

// Helper to get cookie value
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

export default useApi;
