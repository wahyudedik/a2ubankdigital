import React from 'react';
import { usePage, router } from '@inertiajs/react';
import MainLayout from '@/components/layout/MainLayout';

const CustomerLayout = ({ children }) => {
    const { auth } = usePage().props;

    // Fallback to localStorage if auth not available from server
    let user = auth?.user;
    if (!user) {
        try {
            const stored = localStorage.getItem('authUser');
            user = stored ? JSON.parse(stored) : null;
        } catch (e) { user = null; }
    }

    const handleLogout = () => {
        localStorage.removeItem('authUser');
        router.post('/logout');
    };

    return (
        <MainLayout user={user} onLogout={handleLogout}>
            {children}
        </MainLayout>
    );
};

export default CustomerLayout;
