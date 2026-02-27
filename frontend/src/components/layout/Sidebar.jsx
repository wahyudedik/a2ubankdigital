import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
    LayoutDashboard, Users, Landmark, LogOut, BarChart, Settings, Database,
    FolderClock, X, ChevronDown, Building, PiggyBank, ShieldCheck, CreditCard // <-- 1. Impor ikon baru
} from 'lucide-react';
import { AppConfig } from '../../config';

const NavLink = ({ to, icon, text, isActive, onClick }) => (
    <Link
        to={to}
        onClick={onClick}
        className={`flex items-center py-3 px-6 my-1 transition-colors duration-200 text-gray-600 hover:${AppConfig.theme.textPrimary} hover:bg-blue-50 ${isActive ? `bg-blue-50 border-r-4 border-bpn-blue ${AppConfig.theme.textPrimary} font-semibold` : ''
            }`}
    >
        {icon}
        <span className="mx-4">{text}</span>
    </Link>
);

const CollapsibleMenu = ({ icon, text, children, isActive }) => {
    const [isOpen, setIsOpen] = React.useState(isActive);

    React.useEffect(() => {
        if (isActive) {
            setIsOpen(true);
        }
    }, [isActive]);

    return (
        <div>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={`flex items-center justify-between w-full py-3 px-6 my-1 transition-colors duration-200 text-gray-600 hover:${AppConfig.theme.textPrimary} hover:bg-blue-50 ${isActive ? `bg-blue-50 ${AppConfig.theme.textPrimary} font-semibold` : ''
                    }`}
            >
                <div className="flex items-center">
                    {icon}
                    <span className="mx-4">{text}</span>
                </div>
                <ChevronDown className={`transform transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`} size={16} />
            </button>
            <div className={`pl-8 overflow-hidden transition-all duration-300 ease-in-out ${isOpen ? 'max-h-screen' : 'max-h-0'}`}>
                {children}
            </div>
        </div>
    );
};

