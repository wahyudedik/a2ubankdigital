import React from 'react';
import { Link } from '@inertiajs/react';
import { User, Menu } from 'lucide-react';
import AdminNotificationBell from '@/components/admin/AdminNotificationBell';

const AdminHeader = ({ user, onMenuClick }) => {
    const getRoleName = (roleId) => {
        const roles = {
            1: 'Super Admin', 2: 'Kepala Cabang', 3: 'Kepala Unit',
            4: 'Marketing', 5: 'Teller', 6: 'Customer Service', 7: 'Analis Kredit'
        };
        return roles[roleId] || 'Staf';
    };

    return (
        <header className="flex items-center justify-between h-20 px-6 bg-white border-b flex-shrink-0">
            <button onClick={onMenuClick} className="md:hidden text-gray-600">
                <Menu size={24} />
            </button>
            <div className="hidden md:block flex-grow"></div>
            <div className="flex items-center">
                <AdminNotificationBell />
                <Link href="/admin/settings" className="ml-4 flex items-center p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <span className="text-right mr-3 hidden sm:block">
                        <span className="block text-sm font-medium text-gray-700">{user.fullName}</span>
                        <span className="block text-xs text-gray-500">{getRoleName(user.roleId)}</span>
                    </span>
                    <div className="w-10 h-10 rounded-full bg-taskora-green-500 text-white flex items-center justify-center font-bold">
                        {user.fullName.charAt(0)}
                    </div>
                </Link>
            </div>
        </header>
    );
};

export default AdminHeader;
