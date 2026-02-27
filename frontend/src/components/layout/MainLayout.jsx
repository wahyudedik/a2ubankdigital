import React from 'react';
import { Outlet } from 'react-router-dom';
// PERBAIKAN: Menggunakan path absolut dari root 'src' untuk mengatasi masalah resolusi path
import Header from '/src/components/layout/Header.jsx';
import BottomNav from '/src/components/layout/BottomNav.jsx';

const MainLayout = ({ user, onLogout }) => {
    return (
        // PERBAIKAN: Menghapus NotificationProvider dari sini karena sudah dipindah ke App.jsx
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-md mx-auto bg-gray-50 flex flex-col">
                <Header user={user} onLogout={onLogout} />
                <main className="flex-grow p-4 pb-24">
                    <Outlet />
                </main>
                <BottomNav />
            </div>
        </div>
    );
};

export default MainLayout;
