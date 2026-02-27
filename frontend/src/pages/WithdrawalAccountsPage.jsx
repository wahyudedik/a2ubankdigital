import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import { ArrowLeft, PlusCircle, Trash2, Banknote } from 'lucide-react';

const WithdrawalAccountsPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [accounts, setAccounts] = useState([]);
    const [isAddMode, setAddMode] = useState(false);
    const [newData, setNewData] = useState({ bank_name: '', account_number: '', account_name: '' });

    const fetchAccounts = useCallback(async () => {
        const result = await callApi('user_get_withdrawal_accounts.php');
        if(result && result.status === 'success') {
            setAccounts(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchAccounts();
    }, [fetchAccounts]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setNewData(prev => ({...prev, [name]: value}));
    };
    
    const handleSave = async (e) => {
        e.preventDefault();
        const result = await callApi('user_add_withdrawal_account.php', 'POST', newData);
        if(result && result.status === 'success') {
            setNewData({ bank_name: '', account_number: '', account_name: '' });
            setAddMode(false);
            fetchAccounts();
        }
    };

    // Fungsi hapus belum ada di backend, kita siapkan di frontend
    const handleDelete = async (id) => {
        const confirmed = await modal.showConfirmation({title:"Hapus Rekening?", message: "Anda yakin ingin menghapus rekening tujuan ini?"});
        if(confirmed) {
            // const result = await callApi('user_delete_withdrawal_account.php', 'POST', {id});
            // if(result && result.status === 'success') fetchAccounts();
            modal.showAlert({title:"Info", message:"Fitur hapus belum tersedia di backend."});
        }
    };

    return (
        <div>
            <Link to="/profile" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Rekening Penarikan</h1>
            </Link>

            {!isAddMode && (
                 <Button onClick={() => setAddMode(true)} fullWidth className="mb-4">
                    <PlusCircle size={20} className="mr-2"/> Tambah Rekening Baru
                </Button>
            )}

            {isAddMode && (
                <div className="bg-white p-4 rounded-lg shadow-md mb-4">
                    <h3 className="font-semibold mb-2">Form Rekening Baru</h3>
                    <form onSubmit={handleSave} className="space-y-3">
                         <Input name="bank_name" label="Nama Bank" value={newData.bank_name} onChange={handleChange} required />
                         <Input name="account_number" label="Nomor Rekening" value={newData.account_number} onChange={handleChange} required />
                         <Input name="account_name" label="Nama Pemilik Rekening" value={newData.account_name} onChange={handleChange} required />
                         {error && <p className="text-red-500 text-sm">{error}</p>}
                         <div className="flex gap-2 justify-end">
                            <Button type="button" onClick={() => setAddMode(false)} className="bg-gray-200 text-gray-800">Batal</Button>
                            <Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan'}</Button>
                         </div>
                    </form>
                </div>
            )}
            
            <div className="space-y-3">
                {accounts.map(acc => (
                    <div key={acc.id} className="bg-white p-3 rounded-lg shadow-sm flex justify-between items-center">
                        <div>
                            <p className="font-bold">{acc.bank_name}</p>
                            <p className="text-sm">{acc.account_number}</p>
                            <p className="text-xs text-gray-500">{acc.account_name}</p>
                        </div>
                        <button onClick={() => handleDelete(acc.id)} className="text-red-500 hover:text-red-700 p-2"><Trash2 size={18}/></button>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default WithdrawalAccountsPage;
