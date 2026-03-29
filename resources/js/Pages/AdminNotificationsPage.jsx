import React, { useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import useApi from '@/hooks/useApi';
import { Bell, Check } from 'lucide-react';
import Button from '@/components/ui/Button';

const NotificationItem = ({ notification }) => (
    <div className={`p-4 border-l-4 ${notification.is_read ? 'bg-white' : 'bg-blue-50'} ${notification.is_read ? 'border-transparent' : 'border-blue-500'}`}>
        <div className="flex items-start">
            <div className="flex-shrink-0 pt-1"><Bell className={`w-5 h-5 ${notification.is_read ? 'text-gray-400' : 'text-blue-700'}`} /></div>
            <div className="ml-3 w-0 flex-1">
                <p className="text-sm font-semibold text-gray-900">{notification.title}</p>
                <p className="mt-1 text-sm text-gray-600">{notification.message}</p>
                <p className="mt-1 text-xs text-gray-400">{new Date(notification.created_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}</p>
            </div>
        </div>
    </div>
);

const AdminNotificationsPage = () => {
    const { notifications } = usePage().props;
    const { loading, callApi } = useApi();
    const hasUnread = (notifications || []).some(n => !n.is_read);

    useEffect(() => {
        if (hasUnread) {
            callApi('user_mark_notification_read.php', 'PUT', {});
        }
    }, []);

    const markAllAsRead = async () => {
        const result = await callApi('user_mark_notification_read.php', 'PUT', {});
        if (result && result.status === 'success') { router.reload(); }
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Notifikasi Staf</h1>
                <Button onClick={markAllAsRead} disabled={loading} className="py-2 px-4 text-sm"><Check size={16} className="mr-2" /> Tandai Semua Dibaca</Button>
            </div>
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="divide-y divide-gray-200">
                    {(notifications || []).length > 0 ? (notifications || []).map(notif => <NotificationItem key={notif.id} notification={notif} />) : (<p className="p-8 text-center text-gray-500">Tidak ada notifikasi baru.</p>)}
                </div>
            </div>
        </div>
    );
};

export default AdminNotificationsPage;
