import React, { useState, useEffect, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { AppConfig } from '../config';
import { Printer, Loader2 } from 'lucide-react';
import Button from '../components/ui/Button';

// Fungsi helper untuk format
const formatCurrency = (amount) => {
    if (amount === null || typeof amount === 'undefined') return '-';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
};
const formatDateTime = (dateString) => new Date(dateString).toLocaleString('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
});

// Komponen untuk baris detail pada struk
const ReceiptRow = ({ label, value, isTotal = false, fullWidth = false }) => (
    <div className={`flex ${fullWidth ? 'flex-col items-start' : 'justify-between items-start'} ${isTotal ? 'font-bold text-base mt-2 pt-2 border-t border-dashed' : 'text-xs'}`}>
        <span className="text-left">{label}</span>
        <span className={`text-right font-mono ${fullWidth ? 'w-full' : ''}`}>{value}</span>
    </div>
);


const PrintableReceiptPage = () => {
    const { transactionId } = useParams();
    const { loading, error, callApi } = useApi();
    const [receiptData, setReceiptData] = useState(null);

    const fetchReceiptData = useCallback(async () => {
        const result = await callApi(`admin_get_receipt_data.php?id=${transactionId}`);
        if (result && result.status === 'success') {
            setReceiptData(result.data);
        }
    }, [callApi, transactionId]);

    useEffect(() => {
        fetchReceiptData();
    }, [fetchReceiptData]);

    useEffect(() => {
        if (receiptData) {
            const timer = setTimeout(() => window.print(), 500);
            return () => clearTimeout(timer);
        }
    }, [receiptData]);
    
    // Styling khusus untuk printer thermal
    const printStyles = `
        @media print {
            .no-print { display: none; }
            @page {
                size: 80mm auto; /* Atur ukuran kertas jika printer mendukung */
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
                background-color: #fff;
            }
            body * {
                visibility: hidden;
                font-family: 'Courier New', Courier, monospace;
            }
            #printable-area, #printable-area * {
                visibility: visible;
            }
            #printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 2mm; /* Beri sedikit padding */
                box-shadow: none;
                border: none;
            }
        }
    `;

    if (loading) return <div className="flex items-center justify-center h-screen"><Loader2 className="animate-spin mr-2"/> Memuat data nota...</div>;
    if (error) return <div className="text-center p-8 text-red-500">{error}</div>;
    if (!receiptData) return <div className="text-center p-8">Data nota tidak ditemukan.</div>;
    
    const isLoanPayment = receiptData.transaction_type && receiptData.transaction_type.includes('BAYAR_CICILAN');
    const transactionTitle = isLoanPayment ? 'BUKTI PEMBAYARAN ANGSURAN' : 'BUKTI SETORAN TUNAI';
    const amountLabel = isLoanPayment ? 'Jml Bayar' : 'Jml Setor';

    return (
        <>
            <style>{printStyles}</style>
            
            <div className="bg-gray-200 min-h-screen flex flex-col items-center justify-center p-4 font-mono">
                 <div className="w-full max-w-[300px] bg-white shadow-lg" id="printable-area">
                    <div className="p-2">
                        <div className="text-center mb-2">
                            <img src={AppConfig.brand.logo} alt="Logo" className="h-8 mx-auto mb-1"/>
                            <p className="text-xs font-bold">{receiptData.unit_name || 'KANTOR PUSAT'}</p>
                            <p className="text-[10px] text-gray-700 leading-tight">{receiptData.unit_address}</p>
                        </div>

                        <div className="border-t border-b border-dashed border-gray-400 my-2 py-1">
                             <p className="text-center font-bold text-sm">{transactionTitle}</p>
                        </div>
                        
                        <div className="text-xs space-y-1">
                            <ReceiptRow label="No. Trx" value={receiptData.transaction_code} />
                            <ReceiptRow label="Tanggal" value={formatDateTime(receiptData.created_at)} />
                            <ReceiptRow label="Teller" value={receiptData.staff_name} />
                            <div className="border-t border-dashed my-1"></div>
                            <ReceiptRow label="Nama Nasabah" value={receiptData.customer_name} />
                            
                            {/* --- PERBAIKAN UTAMA DI SINI --- */}
                            {/* Logika ini memastikan No. Rekening hanya muncul untuk non-angsuran */}
                            {isLoanPayment ? (
                                <ReceiptRow label="ID Pinjaman" value={receiptData.loan_id} />
                            ) : (
                                <ReceiptRow label="No. Rekening" value={receiptData.customer_account_number} />
                            )}
                            
                            <div className="border-t border-dashed my-1"></div>

                            <ReceiptRow label="Keterangan" value={receiptData.description} fullWidth={true} />

                            {!isLoanPayment && receiptData.initial_balance !== null && (
                                 <ReceiptRow label="Saldo Awal" value={formatCurrency(receiptData.initial_balance)} />
                            )}

                            <ReceiptRow label={amountLabel} value={formatCurrency(receiptData.amount)} />

                            {!isLoanPayment && receiptData.final_balance !== null && (
                                <ReceiptRow label="Saldo Akhir" value={formatCurrency(receiptData.final_balance)} isTotal={true} />
                            )}
                        </div>
                        
                        <p className="text-[10px] text-center mt-3">Terima kasih atas kepercayaan Anda.</p>
                        <p className="text-[10px] text-center">Simpan struk ini sebagai bukti transaksi.</p>
                    </div>
                </div>

                <div className="mt-4 w-full max-w-[300px] no-print">
                    <Button onClick={() => window.print()} className="flex items-center justify-center gap-2 w-full">
                        <Printer size={18}/> Cetak Ulang
                    </Button>
                </div>
            </div>
        </>
    );
};

export default PrintableReceiptPage;

