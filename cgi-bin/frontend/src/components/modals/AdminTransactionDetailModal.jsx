import React from 'react';
import { X, Loader2 } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatDateTime = (dateString) => dateString ? new Date(dateString).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '-';

const DetailRow = ({ label, value, isHighlight = false, fullWidth = false }) => (
    <div className={`py-2 border-b border-gray-100 ${fullWidth ? 'block' : 'flex justify-between items-start'}`}>
        <span className="text-sm text-gray-500 flex-shrink-0 pr-2">{label}</span>
        <span className={`text-sm text-right font-semibold break-words ${isHighlight ? 'text-taskora-green-700' : 'text-gray-800'}`}>{value || '-'}</span>
    </div>
);


const AdminTransactionDetailModal = ({ transaction, isLoading, onClose }) => {
    if (!transaction && !isLoading) return null;

    const renderDetails = () => {
        if (isLoading) {
            return <div className="text-center p-8"><Loader2 className="animate-spin inline-block" /> Memuat detail...</div>;
        }

        const totalAmount = parseFloat(transaction.amount || 0) + parseFloat(transaction.fee || 0);

        return (
            <div className="space-y-1">
                <DetailRow label="Kode Transaksi" value={transaction.transaction_code} />
                <DetailRow label="Tanggal & Waktu" value={formatDateTime(transaction.created_at)} />
                <DetailRow label="Status" value={transaction.status} isHighlight={transaction.status === 'SUCCESS'} />
                <hr className="my-2"/>
                {transaction.from_user_name && <DetailRow label="Dari" value={`${transaction.from_user_name} (${transaction.from_account_number})`} />}
                {transaction.to_user_name && <DetailRow label="Ke" value={`${transaction.to_user_name} (${transaction.to_account_number})`} />}
                <hr className="my-2"/>
                <DetailRow label="Jenis Transaksi" value={transaction.transaction_type.replace(/_/g, ' ')} />
                <DetailRow label="Deskripsi" value={transaction.description} fullWidth={true}/>
                <hr className="my-2"/>
                <DetailRow label="Jumlah Pokok" value={formatCurrency(transaction.amount)} />
                <DetailRow label="Biaya Admin" value={formatCurrency(transaction.fee)} />
                <DetailRow label="Total" value={formatCurrency(totalAmount)} isHighlight />
            </div>
        );
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-md transform transition-all">
                <div className="p-4 border-b flex justify-between items-center">
                    <h3 className="text-lg font-bold text-gray-900">Detail Transaksi</h3>
                    <button onClick={onClose} className="p-1 rounded-full hover:bg-gray-100">
                        <X size={20} />
                    </button>
                </div>
                <div className="p-4 max-h-[70vh] overflow-y-auto">
                    {renderDetails()}
                </div>
                <div className="bg-gray-50 p-3 flex justify-end">
                    <button onClick={onClose} className="px-4 py-2 text-sm bg-gray-200 rounded-lg hover:bg-gray-300">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AdminTransactionDetailModal;

