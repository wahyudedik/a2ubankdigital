import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import { ArrowLeft, PlusCircle, Trash2, User, HelpCircle } from 'lucide-react';

const BeneficiaryListPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [beneficiaries, setBeneficiaries] = useState([]);
    const [isAddMode, setAddMode] = useState(false);
    const [inquiryResult, setInquiryResult] = useState(null);
    const [newData, setNewData] = useState({ accountNumber: '', nickname: '' });
    
    const fetchBeneficiaries = useCallback(async () => {
        const result = await callApi('beneficiaries_get_list.php');
        if (result && result.status === 'success') {
            setBeneficiaries(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchBeneficiaries();
    }, [fetchBeneficiaries]);

    const handleAccountNumberChange = (e) => {
        setNewData({ ...newData, accountNumber: e.target.value });
        setInquiryResult(null); // Reset hasil inquiry jika nomor diubah
    };

    const handleInquiry = async () => {
        if (!newData.accountNumber) return;
        const result = await callApi('transfer_internal_inquiry.php', 'POST', { destination_account_number: newData.accountNumber });
        if (result && result.status === 'success') {
            setInquiryResult(result.data.recipient_name);
        } else {
            // Error sudah ditangani oleh useApi hook, akan ditampilkan di bawah form
        }
    };

    const handleAddBeneficiary = async (e) => {
        e.preventDefault();
        const result = await callApi('beneficiaries_add.php', 'POST', {
            account_number: newData.accountNumber,
            nickname: newData.nickname
        });

        if (result && result.status === 'success') {
            modal.showAlert({ title: 'Berhasil', message: 'Penerima baru telah ditambahkan.', type: 'success' });
            setAddMode(false);
            setNewData({ accountNumber: '', nickname: '' });
            setInquiryResult(null);
            fetchBeneficiaries();
        }
    };

    const handleDelete = async (id) => {
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Hapus",
            message: "Apakah Anda yakin ingin menghapus penerima ini dari daftar?",
            confirmText: "Ya, Hapus"
        });
        if (confirmed) {
            const result = await callApi('beneficiaries_delete.php', 'POST', { id });
            if (result && result.status === 'success') {
                modal.showAlert({ title: 'Berhasil', message: 'Penerima telah dihapus.', type: 'success' });
                fetchBeneficiaries();
            }
        }
    };

    return (
        <div className="p-4">
            <Link to="/profile" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Daftar Penerima</h1>
            </Link>

            {/* Form Tambah Penerima (ditampilkan kondisional) */}
            {isAddMode ? (
                <div className="bg-white rounded-lg shadow-md p-4 mb-6">
                    <h2 className="font-bold mb-4">Tambah Penerima Baru</h2>
                    <form onSubmit={handleAddBeneficiary}>
                        <div className="space-y-4">
                            <div className="flex items-end gap-2">
                                <div className="flex-grow">
                                    <Input label="Nomor Rekening" name="accountNumber" value={newData.accountNumber} onChange={handleAccountNumberChange} required />
                                </div>
                                <Button type="button" onClick={handleInquiry} disabled={loading} className="py-2 px-4 h-10">Cek</Button>
                            </div>
                            {inquiryResult && <div className="p-2 bg-green-50 text-green-800 rounded-md text-sm">Nama: <strong>{inquiryResult}</strong></div>}
                            <Input label="Nama Panggilan" name="nickname" value={newData.nickname} onChange={(e) => setNewData({...newData, nickname: e.target.value})} required />
                        </div>
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-4 flex gap-2 justify-end">
                            <Button type="button" onClick={() => setAddMode(false)} className="bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</Button>
                            <Button type="submit" disabled={!inquiryResult || loading}>
                                {loading ? 'Menyimpan...' : 'Simpan'}
                            </Button>
                        </div>
                    </form>
                </div>
            ) : (
                <div className="mb-6">
                    <Button onClick={() => setAddMode(true)} fullWidth>
                        <PlusCircle size={20} className="mr-2"/> Tambah Penerima Baru
                    </Button>
                </div>
            )}
            
            {/* Daftar Penerima yang Sudah Ada */}
            <div className="bg-white rounded-lg shadow-md">
                <ul className="divide-y divide-gray-200">
                    {loading && beneficiaries.length === 0 ? (
                        <li className="p-4 text-center text-gray-500">Memuat daftar...</li>
                    ) : beneficiaries.length > 0 ? (
                        beneficiaries.map(b => (
                            <li key={b.id} className="p-4 flex justify-between items-center">
                                <div className="flex items-center">
                                    <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center mr-3"><User className="text-gray-500"/></div>
                                    <div>
                                        <p className="font-semibold text-gray-800">{b.nickname}</p>
                                        <p className="text-sm text-gray-500">{b.beneficiary_name} - {b.beneficiary_account_number}</p>
                                    </div>
                                </div>
                                <button onClick={() => handleDelete(b.id)} className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full">
                                    <Trash2 size={18}/>
                                </button>
                            </li>
                        ))
                    ) : (
                        <li className="p-8 text-center text-gray-500">
                             <HelpCircle size={32} className="mx-auto text-gray-300 mb-2"/>
                            Anda belum memiliki daftar penerima.
                        </li>
                    )}
                </ul>
            </div>
        </div>
    );
};

export default BeneficiaryListPage;
