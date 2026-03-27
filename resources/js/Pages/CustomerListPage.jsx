import React, { useState } from 'react';
import { usePage, router, Link } from '@inertiajs/react';
import { Search, ChevronLeft, ChevronRight, Eye, PlusCircle } from 'lucide-react';
import useNavigate from '@/hooks/useNavigate';
import Button from '@/components/ui/Button';

const CustomerListPage = () => {
    const { customers, pagination, filters } = usePage().props;
    const navigate = useNavigate();
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');

    const handleSearchChange = (e) => {
        const value = e.target.value;
        setSearchTerm(value);
        router.get(window.location.pathname, { search: value, page: 1 }, { preserveState: true });
    };

    const handlePageChange = (newPage) => {
        router.get(window.location.pathname, { search: searchTerm, page: newPage }, { preserveState: true });
    };

    return (
        <div>
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Manajemen Nasabah</h1>
                <div className="w-full md:w-auto flex items-center gap-2">
                    <div className="relative flex-grow">
                        <input
                            type="text"
                            placeholder="Cari nama, email, atau ID..."
                            value={searchTerm}
                            onChange={handleSearchChange}
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-bpn-blue-700"
                        />
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                    </div>
                    <Button
                        onClick={() => navigate('/admin/customers/add')}
                        className="flex items-center gap-2 px-4 py-2"
                    >
                        <PlusCircle size={20} />
                        <span className="hidden sm:inline">Tambah</span>
                    </Button>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-max">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                {['ID Bank', 'Nama Lengkap', 'Email', 'Status', 'Tgl. Daftar', 'Aksi'].map(head =>
                                    <th key={head} className="text-left text-sm font-semibold text-gray-600 px-6 py-3">{head}</th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {!customers || customers.length === 0 ? (
                                <tr><td colSpan="6" className="text-center p-8 text-gray-500">Tidak ada data nasabah ditemukan.</td></tr>
                            ) : (
                                customers.map(customer => (
                                    <tr key={customer.id} className="hover:bg-gray-50">
                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">{customer.bank_id}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{customer.full_name}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{customer.email}</td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${customer.status === 'ACTIVE' ? 'bg-green-100 text-green-800' :
                                                customer.status === 'DORMANT' ? 'bg-yellow-100 text-yellow-800' :
                                                    'bg-red-100 text-red-800'
                                                }`}>
                                                {customer.status}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {new Date(customer.created_at).toLocaleDateString('id-ID')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <div className="flex gap-2">
                                                <Link href={`/admin/customers/${customer.id}`} className="text-gray-500 hover:text-bpn-blue-700">
                                                    <Eye size={18} />
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                <div className="flex justify-between items-center px-6 py-3 border-t border-gray-200">
                    <button onClick={() => handlePageChange((pagination?.current_page || 1) - 1)} disabled={!pagination || pagination.current_page <= 1} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronLeft size={16} /> Sebelumnya
                    </button>
                    <span className="text-sm text-gray-700">
                        Halaman {pagination?.current_page || 0} dari {pagination?.total_pages || 0}
                    </span>
                    <button onClick={() => handlePageChange((pagination?.current_page || 1) + 1)} disabled={!pagination || pagination.current_page >= pagination.total_pages} className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        Berikutnya <ChevronRight size={16} />
                    </button>
                </div>
            </div>
        </div>
    );
};

export default CustomerListPage;
