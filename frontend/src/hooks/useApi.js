import { useState, useCallback } from 'react';
import { AppConfig } from '../config';

const useApi = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const callApi = useCallback(async (endpoint, method = 'GET', body = null) => {
        setLoading(true);
        setError(null);

        const token = localStorage.getItem('authToken');
        const headers = {
            'Content-Type': 'application/json',
        };
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            method,
            headers,
        };
        if (body) {
            config.body = JSON.stringify(body);
        }

        try {
            // Remove leading slash from endpoint to avoid double slashes
            const cleanEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint;
            const response = await fetch(`${AppConfig.api.baseUrl}/${cleanEndpoint}`, config);

            const responseData = await response.json().catch(() => ({
                // Fallback jika respons bukan JSON valid (misalnya error 500 dari PHP)
                message: `Server merespons dengan status ${response.status}, tetapi tidak ada pesan error yang bisa dibaca.`
            }));

            if (!response.ok) {
                // Gunakan pesan error dari server jika ada, jika tidak gunakan pesan umum
                const errorMessage = responseData?.message || `HTTP error! status: ${response.status}`;
                throw new Error(errorMessage);
            }

            return responseData;

        } catch (e) {
            // Memberikan pesan yang lebih jelas untuk error jaringan
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

    // PERBAIKAN: Mengekspor setLoading dan setError agar bisa digunakan di komponen
    return { loading, error, callApi, setLoading, setError };
};

export default useApi;