const Sidebar = ({ isOpen, setIsOpen, onLogout, user }) => {
    const location = useLocation();

    const isActive = (path) => {
        if (!path) return false;
        if (path === '/admin/dashboard') {
            return location.pathname === path;
        }
        return location.pathname.startsWith(path);
    };

    const getNavLinks = (roleId) => {
        const allLinks = {
            dashboard: { type: 'link', icon: <LayoutDashboard size={20} />, text: 'Dashboard', path: '/admin/dashboard' },
            customers: { type: 'link', icon: <Users size={20} />, text: 'Nasabah', path: '/admin/customers' },
            // --- PERUBAHAN DI SINI ---
            teller_ops: {
                type: 'group',
                icon: <Landmark size={20} />,
                text: 'Transaksi Teller',
                activePaths: ['/admin/teller-deposit', '/admin/teller-loan-payment'], // <-- Tambah path baru
                subLinks: [
                    { text: 'Setor Tunai', path: '/admin/teller-deposit' },
                    { text: 'Bayar Angsuran', path: '/admin/teller-loan-payment' } // <-- Tambah menu baru
                ]
            },
            // --- AKHIR PERUBAHAN ---
            requests: {
                type: 'group',
                icon: <FolderClock size={20} />,
                text: 'Permintaan',
                activePaths: ['/admin/topup-requests', '/admin/withdrawal-requests', '/admin/card-requests'],
                subLinks: [
                    { text: 'Isi Saldo', path: '/admin/topup-requests' },
                    { text: 'Penarikan Dana', path: '/admin/withdrawal-requests' },
                    { text: 'Request Kartu', path: '/admin/card-requests' },
                ]
            },
            transactions: { type: 'link', icon: <CreditCard size={20} />, text: 'Semua Transaksi', path: '/admin/transactions' },
            loanMgmt: {
                type: 'group',
                icon: <PiggyBank size={20} />,
                text: 'Manajemen Pinjaman',
                activePaths: ['/admin/loan-products', '/admin/loan-applications', '/admin/loan-accounts'],
                subLinks: [
                    { text: 'Produk Pinjaman', path: '/admin/loan-products' },
                    { text: 'Pengajuan Baru', path: '/admin/loan-applications' },
                    { text: 'Daftar Pinjaman', path: '/admin/loan-accounts' },
                ]
            },
            depositMgmt: {
                type: 'group',
                icon: <Database size={20} />,
                text: 'Manajemen Deposito',
                activePaths: ['/admin/deposit-products', '/admin/deposit-accounts'],
                subLinks: [
                    { text: 'Produk Deposito', path: '/admin/deposit-products' },
                    { text: 'Daftar Deposito', path: '/admin/deposit-accounts' },
                ]
            },
            orgStructure: {
                type: 'group',
                icon: <Building size={20} />,
                text: 'Struktur Organisasi',
                activePaths: ['/admin/units', '/admin/staff'],
                subLinks: [
                    { text: 'Manajemen Unit', path: '/admin/units' },
                    { text: 'Manajemen Staf', path: '/admin/staff' },
                ]
            },
            reports: { type: 'link', icon: <BarChart size={20} />, text: 'Laporan', path: '/admin/reports' },
            auditLog: { type: 'link', icon: <ShieldCheck size={20} />, text: 'Log Audit', path: '/admin/audit-log' }
        };

        // --- PERUBAHAN DI SINI ---
        const rolesConfig = {
            1: [allLinks.dashboard, allLinks.customers, allLinks.teller_ops, allLinks.requests, allLinks.transactions, allLinks.loanMgmt, allLinks.depositMgmt, allLinks.orgStructure, allLinks.reports, allLinks.auditLog],
            2: [allLinks.dashboard, allLinks.customers, allLinks.teller_ops, allLinks.requests, allLinks.transactions, allLinks.loanMgmt, allLinks.depositMgmt, allLinks.orgStructure, allLinks.reports, allLinks.auditLog],
            3: [allLinks.dashboard, allLinks.customers, allLinks.teller_ops, allLinks.requests, allLinks.transactions, allLinks.loanMgmt, allLinks.depositMgmt, allLinks.reports],
            5: [allLinks.dashboard, allLinks.customers, allLinks.teller_ops, allLinks.requests],
            6: [allLinks.dashboard, allLinks.customers, allLinks.teller_ops, allLinks.requests],
            7: [allLinks.dashboard, allLinks.loanMgmt],
            8: [allLinks.dashboard, allLinks.reports],
        };
        // --- AKHIR PERUBAHAN ---

        return rolesConfig[roleId] || [allLinks.dashboard];
    };

    const navLinks = user ? getNavLinks(user.roleId) : [];

    return (
        <>
            <div
                className={`fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden ${isOpen ? 'block' : 'hidden'}`}
                onClick={() => setIsOpen(false)}
            ></div>

            <div className={`fixed top-0 left-0 h-full w-64 bg-white shadow-md z-30 transform transition-transform duration-300 ease-in-out md:relative md:translate-x-0 ${isOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex items-center justify-between h-20 border-b px-6">
                    <img src={AppConfig.brand.logo} alt="A2U Bank Digital Logo" className="h-8" />
                    <button onClick={() => setIsOpen(false)} className="md:hidden text-gray-600">
                        <X size={24} />
                    </button>
                </div>
                <div className="flex flex-col justify-between" style={{ height: 'calc(100% - 80px)' }}>
                    <nav className="mt-6 flex-grow overflow-y-auto">
                        {navLinks.map((link) => {
                            const key = link.path || link.text;
                            if (link.type === 'group') {
                                return (
                                    <CollapsibleMenu key={key} icon={link.icon} text={link.text} isActive={link.activePaths.some(p => isActive(p))}>
                                        {link.subLinks.map(sub => (
                                            <NavLink
                                                key={sub.path}
                                                to={sub.path}
                                                icon={sub.icon}
                                                text={sub.text}
                                                isActive={isActive(sub.path)}
                                                onClick={() => setIsOpen(false)}
                                            />
                                        ))}
                                    </CollapsibleMenu>
                                );
                            }
                            return (
                                <NavLink
                                    key={key}
                                    to={link.path}
                                    icon={link.icon}
                                    text={link.text}
                                    isActive={isActive(link.path)}
                                    onClick={() => setIsOpen(false)}
                                />
                            );
                        })}
                    </nav>

                    <div className="border-t">
                        <Link
                            to="/admin/settings"
                            onClick={() => setIsOpen(false)}
                            className={`flex items-center py-4 px-6 w-full text-left text-gray-600 hover:${AppConfig.theme.textPrimary} hover:bg-blue-50 ${isActive('/admin/settings') ? `bg-blue-50 ${AppConfig.theme.textPrimary} font-semibold` : ''
                                }`}
                        >
                            <Settings size={20} />
                            <span className="mx-4">Pengaturan</span>
                        </Link>
                        <button
                            onClick={onLogout}
                            className="flex items-center py-4 px-6 w-full text-left text-gray-600 hover:text-red-600 hover:bg-red-50"
                        >
                            <LogOut size={20} />
                            <span className="mx-4">Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
};

export default Sidebar;

