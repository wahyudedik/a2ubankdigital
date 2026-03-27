import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import useApi from '@/hooks/useApi';

const AdminNotificationBell = () => {
    const [unreadCount, setUnreadCount] = useState(0);
    const { callApi } = useApi();

    useEffect(() => {
        const fetchNotifications = async () => {
            const result = await callApi('notifications_get_list.php');
            if (result && result.status === 'success') {
                const unread = result.data.filter(n => !n.is_read).length;
                setUnreadCount(unread);
            }
        };

        fetchNotifications();
        const interval = setInterval(fetchNotifications, 60000);
        return () => clearInterval(interval);
    }, [callApi]);

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
