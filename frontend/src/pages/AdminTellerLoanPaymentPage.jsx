import React, { useState, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { ArrowLeft, Search, Loader2, Info } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const AdminTellerLoanPaymentPage = () => {
    const { loading, error, callApi, setError } = useApi();
    const modal = useModal();
    const [searchTerm, setSearchTerm] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [selectedInstallment, setSelectedInstallment] = useState(null);
    const [cashAmount, setCashAmount] = useState('');

    useEffect(() => {
        if (searchTerm.length < 3) {
            setSearchResults([]);
            return;
        }
        const handler = setTimeout(() => {
            handleSearch();
        }, 500);
        return () => clearTimeout(handler);
    }, [searchTerm]);

    const handleSearch = async () => {
        if (searchTerm.length < 3) return;
        setSelectedInstallment(null);
        const result = await callApi(`admin_search_installments.php?q=${searchTerm}`);
        if (result && result.status === 'success') {
            setSearchResults(result.data);
        } else {
            setSearchResults([]);
        }
    };

    const handleSelectInstallment = (installment) => {
        setSelectedInstallment(installment);
        const totalDue = parseFloat(installment.amount_due) + parseFloat(installment.penalty_amount);
        setCashAmount(totalDue.toString());
        setError(null);
    };
    
    const handleSubmit = async (e) => {
        e.preventDefault();
        const payload = {
            installment_id: selectedInstallment.installment_id,
            cash_amount: cashAmount
        };

        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Pembayaran",
            message: `Anda akan memproses pembayaran tunai sebesar ${formatCurrency(cashAmount)} untuk angsuran pinjaman ${selectedInstallment.customer_name}. Lanjutkan?`,
            confirmText: "Ya, Proses Pembayaran"
        });

        if (confirmed) {
            const result = await callApi('admin_teller_pay_installment.php', 'POST', payload);
            if (result && result.status === 'success') {
                await modal.showAlert({ title: "Berhasil", message: result.message, type: 'success' });
                
                // --- PERUBAHAN DI SINI ---
                // Buka tab baru untuk cetak nota, lalu reset form
                window.open(`/admin/print-receipt/${result.data.transaction_id}`, '_blank');
                resetForm();
                // --- AKHIR PERUBAHAN ---
            }
        }
    };
    
    const resetForm = () => {
        setSearchTerm('');
        setSearchResults([]);
        setSelectedInstallment(null);
        setCashAmount('');
    };

    const totalDue = selectedInstallment ? parseFloat(selectedInstallment.amount_due) + parseFloat(selectedInstallment.penalty_amount) : 0;

    return (
        <div>
            <Link to="/admin/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Pembayaran Angsuran Tunai</h1>
            </Link>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-lg shadow-md">
                    <h2 className="text-lg font-semibold mb-4">1. Cari Angsuran Nasabah</h2>
                    <div className="relative">
                        <Input 
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Ketik nama atau ID pinjaman..."
                        />
                        <Search className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"/>
                    </div>
                    {loading && <div className="text-center mt-4"><Loader2 className="animate-spin inline-block"/></div>}
                    <div className="mt-4 space-y-2 max-h-96 overflow-y-auto">
                        {searchResults.map(item => (
                            <div 
                                key={item.installment_id}
                                onClick={() => handleSelectInstallment(item)}
                                className={`p-3 border rounded-lg cursor-pointer ${selectedInstallment?.installment_id === item.installment_id ? 'bg-blue-100 border-blue-400' : 'hover:bg-gray-50'}`}
                            >
                                <p className="font-bold">{item.customer_name}</p>
                                <p className="text-sm">{item.product_name} - Angsuran ke-{item.installment_number}</p>
                                <p className="text-xs text-gray-500">Jatuh Tempo: {new Date(item.due_date).toLocaleDateString('id-ID')}</p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="bg-white p-6 rounded-lg shadow-md">
                    <h2 className="text-lg font-semibold mb-4">2. Konfirmasi Pembayaran</h2>
                    {selectedInstallment ? (
                        <form onSubmit={handleSubmit}>
                            <div className="space-y-3 bg-gray-50 p-4 rounded-lg">
                                <div className="flex justify-between"><span className="text-sm text-gray-600">Angsuran Pokok</span><span className="font-semibold">{formatCurrency(selectedInstallment.amount_due)}</span></div>
                                <div className="flex justify-between"><span className="text-sm text-gray-600">Denda</span><span className="font-semibold">{formatCurrency(selectedInstallment.penalty_amount)}</span></div>
                                <div className="flex justify-between font-bold text-base border-t pt-2 mt-2"><span >Total Tagihan</span><span>{formatCurrency(totalDue)}</span></div>
                            </div>
                             <div className="mt-4">
                                <Input label="Jumlah Uang Tunai Diterima (Rp)" type="number" value={cashAmount} onChange={e => setCashAmount(e.target.value)} required />
                             </div>
                             {parseFloat(cashAmount) > totalDue && (
                                <div className="mt-2 text-sm text-blue-700 bg-blue-100 p-2 rounded-md flex items-center gap-2">
                                    <Info size={16}/>
                                    Kembalian: {formatCurrency(cashAmount - totalDue)}
                                </div>
                             )}
                             {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                             <div className="mt-6">
                                <Button type="submit" fullWidth disabled={loading || parseFloat(cashAmount) < totalDue}>
                                    {loading ? 'Memproses...' : 'Proses Pembayaran'}
                                </Button>
                             </div>
                        </form>
                    ) : (
                        <div className="text-center text-gray-500 h-full flex flex-col justify-center items-center">
                            <p>Pilih angsuran dari hasil pencarian untuk melanjutkan.</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AdminTellerLoanPaymentPage;
