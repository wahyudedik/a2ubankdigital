import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useApi from '@/hooks/useApi';
import { ArrowLeft, Filter, ArrowDown, ArrowUp, Download, Landmark, Banknote, Wallet, Eye, EyeOff } from 'lucide-react';
import Button from '@/components/ui/Button';
import TransactionDetailModal from '@/components/modals/TransactionDetailModal';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatDate = (dateString) => new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });

const TRANSACTION_TYPE_LABELS = {
    TRANSFER_INTERNAL: 'Transfer Internal',
    LOAN_PAYMENT: 'Bayar Angsuran',
    LOAN_DISBURSEMENT: 'Pencairan Pinjaman',
    DEPOSIT_PLACEMENT: 'Penempatan Deposito',
    DEPOSIT_DISBURSEMENT: 'Pencairan Deposito',
    TOPUP: 'Isi Saldo',
    WITHDRAWAL: 'Penarikan',
    BILL_PAYMENT: 'Bayar Tagihan',
    EWALLET_TRANSFER: 'Transfer E-Wallet',
    QR_PAYMENT: 'Pembayaran QR',
    GOAL_SAVINGS: 'Tabungan Tujuan',
    INVESTMENT: 'Investasi',
    LOYALTY_REDEMPTION: 'Penukaran Poin',
    TRANSFER_EXTERNAL: 'Transfer Eksternal',
};

const TOPUP_STATUS = {
    pending: { label: 'Menunggu', className: 'bg-yellow-100 text-yellow-800' },
    approved: { label: 'Disetujui', className: 'bg-green-100 text-green-800' },
    rejected: { label: 'Ditolak', className: 'bg-red-100 text-red-800' },
};

const WITHDRAWAL_STATUS = {
    pending: { label: 'Menunggu', className: 'bg-yellow-100 text-yellow-800' },
    approved: { label: 'Disetujui', className: 'bg-blue-100 text-blue-800' },
    rejected: { label: 'Ditolak', className: 'bg-red-100 text-red-800' },
    completed: { label: 'Selesai', className: 'bg-green-100 text-green-800' },
};

const LOAN_STATUS = {
    SUBMITTED: { label: 'Diajukan', className: 'bg-yellow-100 text-yellow-800' },
    APPROVED: { label: 'Disetujui', className: 'bg-blue-100 text-blue-800' },
    REJECTED: { label: 'Ditolak', className: 'bg-red-100 text-red-800' },
    DISBURSED: { label: 'Aktif', className: 'bg-green-100 text-green-800' },
    ACTIVE: { label: 'Aktif', className: 'bg-green-100 text-green-800' },
    COMPLETED: { label: 'Lunas', className: 'bg-gray-100 text-gray-600' },
};

