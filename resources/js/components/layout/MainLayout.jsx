import React from 'react';
import Header from '@/components/layout/Header.jsx';
import BottomNav from '@/components/layout/BottomNav.jsx';

const MainLayout = ({ user, onLogout, children }) => {
    return (
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-md mx-auto bg-gray-50 flex flex-col">
                <Header user={user} onLogout={onLogout} />
                <main className="flex-grow p-4 pb-24">
                    {children}
                </main>
                <BottomNav />
            </div>
        </div>
    );
};

export default MainLayout;
