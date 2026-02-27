import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { Search, ChevronLeft, ChevronRight, Download, Eye, Loader2 } from 'lucide-react';
import { AppConfig } from '../config';
import AdminTransactionDetailModal from '../components/modals/AdminTransactionDetailModal';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const StatusBadge = ({ status }) => {
    const statusMap = {
        'SUCCESS': 'bg-green-100 text-green-800',
        'PENDING': 'bg-yellow-100 text-yellow-800',
        'FAILED': 'bg-red-100 text-red-800',
        'REVERSED': 'bg-gray-100 text-gray-800',
    };
    return <span className={`px-2 py-1 text-xs font-semibold rounded-full ${statusMap[status] || 'bg-gray-100'}`}>{status}</span>;
};


const TransactionListPage = () => {
    const { loading, error, callApi } = useApi();
    const [exporting, setExporting] = useState(false);
    const [transactions, setTransactions] = useState([]);
    const [pagination, setPagination] = useState({});
    const [page, setPage] = useState(1);
    const [searchTerm, setSearchTerm] = useState('');
    const [typeFilter, setTypeFilter] = useState('');

    const { loading: detailLoading, callApi: callDetailApi } = useApi();
    const [selectedTx, setSelectedTx] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    
    const transactionTypes = [
        'TRANSFER_INTERNAL', 'PEMBAYARAN_TAGIHAN', 'SETOR_TUNAI', 'TARIK_TUNAI', 
        'PENCAIRAN_PINJAMAN', 'BAYAR_CICILAN', 'PEMBUKAAN_DEPOSITO', 
        'PENCAIRAN_DEPOSITO', 'BIAYA_ADMIN'
    ];

    const fetchTransactions = useCallback(async (currentPage, search, type) => {
        const result = await callApi(`admin_get_transactions.php?page=${currentPage}&limit=15&search=${encodeURIComponent(search)}&type=${type}`);
        if (result && result.status === 'success') {
            setTransactions(result.data);
            setPagination(result.pagination);
        }
    }, [callApi]);

    useEffect(() => {
        const handler = setTimeout(() => {
            fetchTransactions(page, searchTerm, typeFilter);
        }, 500);
        return () => clearTimeout(handler);
    }, [page, searchTerm, typeFilter, fetchTransactions]);
    
    const handleExport = async () => {
        setExporting(true);
        // ... (logika ekspor tetap sama)
        setExporting(false);
    };

    const handleViewDetail = async (txId) => {
        setIsModalOpen(true);
        const result = await callDetailApi(`admin_get_transaction_detail.php?id=${txId}`);
        if (result && result.status === 'success') {
            setSelectedTx(result.data);
        }
    };

    return (
        <div>
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Riwayat Transaksi</h1>
                <div className="w-full md:w-auto flex flex-col md:flex-row items-stretch gap-2">
                    <div className="relative flex-grow">
                        <input 
                            type="text" 
                            placeholder="Cari nama, kode trx, deskripsi..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border rounded-lg"
                        />
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20}/>
                    </div>
                    <select value={typeFilter} onChange={(e) => setTypeFilter(e.target.value)} className="p-2 border rounded-lg bg-gray-50">
                        <option value="">Semua Jenis</option>
                        {transactionTypes.map(type => 
                            <option key={type} value={type}>{type.replace(/_/g, ' ')}</option>
                        )}
                    </select>
                    <button 
                        onClick={handleExport} 
                        disabled={exporting}
                        className="flex items-center justify-center gap-2 px-4 py-2 bg-white border text-gray-700 rounded-lg hover:bg-gray-100 disabled:opacity-50"
                    >
                        <Download size={18} />
                        <span className="hidden sm:inline">{exporting ? 'Mengekspor...' : 'Ekspor'}</span>
                    </button>
                </div>
            </div>

            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                {['Tanggal', 'Jenis', 'Dari', 'Ke', 'Jumlah', 'Status', 'Aksi'].map(head => 
                                    <th key={head} className="p-4 text-left text-sm font-semibold text-gray-600">{head}</th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {loading && <tr><td colSpan="7" className="p-8 text-center"><Loader2 className="animate-spin inline-block"/></td></tr>}
                            {error && <tr><td colSpan="7" className="p-8 text-center text-red-500">{error}</td></tr>}
                            {!loading && transactions.map(tx => (
                                <tr key={tx.id}>
                                    <td className="p-4 text-sm text-gray-600 whitespace-nowrap">{new Date(tx.created_at).toLocaleString('id-ID')}</td>
                                    <td className="p-4 text-sm font-medium">{tx.transaction_type.replace(/_/g, ' ')}</td>
                                    <td className="p-4 text-sm">{tx.from_name || '-'}</td>
                                    <td className="p-4 text-sm">{tx.to_name || '-'}</td>
                                    <td className="p-4 font-semibold">{formatCurrency(tx.amount)}</td>
                                    <td className="p-4"><StatusBadge status={tx.status} /></td>
                                    <td className="p-4"><button onClick={() => handleViewDetail(tx.id)} className="p-2 text-gray-500 hover:bg-gray-100 rounded-full"><Eye size={18}/></button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {pagination && pagination.total_pages > 1 && (
                    <div className="flex justify-between items-center p-4 border-t">
                        <button onClick={() => setPage(p => p - 1)} disabled={page <= 1} className="px-3 py-1 border rounded-lg disabled:opacity-50 flex items-center gap-1"><ChevronLeft size={16}/> Sebelumnya</button>
                        <span>Halaman {pagination.current_page} dari {pagination.total_pages}</span>
                        <button onClick={() => setPage(p => p + 1)} disabled={page >= pagination.total_pages} className="px-3 py-1 border rounded-lg disabled:opacity-50 flex items-center gap-1">Berikutnya <ChevronRight size={16}/></button>
                    </div>
                )}
            </div>
            
            {isModalOpen && <AdminTransactionDetailModal transaction={selectedTx} isLoading={detailLoading} onClose={() => setIsModalOpen(false)} />}
        </div>
    );
};

export default TransactionListPage;
