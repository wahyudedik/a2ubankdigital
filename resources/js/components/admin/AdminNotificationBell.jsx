import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import useApi from '@/hooks/useApi';

const AdminNotificationBell = () => {
    const [unreadCount, setUnreadCount] = useState(0);
    const { callApi } = useApi();

    const fetchNotifications = async () => {
        const result = await callApi('/user/notifications');
        if (result && result.status === 'success') {
            const unreadCount = result.pagination?.unread_count || 0;
            setUnreadCount(unreadCount);
        }
    };

    useEffect(() => {
        fetchNotifications();
        const interval = setInterval(fetchNotifications, 60000);
        return () => clearInterval(interval);
    }, [callApi]);

    // Listen for custom events to update notification count
    useEffect(() => {
        const handleNotificationUpdate = () => {
            fetchNotifications();
        };

        window.addEventListener('notificationsUpdated', handleNotificationUpdate);
        return () => window.removeEventListener('notificationsUpdated', handleNotificationUpdate);
    }, []);

    return (
        <Link href="/admin/notifications" className="relative p-2 rounded-full text-gray-500 hover:bg-gray-100">
            <Bell size={20} />
            {unreadCount > 0 && (
                <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">
                    {unreadCount > 9 ? '9+' : unreadCount}
                </span>
            )}
        </Link>
    );
};

export default AdminNotificationBell;
