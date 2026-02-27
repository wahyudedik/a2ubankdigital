import React from 'react';
import { Link } from 'react-router-dom';
import NotificationBell from '../customer/NotificationBell';

const Header = ({ user }) => {
    if (!user) {
        return null; 
    }

    // Mendapatkan inisial dari nama lengkap
    const getInitials = (name) => {
        if (!name) return '?';
        const names = name.split(' ');
        if (names.length > 1) {
            return names[0].charAt(0) + names[1].charAt(0);
        }
        return name.charAt(0);
    };

    return (
        <header className="sticky top-0 bg-gray-50/80 backdrop-blur-md z-10 p-4">
            <div className="flex justify-between items-center">
                <Link to="/profile" className="flex items-center">
                    {/* PERBAIKAN: Avatar dengan warna branding baru */}
                    <div className="w-10 h-10 rounded-full bg-bpn-blue text-white flex items-center justify-center font-bold mr-3">
                        {getInitials(user.fullName)}
                    </div>
                    <div>
                        <p className="text-xs text-gray-500">Selamat Datang,</p>
                        <h2 className="font-bold text-gray-800 text-lg">{user.fullName || 'Pengguna'}</h2>
                    </div>
                </Link>
                <NotificationBell />
            </div>
        </header>
    );
};

export default Header;
