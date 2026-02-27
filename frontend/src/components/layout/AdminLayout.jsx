import React, { useState } from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';
import AdminHeader from './AdminHeader';

const AdminLayout = ({ user, onLogout }) => {
    const [isSidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="flex h-screen bg-gray-100">
            {/* PERBAIKAN: Melewatkan prop 'user' ke Sidebar */}
            <Sidebar isOpen={isSidebarOpen} setIsOpen={setSidebarOpen} onLogout={onLogout} user={user} />
            <div className="flex-1 flex flex-col overflow-hidden">
                <AdminHeader user={user} onMenuClick={() => setSidebarOpen(true)} />
                <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
};

export default AdminLayout;
