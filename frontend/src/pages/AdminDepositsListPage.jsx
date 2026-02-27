import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { Database, Search, Clock, CalendarCheck2, TrendingUp } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const StatCard = ({ icon, title, value, isLoading }) => (
    <div className="bg-white p-4 rounded-lg shadow-sm border">
        {isLoading ? (
            <div className="animate-pulse flex items-center space-x-3">
                <div className="rounded-full bg-gray-200 h-10 w-10"></div>
                <div className="flex-1 space-y-2 py-1">
                    <div className="h-2 bg-gray-200 rounded w-3/4"></div>
                    <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
        ) : (
            <div className="flex items-center">
                <div className="p-2 bg-taskora-green-100 text-taskora-green-700 rounded-full mr-3">
                    {icon}
                </div>
                <div>
                    <p className="text-xs font-medium text-gray-500">{title}</p>
                    <p className="text-lg font-bold text-gray-800">{value}</p>
                </div>
            </div>
        )}
    </div>
);

const AdminDepositsListPage = () => {
    const { loading, error, callApi } = useApi();
    const [deposits, setDeposits] = useState([]);
    const [summary, setSummary] = useState({});
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('active');

    const fetchDeposits = useCallback(async (search, status) => {
        const result = await callApi(`admin_get_customer_deposits.php?search=${search}&status=${status}`);
        if (result && result.status === 'success') {
            // PERBAIKAN: Mengakses properti yang benar dari respons API
            setDeposits(result.data.deposits || []);
            setSummary(result.data.summary || {});
        }
    }, [callApi]);

    useEffect(() => {
        // Debounce search term
        const handler = setTimeout(() => {
            fetchDeposits(searchTerm, statusFilter);
        }, 500);
        return () => clearTimeout(handler);
    }, [searchTerm, statusFilter, fetchDeposits]);

    const kpiCards = [
        { icon: <TrendingUp size={20} />, title: "Total Dana Aktif", value: formatCurrency(summary.totalActiveBalance || 0) },
        { icon: <Database size={20} />, title: "Jumlah Deposito", value: (summary.totalDeposits || 0).toLocaleString('id-ID') },
        { icon: <CalendarCheck2 size={20} />, title: "Jatuh Tempo Bulan Ini", value: (summary.maturingThisMonth || 0).toLocaleString('id-ID') },
    ];
    
    const filterButtons = [
        { label: 'Aktif', value: 'active' },
        { label: 'Segera Jatuh Tempo', value: 'near_maturity' },
        { label: 'Telah Jatuh Tempo', value: 'matured' }
    ];

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Dasbor Deposito Nasabah</h1>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                {kpiCards.map((card, index) => (
                    <StatCard key={index} {...card} isLoading={loading && !summary.totalActiveBalance} />
                ))}
            </div>

            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="p-4 flex flex-col md:flex-row gap-4">
                    <div className="relative flex-grow">
                        <input
                            type="text"
                            placeholder="Cari nama atau no. rekening..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-taskora-green-300"
                        />
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                    </div>
                    <div className="flex items-center gap-2">
                        {filterButtons.map(btn => (
                            <button
                                key={btn.value}
                                onClick={() => setStatusFilter(btn.value)}
                                className={`px-3 py-1.5 text-sm rounded-md transition-colors ${statusFilter === btn.value ? 'bg-taskora-green-700 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`}
                            >
                                {btn.label}
                            </button>
                        ))}
                    </div>
                </div>
                {error && <p className="text-red-500 px-4 pb-4">{error}</p>}
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Nasabah</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">No. Rekening</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Produk</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Pokok</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Keuntungan</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Jatuh Tempo</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {loading && deposits.length === 0 ? (
                                <tr><td colSpan="6" className="p-8 text-center text-gray-500">Memuat...</td></tr>
                            ) : deposits.length > 0 ? deposits.map(d => (
                                <tr key={d.id} className={d.is_near_maturity ? 'bg-yellow-50' : ''}>
                                    <td className="p-4 font-medium">{d.customer_name}</td>
                                    <td className="p-4 text-sm text-gray-600 font-mono">{d.account_number}</td>
                                    <td className="p-4 text-sm text-gray-600">{d.product_name}</td>
                                    <td className="p-4 text-sm text-gray-600">{formatCurrency(d.balance)}</td>
                                    <td className="p-4 text-sm text-green-600 font-semibold">{formatCurrency(d.interest_earned)}</td>
                                    <td className="p-4 text-sm text-gray-600 flex items-center gap-2">
                                        {d.is_near_maturity && <Clock size={14} className="text-yellow-600" />}
                                        {new Date(d.maturity_date).toLocaleDateString('id-ID')}
                                    </td>
                                </tr>
                            )) : (
                                <tr><td colSpan="6" className="p-8 text-center text-gray-500">Tidak ada data deposito ditemukan.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default AdminDepositsListPage;

