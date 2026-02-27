import React, { createContext, useContext, useCallback, useState, useEffect, useMemo } from 'react';
import useApi from '/src/hooks/useApi.js';
import { useModal } from '/src/contexts/ModalContext.jsx';

const NotificationContext = createContext(null);

export const useNotification = () => useContext(NotificationContext);

export const NotificationProvider = ({ children }) => {
    const { callApi } = useApi();
    const modal = useModal();
    const [status, setStatus] = useState('loading');

    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    };

    const updateSubscriptionStatus = useCallback(async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            setStatus('unsupported');
            return;
        }
        
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            
            if (subscription) {
                setStatus('granted');
            } else {
                setStatus(Notification.permission === 'denied' ? 'denied' : 'prompt');
            }
        } catch (error) {
            console.error("Gagal mendapatkan status langganan:", error);
            setStatus('error_checking');
        }
    }, []);

    useEffect(() => {
        // Fungsi ini akan dipanggil setelah service worker berhasil terdaftar
        updateSubscriptionStatus();
    }, [updateSubscriptionStatus]);

    const subscribe = useCallback(async () => {
        const vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;
        if (!vapidPublicKey) {
            modal.showAlert({ title: 'Kesalahan Konfigurasi', message: 'Kunci notifikasi tidak terkonfigurasi.', type: 'warning' });
            return;
        }

        try {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                modal.showAlert({ title: 'Izin Ditolak', message: 'Anda telah memblokir notifikasi. Aktifkan melalui pengaturan browser.', type: 'info'});
                updateSubscriptionStatus();
                return;
            }
            
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
            });

            const result = await callApi('push_notification_subscribe.php', 'POST', subscription.toJSON());

            if (result && result.status === 'success') {
                await modal.showAlert({ title: 'Berhasil', message: 'Notifikasi push berhasil diaktifkan.', type: 'success' });
            } else {
                await subscription.unsubscribe();
                throw new Error(result?.message || 'Gagal menyimpan langganan.');
            }
        } catch (err) {
            console.error("Gagal berlangganan push notification: ", err);
            modal.showAlert({ title: 'Gagal', message: err.message, type: 'warning' });
        } finally {
            updateSubscriptionStatus();
        }
    }, [callApi, modal, updateSubscriptionStatus]);

    const value = useMemo(() => ({
        notificationStatus: status,
        subscribeToNotifications: subscribe
    }), [status, subscribe]);

    return (
        <NotificationContext.Provider value={value}>
            {children}
        </NotificationContext.Provider>
    );
};

