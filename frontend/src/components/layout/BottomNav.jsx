import React from 'react';
import { NavLink } from 'react-router-dom';
import { Home, BarChart2, Wallet, User, TrendingUp } from 'lucide-react';

const BottomNav = () => {
    const navItems = [
        { path: '/dashboard', icon: <Home />, label: 'Beranda' },
        { path: '/history', icon: <BarChart2 />, label: 'Riwayat' },
        { path: '/payment', icon: <Wallet />, label: 'Bayar' },
        { path: '/investments', icon: <TrendingUp />, label: 'Investasi' },
        { path: '/profile', icon: <User />, label: 'Profil' },
    ];

    // PERBAIKAN: Menggunakan warna branding BPN Syariah
    const activeLinkStyle = `text-bpn-blue`;
    const inactiveLinkStyle = `text-gray-500 hover:text-bpn-blue`;

    return (
        <nav className="fixed bottom-0 left-0 right-0 bg-white shadow-[0_-2px_10px_rgba(0,0,0,0.1)] z-10">
            <div className="flex justify-around max-w-md mx-auto">
                {navItems.map((item) => (
                    <NavLink
                        key={item.path}
                        to={item.path}
                        className={({ isActive }) => 
                            `flex flex-col items-center justify-center w-full pt-2 pb-1 transition-colors duration-200 ${isActive ? activeLinkStyle : inactiveLinkStyle}`
                        }
                    >
                        {item.icon}
                        <span className="text-xs mt-1">{item.label}</span>
                    </NavLink>
                ))}
            </div>
        </nav>
    );
};

export default BottomNav;
