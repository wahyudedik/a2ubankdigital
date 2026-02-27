import React, { useState } from 'react';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { Landmark, UserCheck, ArrowLeft, Loader2, Printer, PlusCircle, CheckCircle } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

// Komponen baru untuk menampilkan baris detail pada ringkasan
const SummaryRow = ({ label, value, highlight = false }) => (
    <div className="flex justify-between items-center py-2">
        <span className="text-sm text-gray-500">{label}</span>
        <span className={`text-sm font-semibold ${highlight ? 'text-lg text-taskora-green-700' : 'text-gray-800'}`}>{value}</span>
    </div>
);

const AdminTellerDepositPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [step, setStep] = useState(1);
    const [accountNumber, setAccountNumber] = useState('');
    const [amount, setAmount] = useState('');
    const [customerInfo, setCustomerInfo] = useState(null);
    // State baru untuk menyimpan hasil transaksi
    const [transactionResult, setTransactionResult] = useState(null);

    const handleInquiry = async (e) => {
        e.preventDefault();
        const result = await callApi('transfer_internal_inquiry.php', 'POST', { destination_account_number: accountNumber });
        if (result && result.status === 'success') {
            setCustomerInfo(result.data);
            setStep(2);
        }
    };

    const handleDeposit = async (e) => {
        e.preventDefault();
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Setoran Tunai",
            message: `Anda akan melakukan setoran sebesar ${formatCurrency(amount)} ke rekening ${customerInfo.account_number} a/n ${customerInfo.recipient_name}. Lanjutkan?`,
            confirmText: "Ya, Konfirmasi Setoran"
        });

        if (confirmed) {
            const result = await callApi('admin_teller_deposit.php', 'POST', { account_number: accountNumber, amount });
            if (result && result.status === 'success') {
                // Simpan hasil transaksi ke state
                setTransactionResult(result.data);
                setStep(3); // Pindah ke langkah ringkasan
            }
        }
    };

    const resetForm = () => {
        setStep(1);
        setAccountNumber('');
        setAmount('');
        setCustomerInfo(null);
        setTransactionResult(null); // Reset juga hasil transaksi
    };

    const handlePrintReceipt = () => {
        if (transactionResult?.transaction_id) {
            window.open(`/admin/print-receipt/${transactionResult.transaction_id}`, '_blank');
        }
    };

    const renderContent = () => {
        // Langkah 3: Tampilkan Ringkasan Transaksi
        if (step === 3 && transactionResult) {
            return (
                <div>
                    <div className="text-center mb-6">
                        <CheckCircle className="w-16 h-16 text-green-500 mx-auto mb-2" />
                        <h2 className="text-xl font-bold text-gray-800">Setoran Berhasil</h2>
                        <p className="text-sm text-gray-500">Dana telah berhasil ditambahkan ke rekening nasabah.</p>
                    </div>
                    <div className="bg-gray-50 p-4 rounded-lg space-y-1 divide-y">
                        <SummaryRow label="Nasabah" value={customerInfo.recipient_name} />
                        <SummaryRow label="No. Rekening" value={customerInfo.account_number} />
                        <SummaryRow label="Saldo Awal" value={formatCurrency(transactionResult.initial_balance)} />
                        <SummaryRow label="Jumlah Setoran" value={formatCurrency(amount)} />
                        <SummaryRow label="Saldo Akhir" value={formatCurrency(transactionResult.final_balance)} highlight />
                    </div>
                    <div className="mt-6 flex gap-2">
                        <Button onClick={handlePrintReceipt} className="bg-gray-600 hover:bg-gray-700 w-1/2">
                            <Printer size={16} className="mr-2"/> Cetak Nota
                        </Button>
                        <Button onClick={resetForm} fullWidth>
                            <PlusCircle size={16} className="mr-2"/> Transaksi Baru
                        </Button>
                    </div>
                </div>
            );
        }
        
        // Langkah 2: Konfirmasi dan Masukkan Jumlah
        if (step === 2 && customerInfo) {
             return (
                <form onSubmit={handleDeposit}>
                     <h2 className="text-lg font-semibold mb-4">Langkah 2: Konfirmasi & Setoran</h2>
                     <div className="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md mb-4">
                        <div className="flex items-center gap-3">
                            <UserCheck size={24} />
                            <div>
                                <p className="font-bold">{customerInfo.recipient_name}</p>
                                <p className="text-sm">{customerInfo.account_number}</p>
                            </div>
                        </div>
                    </div>
                    <Input
                        label="Jumlah Setoran (Rp)"
                        type="number"
                        name="amount"
                        value={amount}
                        onChange={(e) => setAmount(e.target.value)}
                        placeholder="Masukkan jumlah setoran"
                        required
                    />
                    {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                    <div className="mt-6 flex gap-2">
                         <Button type="button" onClick={resetForm} className="bg-gray-200 text-gray-800 hover:bg-gray-300">
                            <ArrowLeft size={16} className="mr-1"/> Batal
                        </Button>
                        <Button type="submit" fullWidth disabled={loading}>
                            {loading ? <><Loader2 className="animate-spin mr-2"/> Memproses...</> : 'Setor Dana'}
                        </Button>
                    </div>
                </form>
            );
        }

        // Langkah 1: Cari Rekening (Default)
        return (
            <form onSubmit={handleInquiry}>
                <h2 className="text-lg font-semibold mb-4">Langkah 1: Cari Rekening Nasabah</h2>
                <Input
                    label="Nomor Rekening Tujuan"
                    name="accountNumber"
                    value={accountNumber}
                    onChange={(e) => setAccountNumber(e.target.value)}
                    placeholder="Masukkan nomor rekening"
                    required
                />
                {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                <div className="mt-6">
                    <Button type="submit" fullWidth disabled={loading}>
                        {loading ? <><Loader2 className="animate-spin mr-2"/> Mencari...</> : 'Cek Rekening'}
                    </Button>
                </div>
            </form>
        );
    };

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                <Landmark size={32} />
                <span>Setor Tunai Teller</span>
            </h1>
            <div className="bg-white p-8 rounded-lg shadow-md max-w-md mx-auto">
                {renderContent()}
            </div>
        </div>
    );
};

export default AdminTellerDepositPage;

