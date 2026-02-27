import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { ArrowLeft, Bell, Check } from 'lucide-react';
import Button from '../components/ui/Button';

const NotificationItem = ({ notification }) => {
    return (
        <div className={`p-4 border-l-4 ${notification.is_read ? 'bg-white' : 'bg-green-50'} ${notification.is_read ? 'border-transparent' : 'border-taskora-green-500'}`}>
            <div className="flex items-start">
                <div className="flex-shrink-0 pt-1">
                    <Bell className={`w-5 h-5 ${notification.is_read ? 'text-gray-400' : 'text-taskora-green-700'}`} />
                </div>
                <div className="ml-3 w-0 flex-1">
                    <p className="text-sm font-semibold text-gray-900">{notification.title}</p>
                    <p className="mt-1 text-sm text-gray-600">{notification.message}</p>
                    <p className="mt-1 text-xs text-gray-400">
                        {new Date(notification.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}
                    </p>
                </div>
            </div>
        </div>
    );
};

const NotificationsPage = () => {
    const { loading, error, callApi } = useApi();
    const [notifications, setNotifications] = useState([]);

    const fetchNotifications = useCallback(async () => {
        const result = await callApi('notifications_get_list.php');
        if (result && result.status === 'success') {
            setNotifications(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchNotifications();
    }, [fetchNotifications]);

    const markAllAsRead = async () => {
        const result = await callApi('user_mark_notification_read.php', 'POST', { notification_id: 'all' });
        if (result && result.status === 'success') {
            fetchNotifications(); // Muat ulang daftar
        }
    };

    // Logika untuk mengecek apakah ada notifikasi yang belum dibaca
    const hasUnread = notifications.some(n => !n.is_read);

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <Link to="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                    <ArrowLeft size={20} />
                    <h1 className="text-2xl font-bold text-gray-800">Notifikasi</h1>
                </Link>
                {/* Tombol sekunder yang lebih kecil dan proporsional */}
                <Button 
                    onClick={markAllAsRead} 
                    disabled={loading || !hasUnread} 
                    className="py-2 px-4 text-sm bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 disabled:bg-gray-50 disabled:text-gray-400 flex items-center gap-2"
                >
                    <Check size={16}/> 
                    <span>Tandai Semua Dibaca</span>
                </Button>
            </div>

            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                {loading && <p className="p-4 text-center">Memuat notifikasi...</p>}
                {error && <p className="p-4 text-center text-red-500">{error}</p>}
                
                <div className="divide-y divide-gray-200">
                    {!loading && notifications.length > 0 ? (
                        notifications.map(notif => <NotificationItem key={notif.id} notification={notif} />)
                    ) : (
                        !loading && <p className="p-8 text-center text-gray-500">Tidak ada notifikasi baru.</p>
                    )}
                </div>
            </div>
        </div>
    );
};

export default NotificationsPage;

