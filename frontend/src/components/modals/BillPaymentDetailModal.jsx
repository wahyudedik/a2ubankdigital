import React from 'react';
import { X } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatDateTime = (dateString) => new Date(dateString).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });

const DetailRow = ({ label, value }) => (
    <div className="flex justify-between py-2 border-b">
        <span className="text-sm text-gray-500">{label}</span>
        <span className="text-sm text-right font-semibold text-gray-800 break-all">{value || '-'}</span>
    </div>
);

const BillPaymentDetailModal = ({ transaction, onClose }) => {
    if (!transaction) return null;

    // Parsing description untuk mendapatkan detail yang lebih baik
    let productName = transaction.description;
    let customerNo = '-';
    let serialNumber = '-';

    const snSplit = transaction.description.split(' | SN: ');
    if (snSplit.length > 1) {
        serialNumber = snSplit[1];
    }
    const mainDesc = snSplit[0];
    const descSplit = mainDesc.split(' ke ');
    if (descSplit.length > 1) {
        productName = descSplit[0].replace('Pembayaran ', '');
        customerNo = descSplit[1];
    }

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div className="p-4 border-b flex justify-between items-center">
                    <h3 className="text-lg font-bold">Detail Transaksi Tagihan</h3>
                    <button onClick={onClose} className="p-1 rounded-full hover:bg-gray-100"><X size={20} /></button>
                </div>
                <div className="p-4 max-h-[70vh] overflow-y-auto">
                    <DetailRow label="Nama Nasabah" value={transaction.customer_name} />
                    <DetailRow label="Kode Transaksi" value={transaction.transaction_code} />
                    <DetailRow label="Tanggal" value={formatDateTime(transaction.created_at)} />
                    <DetailRow label="Produk" value={productName} />
                    <DetailRow label="No. Tujuan" value={customerNo} />
                    <DetailRow label="Serial Number (SN)" value={serialNumber} />
                    <DetailRow label="Harga Produk" value={formatCurrency(transaction.amount)} />
                    <DetailRow label="Biaya Admin" value={formatCurrency(transaction.fee)} />
                    <DetailRow label="Total Bayar" value={formatCurrency(parseFloat(transaction.amount) + parseFloat(transaction.fee))} />
                    <DetailRow label="Status" value={transaction.status} />
                </div>
                <div className="bg-gray-50 p-3 flex justify-end">
                    <button onClick={onClose} className="px-4 py-2 text-sm bg-gray-200 rounded-lg">Tutup</button>
                </div>
            </div>
        </div>
    );
};

export default BillPaymentDetailModal;
