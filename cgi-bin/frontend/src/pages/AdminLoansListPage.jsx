import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { Link } from 'react-router-dom';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { DollarSign, FileText, AlertTriangle, Search, User, Tag, Clock, ChevronLeft, ChevronRight } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatDate = (dateString) => dateString ? format(parseISO(dateString), 'dd MMM yyyy', { locale: id }) : '-';

const AdminLoansListPage = () => {
    const { callApi, loading, error } = useApi(); 
    
    const [loans, setLoans] = useState([]);
    const [summary, setSummary] = useState({});
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('disbursed');
    
    const [currentPage, setCurrentPage] = useState(1);
    const [pagination, setPagination] = useState(null);

    const fetchLoans = useCallback(async (search, status, page) => {
        try {
            const result = await callApi(`admin_get_all_loans.php?search=${search}&status=${status}&page=${page}`);
            if (result && result.status === 'success') {
                setLoans(result.data || []);
                setSummary(result.summary || {});
                setPagination(result.pagination || null);
            }
        } catch (err) {
            console.error(err);
        }
    }, [callApi]);

    // --- PERBAIKAN LOGIKA: Pisahkan efek untuk reset halaman dan fetch data ---

    // Efek ini HANYA untuk mereset halaman ke 1 JIKA filter atau pencarian berubah
    useEffect(() => {
        setCurrentPage(1);
    }, [searchTerm, statusFilter]);

    // Efek ini untuk mengambil data setiap kali ada perubahan pada filter, pencarian, atau halaman
    useEffect(() => {
        const handler = setTimeout(() => {
            fetchLoans(searchTerm, statusFilter, currentPage);
        }, 300); // Sedikit debounce untuk pengalaman pengguna yang lebih baik
        return () => clearTimeout(handler);
    }, [fetchLoans, searchTerm, statusFilter, currentPage]);
    
    // --- AKHIR PERBAIKAN LOGIKA ---

    const handleNextPage = () => {
        if (pagination && currentPage < pagination.total_pages) {
            setCurrentPage(currentPage + 1);
        }
    };

    const handlePrevPage = () => {
        if (currentPage > 1) {
            setCurrentPage(currentPage - 1);
        }
    };

    const StatCard = ({ icon, title, value, color }) => (
        <div className="bg-white p-6 rounded-lg shadow flex items-start justify-between">
            <div>
                <p className="text-sm font-medium text-gray-500">{title}</p>
                <p className="text-2xl font-bold text-gray-800 mt-1">{value}</p>
            </div>
            <div className={`p-3 rounded-full ${color}`}>
                {icon}
            </div>
        </div>
    );

    return (
        <div className="p-4 md:p-6 bg-gray-50 min-h-screen">
            <h1 className="text-3xl font-bold text-gray-800 mb-6">Dasbor Pinjaman Aktif</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <StatCard 
                    icon={<DollarSign size={24} className="text-green-600"/>} 
                    title="Total Portofolio Aktif" 
                    value={formatCurrency(summary.totalActiveLoans || 0)}
                    color="bg-green-100"
                />
                <StatCard 
                    icon={<FileText size={24} className="text-blue-600"/>} 
                    title="Jumlah Pinjaman Aktif" 
                    value={summary.activeLoansCount || 0}
                    color="bg-blue-100"
                />
                <StatCard 
                    icon={<AlertTriangle size={24} className="text-red-600"/>} 
                    title="Pinjaman Menunggak" 
                    value={summary.overdueLoansCount || 0}
                    color="bg-red-100"
                />
            </div>
            
            <div className="bg-white rounded-lg shadow-md p-4">
                <div className="flex flex-col md:flex-row gap-4 mb-4">
                    <div className="relative flex-grow">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                        <input 
                            type="text"
                            placeholder="Cari nama nasabah atau ID pinjaman..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <select 
                        value={statusFilter}
                        onChange={(e) => setStatusFilter(e.target.value)}
                        className="border rounded-lg px-4 py-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="disbursed">Aktif</option>
                        <option value="overdue">Menunggak</option>
                        <option value="completed">Lunas</option>
                    </select>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nasabah</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk Pinjaman</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pokok Pinjaman</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa Pokok</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jatuh Tempo Berikutnya</th>
                                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {loading ? (
                                <tr><td colSpan="7" className="p-8 text-center text-gray-500">Memuat data pinjaman...</td></tr>
                            ) : error ? (
                                <tr><td colSpan="7" className="p-8 text-center text-red-500">{error}</td></tr>
                            ) : loans.length > 0 ? loans.map(loan => (
                                <tr key={loan.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="flex items-center">
                                            <div className="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-gray-100 rounded-full">
                                                <User size={20} className="text-gray-500"/>
                                            </div>
                                            <div className="ml-4">
                                                <div className="text-sm font-medium text-gray-900">{loan.customer_name}</div>
                                                <div className="text-sm text-gray-500">ID Pinj: {loan.id}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <div className="flex items-center">
                                            <Tag size={14} className="mr-2 text-gray-400"/>
                                            {loan.product_name || 'N/A'}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{formatCurrency(loan.loan_amount)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-800">{formatCurrency(loan.outstanding_principal)}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <div className="flex items-center">
                                            <Clock size={14} className="mr-2 text-gray-400"/>
                                            {formatDate(loan.next_due_date)}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-center">
                                        {loan.overdue_installments_count > 0 ? (
                                            <span className="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Menunggak
                                            </span>
                                        ) : (
                                            <span className="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Lancar
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <Link to={`/admin/loan-applications/${loan.id}`} className="text-indigo-600 hover:text-indigo-900 font-medium">
                                            Lihat Detail
                                        </Link>
                                    </td>
                                </tr>
                            )) : (
                                <tr><td colSpan="7" className="p-8 text-center text-gray-500">Tidak ada data pinjaman yang cocok dengan filter.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {pagination && pagination.total_records > 0 && (
                    <div className="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                        <div className="flex-1 flex justify-between sm:hidden">
                            <button onClick={handlePrevPage} disabled={currentPage === 1} className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                                Sebelumnya
                            </button>
                            <button onClick={handleNextPage} disabled={!pagination || currentPage === pagination.total_pages} className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                                Berikutnya
                            </button>
                        </div>
                        <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p className="text-sm text-gray-700">
                                    Halaman <span className="font-medium">{pagination.current_page}</span> dari <span className="font-medium">{pagination.total_pages}</span> ({pagination.total_records} total data)
                                </p>
                            </div>
                            <div>
                                <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <button onClick={handlePrevPage} disabled={currentPage === 1} className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                        <ChevronLeft className="h-5 w-5" />
                                    </button>
                                    <button onClick={handleNextPage} disabled={!pagination || currentPage === pagination.total_pages} className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                        <ChevronRight className="h-5 w-5" />
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default AdminLoansListPage;

