import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import { ArrowLeft, Banknote } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const WithdrawalPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const navigate = useNavigate();
    const [accounts, setAccounts] = useState([]);
    const [formData, setFormData] = useState({
        withdrawal_account_id: '',
        amount: '',
        pin: ''
    });

    useEffect(() => {
        const fetchAccounts = async () => {
            const result = await callApi('user_get_withdrawal_accounts.php');
            if (result && result.status === 'success' && result.data.length > 0) {
                setAccounts(result.data);
                // Set default value for the dropdown
                setFormData(prev => ({...prev, withdrawal_account_id: result.data[0].id}));
            }
        };
        fetchAccounts();
    }, [callApi]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        // PERBAIKAN: Memastikan perbandingan tipe data selalu benar (string vs string)
        const selectedAccount = accounts.find(acc => acc.id.toString() === formData.withdrawal_account_id.toString());
        
        if (!selectedAccount) {
            modal.showAlert({title: "Error", message: "Silakan pilih rekening tujuan penarikan.", type: "warning"});
            return;
        }

        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Penarikan",
            message: `Anda akan menarik dana sebesar ${formatCurrency(formData.amount)} ke rekening ${selectedAccount.bank_name} - ${selectedAccount.account_number}. Lanjutkan?`,
            confirmText: "Ya, Lanjutkan"
        });

        if(confirmed) {
            const result = await callApi('user_create_withdrawal_request.php', 'POST', formData);
            if(result && result.status === 'success') {
                await modal.showAlert({title: "Berhasil", message: result.message, type: "success"});
                navigate('/dashboard');
            }
        }
    };

    return (
        <div>
            <Link to="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Tarik Saldo</h1>
            </Link>
            <div className="bg-white p-6 rounded-lg shadow-md">
                {accounts.length === 0 ? (
                    <div className="text-center p-4">
                        <p className="text-gray-600">Anda belum memiliki rekening penarikan. Silakan tambahkan terlebih dahulu di menu profil.</p>
                        <Link to="/profile/withdrawal-accounts"><Button className="mt-4">Tambah Rekening</Button></Link>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block mb-2 text-sm font-medium text-gray-700">Pilih Rekening Tujuan</label>
                            <select name="withdrawal_account_id" value={formData.withdrawal_account_id} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-taskora-green-300">
                                {accounts.map(acc => (
                                    <option key={acc.id} value={acc.id}>{acc.bank_name} - {acc.account_number} (a/n {acc.account_name})</option>
                                ))}
                            </select>
                        </div>
                        <Input name="amount" type="number" label="Jumlah Penarikan" value={formData.amount} onChange={handleChange} required />
                        <Input name="pin" type="password" label="PIN Transaksi" value={formData.pin} onChange={handleChange} maxLength="6" required />
                        {error && <p className="text-red-500 text-sm mt-2 text-center">{error}</p>}
                        <div className="mt-4 border-t pt-4">
                            <Button type="submit" fullWidth disabled={loading}>{loading ? 'Memproses...' : 'Tarik Saldo'}</Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
};

export default WithdrawalPage;

