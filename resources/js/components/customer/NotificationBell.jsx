import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import useApi from '@/hooks/useApi';

const NotificationBell = () => {
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
        <Link href="/notifications" className="relative p-2 rounded-full bg-white shadow-sm text-gray-600">
            <Bell size={20} />
            {unreadCount > 0 && (
                <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-xs font-bold">
                    {unreadCount}
                </span>
            )}
        </Link>
    );
};

export default NotificationBell;
