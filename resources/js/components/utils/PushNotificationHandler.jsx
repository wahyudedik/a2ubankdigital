import React from 'react';
import { useNotification } from '@/contexts/NotificationContext.jsx';
import { Bell, BellOff } from 'lucide-react';
import Button from '@/components/ui/Button.jsx';

const PushNotificationHandler = () => {
    const { notificationStatus, subscribeToNotifications } = useNotification();
    if (notificationStatus === 'granted') return (<div className="flex items-center gap-2 text-green-600 text-sm"><Bell size={16} /><span>Notifikasi push aktif</span></div>);
    if (notificationStatus === 'denied') return (<div className="flex items-center gap-2 text-red-500 text-sm"><BellOff size={16} /><span>Notifikasi diblokir. Aktifkan di pengaturan browser.</span></div>);
    if (notificationStatus === 'unsupported') return (<div className="text-gray-500 text-sm">Browser tidak mendukung notifikasi push.</div>);
    return (<Button onClick={subscribeToNotifications} variant="outline" className="text-sm">Aktifkan Notifikasi Push</Button>);
};
export default PushNotificationHandler;
