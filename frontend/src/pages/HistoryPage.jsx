import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { ArrowLeft, Filter, ArrowDown, ArrowUp, Briefcase } from 'lucide-react';
import Button from '../components/ui/Button';
import TransactionDetailModal from '../components/modals/TransactionDetailModal';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });

const TransactionIcon = ({ flow }) => {
    const iconProps = { size: 20, className: "text-white" };
    const isCredit = flow === 'KREDIT';
    const icon = isCredit ? <ArrowDown {...iconProps} /> : <ArrowUp {...iconProps} />;
    const bgColor = isCredit ? 'bg-green-600' : 'bg-bpn-red';

    return <div className={`w-10 h-10 rounded-full ${bgColor} flex items-center justify-center mr-4`}>{icon}</div>;
};


const HistoryPage = () => {
    const { loading, error, callApi } = useApi();
    const [transactions, setTransactions] = useState([]);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [filters, setFilters] = useState({ startDate: '', endDate: '', type: '' });
    const [showFilters, setShowFilters] = useState(false);
    
    const [selectedTx, setSelectedTx] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const { callApi: callDetailApi } = useApi();

    const fetchTransactions = useCallback(async (currentPage, currentFilters, loadMore = false) => {
        let endpoint = `user_get_transaction_history.php?page=${currentPage}&limit=15`;
        if (currentFilters.startDate && currentFilters.endDate) {
            endpoint += `&start_date=${currentFilters.startDate}&end_date=${currentFilters.endDate}`;
        }
        if (currentFilters.type) {
            endpoint += `&type=${currentFilters.type}`;
        }

        const result = await callApi(endpoint);
        if (result && result.status === 'success') {
            setTransactions(prev => loadMore ? [...prev, ...result.data] : result.data);
            setHasMore(result.pagination.has_more);
        }
    }, [callApi]);

    useEffect(() => {
        fetchTransactions(1, filters);
    }, [filters, fetchTransactions]);

    const handleLoadMore = () => {
        const nextPage = page + 1;
        setPage(nextPage);
        fetchTransactions(nextPage, filters, true);
    };

    const handleFilterChange = (e) => {
        const { name, value } = e.target;
        setFilters(prev => ({ ...prev, [name]: value }));
    };
    
    const handleApplyFilters = () => {
        setPage(1);
        setTransactions([]);
        fetchTransactions(1, filters);
    };

    const handleViewDetail = async (txId) => {
        setIsModalOpen(true);
        const result = await callDetailApi(`user_get_transaction_detail.php?id=${txId}`);
        if (result && result.status === 'success') {
            setSelectedTx(result.data);
        } else {
            setIsModalOpen(false);
        }
    };

    return (
        <>
            <div>
                <div className="flex justify-between items-center mb-6">
                    <div className="flex items-center gap-2">
                        <Link to="/dashboard" className="text-gray-600 hover:text-gray-900"><ArrowLeft size={20} /></Link>
                        <h1 className="text-2xl font-bold text-gray-800">Riwayat Transaksi</h1>
                    </div>
                    <button onClick={() => setShowFilters(!showFilters)} className="p-2 rounded-full hover:bg-gray-100">
                        <Filter size={20} />
                    </button>
                </div>

                {showFilters && (
                    <div className="bg-white p-4 rounded-lg shadow-md mb-6 space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="date" name="startDate" value={filters.startDate} onChange={handleFilterChange} className="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-bpn-blue/50"/>
                            <input type="date" name="endDate" value={filters.endDate} onChange={handleFilterChange} className="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-bpn-blue/50"/>
                        </div>
                        <select name="type" value={filters.type} onChange={handleFilterChange} className="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-bpn-blue/50">
                            <option value="">Semua Jenis</option>
                            <option value="TRANSFER_INTERNAL">Transfer</option>
                            <option value="BAYAR_CICILAN">Bayar Pinjaman</option>
                            <option value="PEMBELIAN_PRODUK">Pembelian</option>
                            <option value="SETOR_TUNAI">Setor Tunai</option>
                            <option value="TARIK_TUNAI">Tarik Tunai</option>
                        </select>
                        <Button onClick={handleApplyFilters} fullWidth>Terapkan Filter</Button>
                    </div>
                )}

                <div className="space-y-3">
                    {transactions.map(tx => (
                        <div key={tx.id} onClick={() => handleViewDetail(tx.id)} className="bg-white rounded-lg shadow-md p-4 flex items-center cursor-pointer hover:bg-gray-50 transition-colors">
                            <TransactionIcon flow={tx.flow} />
                            <div className="flex-grow">
                                <p className="font-semibold text-gray-800">{tx.description}</p>
                                <p className="text-xs text-gray-500">{formatDate(tx.created_at)}</p>
                            </div>
                            <p className={`font-bold ${tx.flow === 'KREDIT' ? 'text-green-600' : 'text-bpn-red'}`}>
                                {tx.flow === 'KREDIT' ? '+' : '-'}{formatCurrency(tx.amount)}
                            </p>
                        </div>
                    ))}
                </div>

                {loading && page === 1 && <p className="text-center mt-4">Memuat...</p>}
                {error && <p className="text-center mt-4 text-bpn-red">{error}</p>}
                
                {hasMore && !loading && (
                    <div className="mt-6">
                        <Button onClick={handleLoadMore} fullWidth className="bg-gray-200 text-gray-800 hover:bg-gray-300">
                            Muat Lebih Banyak
                        </Button>
                    </div>
                )}
            </div>

            {isModalOpen && (
                <TransactionDetailModal 
                    transaction={selectedTx} 
                    onClose={() => setIsModalOpen(false)} 
                />
            )}
        </>
    );
};

export default HistoryPage;