const StatusBadge = ({ status, map }) => {
    const badge = map[status] || { label: status, className: 'bg-gray-100 text-gray-600' };
    return <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${badge.className}`}>{badge.label}</span>;
};

const TransactionIcon = ({ flow }) => {
    const isCredit = flow === 'Kredit';
    return (
        <div className={`w-10 h-10 rounded-full flex-shrink-0 ${isCredit ? 'bg-green-600' : 'bg-red-500'} flex items-center justify-center mr-4`}>
            {isCredit ? <ArrowDown size={20} className="text-white" /> : <ArrowUp size={20} className="text-white" />}
        </div>
    );
};

const HistoryPage = () => {
    const { transactions, pagination, filters: serverFilters, withdrawalRequests, loanApplications, topupRequests } = usePage().props;
    const [activeTab, setActiveTab] = useState('transaksi');
    const [filters, setFilters] = useState({ startDate: serverFilters?.startDate || '', endDate: serverFilters?.endDate || '', type: serverFilters?.type || '' });
    const [showFilters, setShowFilters] = useState(false);
    const [selectedTx, setSelectedTx] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [showBalance, setShowBalance] = useState(true);
    const { callApi: callDetailApi } = useApi();

    const toggleBalanceVisibility = () => {
        setShowBalance(!showBalance);
    };

    const handleFilterChange = (e) => { const { name, value } = e.target; setFilters(prev => ({ ...prev, [name]: value })); };

    const handleApplyFilters = () => {
        router.get(window.location.pathname, { ...filters, page: 1 }, { preserveState: true });
    };

    const handleLoadMore = () => {
        const nextPage = (pagination?.current_page || 1) + 1;
        router.get(window.location.pathname, { ...filters, page: nextPage }, { preserveState: true, preserveScroll: true });
    };

    const handleViewDetail = async (txId) => {
        setIsModalOpen(true);
        const result = await callDetailApi(`/user/transactions/${txId}`);
        if (result && result.status === 'success') { setSelectedTx(result.data); } else { setIsModalOpen(false); }
    };

    const exportToCSV = async () => {
        const params = new URLSearchParams();
        if (filters.startDate) params.append('start_date', filters.startDate);
        if (filters.endDate) params.append('end_date', filters.endDate);
        if (filters.type) params.append('type', filters.type);
        params.append('export', '1');

        const result = await callDetailApi(`user_get_transactions.php?${params.toString()}`);
        const rows = result?.data || result?.transactions || [];

        const header = ['Tanggal', 'Deskripsi', 'Jenis', 'Debit/Kredit', 'Jumlah'];
        const csvRows = [
            header.join(','),
            ...rows.map(tx => [
                `"${formatDate(tx.created_at)}"`,
                `"${(tx.description || '').replace(/"/g, '""')}"`,
                `"${TRANSACTION_TYPE_LABELS[tx.transaction_type] || tx.transaction_type || ''}"`,
                `"${tx.flow}"`,
                tx.amount,
            ].join(',')),
        ];

        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'riwayat-transaksi.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    const tabs = [
        { key: 'transaksi', label: 'Transaksi' },
        { key: 'isi-saldo', label: 'Isi Saldo' },
        { key: 'penarikan', label: 'Penarikan' },
        { key: 'pinjaman', label: 'Pinjaman' },
    ];

    return (
        <>
            <div>
                {/* Header */}
                <div className="flex justify-between items-center mb-4">
                    <div className="flex items-center gap-2">
                        <Link href="/dashboard" className="text-gray-600 hover:text-gray-900"><ArrowLeft size={20} /></Link>
                        <h1 className="text-2xl font-bold text-gray-800">Riwayat</h1>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={toggleBalanceVisibility}
                            className="p-2 rounded-full hover:bg-gray-100 text-gray-600 hover:text-gray-900"
                            title={showBalance ? 'Sembunyikan saldo' : 'Tampilkan saldo'}
                        >
                            {showBalance ? <EyeOff size={20} /> : <Eye size={20} />}
                        </button>
                        {activeTab === 'transaksi' && (
                            <>
                                <button onClick={exportToCSV} className="flex items-center gap-1 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <Download size={16} /> Ekspor
                                </button>
                                <button onClick={() => setShowFilters(!showFilters)} className="p-2 rounded-full hover:bg-gray-100">
                                    <Filter size={20} />
                                </button>
                            </>
                        )}
                    </div>
                </div>

                {/* Tabs */}
                <div className="flex border-b border-gray-200 mb-4">
                    {tabs.map(tab => (
                        <button
                            key={tab.key}
                            onClick={() => setActiveTab(tab.key)}
                            className={`flex-1 py-2.5 text-sm font-medium border-b-2 transition-colors ${activeTab === tab.key
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700'
                                }`}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Tab: Transaksi */}
                {activeTab === 'transaksi' && (
                    <>
                        {showFilters && (
                            <div className="bg-white p-4 rounded-lg shadow-md mb-4 space-y-3">
                                <div className="grid grid-cols-2 gap-3">
                                    <input type="date" name="startDate" value={filters.startDate} onChange={handleFilterChange} className="w-full p-2 border rounded-lg text-sm" />
                                    <input type="date" name="endDate" value={filters.endDate} onChange={handleFilterChange} className="w-full p-2 border rounded-lg text-sm" />
                                </div>
                                <select name="type" value={filters.type} onChange={handleFilterChange} className="w-full p-2 border rounded-lg text-sm">
                                    <option value="">Semua Jenis</option>
                                    <option value="TRANSFER_INTERNAL">Transfer</option>
                                    <option value="LOAN_PAYMENT">Bayar Pinjaman</option>
                                    <option value="BILL_PAYMENT">Bayar Tagihan</option>
                                    <option value="TOPUP">Setor Tunai</option>
                                    <option value="WITHDRAWAL">Tarik Tunai</option>
                                </select>
                                <Button onClick={handleApplyFilters} fullWidth>Terapkan Filter</Button>
                            </div>
                        )}
                        <div className="space-y-3">
                            {(transactions || []).length === 0 && (
                                <div className="text-center text-gray-500 py-12">
                                    <ArrowDown size={40} className="mx-auto text-gray-300 mb-3" />
                                    <p>Belum ada transaksi.</p>
                                </div>
                            )}
                            {(transactions || []).map(tx => (
                                <div key={tx.id} onClick={() => handleViewDetail(tx.id)} className="bg-white rounded-lg shadow-sm p-4 flex items-center cursor-pointer hover:bg-gray-50 transition-colors">
                                    <TransactionIcon flow={tx.flow} />
                                    <div className="flex-grow min-w-0">
                                        <p className="font-semibold text-gray-800 truncate">{tx.description}</p>
                                        <p className="text-xs text-gray-500">{formatDate(tx.created_at)}</p>
                                    </div>
                                    <p className={`font-bold ml-2 flex-shrink-0 ${tx.flow === 'Kredit' ? 'text-green-600' : 'text-red-500'}`}>
                                        {showBalance ? (
                                            `${tx.flow === 'Kredit' ? '+' : '-'}${formatCurrency(tx.amount)}`
                                        ) : (
                                            `${tx.flow === 'Kredit' ? '+' : '-'}Rp ••••••`
                                        )}
                                    </p>
                                </div>
                            ))}
                        </div>
                        {pagination?.has_more && (
                            <div className="mt-4">
                                <Button onClick={handleLoadMore} fullWidth className="bg-gray-200 text-gray-800 hover:bg-gray-300">Muat Lebih Banyak</Button>
                            </div>
                        )}
                    </>
                )}

                {/* Tab: Isi Saldo */}
                {activeTab === 'isi-saldo' && (
                    <div className="space-y-3">
                        {(topupRequests || []).length === 0 && (
                            <div className="text-center text-gray-500 py-12">
                                <Wallet size={40} className="mx-auto text-gray-300 mb-3" />
                                <p>Belum ada riwayat pengajuan isi saldo.</p>
                            </div>
                        )}
                        {(topupRequests || []).map(req => (
                            <div key={req.id} className="bg-white rounded-lg shadow-sm p-4">
                                <div className="flex justify-between items-start">
                                    <div className="flex-grow min-w-0">
                                        <p className="font-semibold text-gray-800">Isi Saldo via {req.payment_method ?? '-'}</p>
                                        <p className="text-xs text-gray-400 mt-1">{formatDate(req.created_at)}</p>
                                        {req.status === 'rejected' && req.rejection_reason && (
                                            <p className="text-xs text-red-500 mt-1">Alasan: {req.rejection_reason}</p>
                                        )}
                                    </div>
                                    <div className="text-right ml-3 flex-shrink-0">
                                        <p className="font-bold text-green-600">
                                            {showBalance ? `+${formatCurrency(req.amount)}` : '+Rp ••••••'}
                                        </p>
                                        <StatusBadge status={req.status} map={TOPUP_STATUS} />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Tab: Penarikan */}
                {activeTab === 'penarikan' && (
                    <div className="space-y-3">
                        {(withdrawalRequests || []).length === 0 && (
                            <div className="text-center text-gray-500 py-12">
                                <Banknote size={40} className="mx-auto text-gray-300 mb-3" />
                                <p>Belum ada riwayat penarikan.</p>
                            </div>
                        )}
                        {(withdrawalRequests || []).map(req => (
                            <div key={req.id} className="bg-white rounded-lg shadow-sm p-4">
                                <div className="flex justify-between items-start">
                                    <div className="flex-grow min-w-0">
                                        <p className="font-semibold text-gray-800">
                                            {req.bank_name ?? 'Bank'} - {req.account_number ?? '-'}
                                        </p>
                                        <p className="text-xs text-gray-500">a/n {req.account_name ?? '-'}</p>
                                        <p className="text-xs text-gray-400 mt-1">{formatDate(req.created_at)}</p>
                                    </div>
                                    <div className="text-right ml-3 flex-shrink-0">
                                        <p className="font-bold text-red-500">
                                            {showBalance ? `-${formatCurrency(req.amount)}` : '-Rp ••••••'}
                                        </p>
                                        <StatusBadge status={req.status} map={WITHDRAWAL_STATUS} />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Tab: Pinjaman */}
                {activeTab === 'pinjaman' && (
                    <div className="space-y-3">
                        {(loanApplications || []).length === 0 && (
                            <div className="text-center text-gray-500 py-12">
                                <Landmark size={40} className="mx-auto text-gray-300 mb-3" />
                                <p>Belum ada riwayat pengajuan pinjaman.</p>
                            </div>
                        )}
                        {(loanApplications || []).map(loan => (
                            <Link key={loan.id} href={`/my-loans/${loan.id}`} className="block bg-white rounded-lg shadow-sm p-4 hover:bg-gray-50 transition-colors">
                                <div className="flex justify-between items-start">
                                    <div className="flex-grow min-w-0">
                                        <p className="font-semibold text-gray-800">{loan.product_name ?? 'Pinjaman'}</p>
                                        <p className="text-sm text-gray-600">
                                            {showBalance ? formatCurrency(loan.loan_amount) : 'Rp ••••••'}
                                        </p>
                                        <p className="text-xs text-gray-400 mt-1">{formatDate(loan.created_at)}</p>
                                    </div>
                                    <div className="ml-3 flex-shrink-0">
                                        <StatusBadge status={loan.status} map={LOAN_STATUS} />
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>

            {isModalOpen && <TransactionDetailModal transaction={selectedTx} onClose={() => setIsModalOpen(false)} />}
        </>
    );
};

export default HistoryPage;
