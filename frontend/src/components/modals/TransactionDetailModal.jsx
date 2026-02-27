import React from 'react';
import { X } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatDateTime = (dateString) => new Date(dateString).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });

const DetailRow = ({ label, value, isHighlight = false }) => (
    <div className="flex justify-between py-2 border-b border-gray-100">
        <span className="text-sm text-gray-500">{label}</span>
        <span className={`text-sm text-right font-semibold ${isHighlight ? 'text-taskora-green-700' : 'text-gray-800'}`}>{value || '-'}</span>
    </div>
);

const TransactionDetailModal = ({ transaction, onClose }) => {
    if (!transaction) return null;

    const totalAmount = parseFloat(transaction.amount || 0) + parseFloat(transaction.fee || 0);

    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-sm transform transition-all">
                <div className="p-4 border-b flex justify-between items-center">
                    <h3 className="text-lg font-bold text-gray-900">Detail Transaksi</h3>
                    <button onClick={onClose} className="p-1 rounded-full hover:bg-gray-100">
                        <X size={20} />
                    </button>
                </div>
                <div className="p-4">
                    <div className="text-center my-4">
                        <p className="text-3xl font-bold">{formatCurrency(transaction.amount)}</p>
                        <p className={`text-sm font-semibold px-2 py-1 inline-block rounded mt-2 ${
                            transaction.status === 'SUCCESS' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                        }`}>{transaction.status}</p>
                    </div>
                    <div className="space-y-1">
                        <DetailRow label="Jenis Transaksi" value={transaction.transaction_type.replace(/_/g, ' ')} />
                        <DetailRow label="Tanggal & Waktu" value={formatDateTime(transaction.created_at)} />
                        <DetailRow label="Kode Transaksi" value={transaction.transaction_code} />
                        <DetailRow label="Deskripsi" value={transaction.description} />
                        {transaction.from_user_name && <DetailRow label="Dari" value={`${transaction.from_user_name} (${transaction.from_account_number})`} />}
                        {transaction.to_user_name && <DetailRow label="Ke" value={`${transaction.to_user_name} (${transaction.to_account_number})`} />}
                        <DetailRow label="Biaya Admin" value={formatCurrency(transaction.fee)} />
                        <DetailRow label="Total" value={formatCurrency(totalAmount)} isHighlight />
                    </div>
                </div>
                <div className="bg-gray-50 p-4 rounded-b-lg">
                    <button 
                        onClick={onClose} 
                        className="w-full px-4 py-2 text-sm font-semibold bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    );
};

export default TransactionDetailModal;
