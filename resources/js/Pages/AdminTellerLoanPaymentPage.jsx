import React, { useState, useCallback } from 'react';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import { Search, CheckCircle, Printer, RotateCcw } from 'lucide-react';
import Button from '@/components/ui/Button';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const AdminTellerLoanPaymentPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [step, setStep] = useState(1); // 1: Input, 2: Konfirmasi, 3: Hasil
    const [loanId, setLoanId] = useState('');
    const [amount, setAmount] = useState('');
    const [loanInfo, setLoanInfo] = useState(null);
    const [transactionResult, setTransactionResult] = useState(null);

    const searchLoan = useCallback(async () => {
        if (!loanId.trim()) {
            await modal.showAlert({ title: 'Peringatan', message: 'Masukkan ID Pinjaman terlebih dahulu.', type: 'warning' });
            return;
        }

        const result = await callApi('/admin/loans/inquiry', 'POST', { loan_id: loanId });
        if (result && result.status === 'success') {
            setLoanInfo(result.data);
            setStep(2);
        }
    }, [loanId, callApi, modal]);

    const processPayment = async () => {
        if (!amount || parseFloat(amount) < 1000) {
            await modal.showAlert({ title: 'Peringatan', message: 'Masukkan jumlah pembayaran minimal Rp 1.000.', type: 'warning' });
            return;
        }

        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Pembayaran Pinjaman",
            message: `Anda akan memproses pembayaran pinjaman sebesar ${formatCurrency(amount)} untuk pinjaman ${loanInfo.loan_code}. Lanjutkan?`,
            confirmText: "Ya, Proses Pembayaran"
        });

        if (confirmed) {
            const result = await callApi('/admin/teller/loan-payment', 'POST', {
                loan_id: loanId,
                amount: parseFloat(amount)
            });

            if (result && result.status === 'success') {
                setTransactionResult(result.data);
                setStep(3);
            }
        }
    };

    const resetForm = () => {
        setStep(1);
        setLoanId('');
        setAmount('');
        setLoanInfo(null);
        setTransactionResult(null);
    };

    const handlePrintReceipt = () => {
        if (transactionResult?.transaction_id) {
            window.open(`/admin/print-receipt/${transactionResult.transaction_id}`, '_blank');
        }
    };

    const renderContent = () => {
        // Step 3: Transaction Result
        if (step === 3 && transactionResult) {
            return (
                <div>
                    <div className="text-center mb-6">
                        <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-2" />
                        <h2 className="text-xl font-semibold text-gray-800">Pembayaran Berhasil</h2>
                        <p className="text-gray-600">Transaksi telah diproses dengan sukses</p>
                    </div>

                    <div className="bg-gray-50 rounded-lg p-4 mb-6">
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="text-gray-600">Kode Transaksi:</span>
                                <p className="font-semibold">{transactionResult.transaction_code}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Jumlah Pembayaran:</span>
                                <p className="font-semibold text-green-600">{formatCurrency(transactionResult.amount)}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Pinjaman:</span>
                                <p className="font-semibold">{loanInfo?.loan_code}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Nasabah:</span>
                                <p className="font-semibold">{loanInfo?.customer_name}</p>
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-3">
                        <Button onClick={handlePrintReceipt} className="flex-1 bg-blue-600 hover:bg-blue-700">
                            <Printer size={16} className="mr-2" />
                            Cetak Struk
                        </Button>
                        <Button onClick={resetForm} variant="outline" className="flex-1">
                            <RotateCcw size={16} className="mr-2" />
                            Transaksi Baru
                        </Button>
                    </div>
                </div>
            );
        }

        // Step 2: Confirmation
        if (step === 2 && loanInfo) {
            return (
                <div>
                    <h2 className="text-lg font-semibold mb-4">Konfirmasi Data Pinjaman</h2>

                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="text-gray-600">Kode Pinjaman:</span>
                                <p className="font-semibold">{loanInfo.loan_code}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Nama Nasabah:</span>
                                <p className="font-semibold">{loanInfo.customer_name}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Jumlah Pinjaman:</span>
                                <p className="font-semibold">{formatCurrency(loanInfo.loan_amount)}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Sisa Pinjaman:</span>
                                <p className="font-semibold text-red-600">{formatCurrency(loanInfo.remaining_balance)}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Angsuran Bulanan:</span>
                                <p className="font-semibold">{formatCurrency(loanInfo.monthly_installment)}</p>
                            </div>
                            <div>
                                <span className="text-gray-600">Status:</span>
                                <p className="font-semibold text-green-600">{loanInfo.status}</p>
                            </div>
                        </div>
                    </div>

                    <div className="mb-6">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Jumlah Pembayaran
                        </label>
                        <input
                            type="number"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            placeholder="Masukkan jumlah pembayaran"
                            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            min="1000"
                            step="1000"
                        />
                        <p className="text-xs text-gray-500 mt-1">
                            Minimal pembayaran: Rp 1.000
                        </p>
                    </div>

                    <div className="flex gap-3">
                        <Button
                            onClick={processPayment}
                            disabled={loading || !amount}
                            className="flex-1 bg-green-600 hover:bg-green-700"
                        >
                            Proses Pembayaran
                        </Button>
                        <Button
                            onClick={() => setStep(1)}
                            variant="outline"
                            className="flex-1"
                        >
                            Kembali
                        </Button>
                    </div>
                </div>
            );
        }

        // Step 1: Input Loan ID
        return (
            <div>
                <h2 className="text-lg font-semibold mb-4">Cari Data Pinjaman</h2>

                <div className="mb-6">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                        ID Pinjaman
                    </label>
                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={loanId}
                            onChange={(e) => setLoanId(e.target.value)}
                            placeholder="Masukkan ID Pinjaman"
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onKeyPress={(e) => e.key === 'Enter' && searchLoan()}
                        />
                        <Button
                            onClick={searchLoan}
                            disabled={loading || !loanId.trim()}
                            className="px-4"
                        >
                            <Search size={16} className="mr-2" />
                            Cari
                        </Button>
                    </div>
                </div>

                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 className="font-medium text-yellow-800 mb-2">Petunjuk:</h3>
                    <ul className="text-sm text-yellow-700 space-y-1">
                        <li>• Masukkan ID Pinjaman yang valid</li>
                        <li>• Pastikan pinjaman dalam status aktif</li>
                        <li>• Verifikasi identitas nasabah sebelum memproses</li>
                        <li>• Cetak struk pembayaran untuk nasabah</li>
                    </ul>
                </div>
            </div>
        );
    };

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6">
                Pembayaran Pinjaman Teller
            </h1>

            {error && (
                <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <p className="text-red-700">{error}</p>
                </div>
            )}

            <div className="bg-white rounded-lg shadow-md p-6">
                {renderContent()}
            </div>
        </div>
    );
};

export default AdminTellerLoanPaymentPage;