import React from 'react';
import { usePage, router } from '@inertiajs/react';
import MainLayout from '@/components/layout/MainLayout';
import WhatsAppFloat from '@/components/ui/WhatsAppFloat';

const AuthenticatedLayout = ({ children }) => {
    const { auth } = usePage().props;

    let user = auth?.user;
    if (!user) {
        try {
            const stored = localStorage.getItem('authUser');
            user = stored ? JSON.parse(stored) : null;
        } catch (e) {
            user = null;
        }
    }

    const handleLogout = () => {
        localStorage.removeItem('authUser');
        router.post('/logout');
    };

    return (
        <MainLayout user={user} onLogout={handleLogout}>
            {children}
            <WhatsAppFloat />
        </MainLayout>
    );
};

export default AuthenticatedLayout;
