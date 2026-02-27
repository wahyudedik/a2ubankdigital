import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import { CheckCircle, XCircle, DollarSign } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

// Komponen navigasi Tab
const TabButton = ({ id, activeTab, setActiveTab, children }) => (
    <button
        onClick={() => setActiveTab(id)}
        className={`px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 transition-colors ${
            activeTab === id
                ? 'border-taskora-green-700 text-taskora-green-700'
                : 'border-transparent text-gray-500 hover:text-gray-700'
        }`}
    >
        {children}
    </button>
);

const AdminWithdrawalRequestsPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [requests, setRequests] = useState([]);
    const [activeTab, setActiveTab] = useState('PENDING');

    const fetchRequests = useCallback(async (status) => {
        const result = await callApi(`admin_get_withdrawal_requests.php?status=${status}`);
        if (result && result.status === 'success') {
            setRequests(result.data);
        } else {
            setRequests([]); // Kosongkan jika ada error atau data tidak ditemukan
        }
    }, [callApi]);

    useEffect(() => {
        fetchRequests(activeTab);
    }, [fetchRequests, activeTab]);

    const handleProcess = async (requestId, action) => {
        const confirmed = await modal.showConfirmation({
            title: `Konfirmasi ${action === 'APPROVE' ? 'Persetujuan' : 'Penolakan'}`,
            message: `Anda yakin ingin ${action === 'APPROVE' ? 'menyetujui' : 'menolak'} permintaan penarikan ini?`,
            confirmText: `Ya, ${action === 'APPROVE' ? 'Setujui' : 'Tolak'}`
        });
        
        if (confirmed) {
            const result = await callApi('admin_process_withdrawal_request.php', 'POST', { request_id: requestId, action });
            if (result && result.status === 'success') {
                await modal.showAlert({ title: 'Berhasil', message: 'Permintaan berhasil diproses.', type: 'success'});
                fetchRequests(activeTab);
            } else {
                 await modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning'});
            }
        }
    };

    const handleDisburse = async (requestId) => {
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Pencairan Dana",
            message: "Anda akan mengeksekusi transfer dana ke rekening nasabah. Pastikan proses transfer eksternal sudah siap. Tindakan ini akan menyelesaikan transaksi.",
            confirmText: "Ya, Cairkan Dana"
        });
        
        if (confirmed) {
            const result = await callApi('admin_disburse_withdrawal.php', 'POST', { request_id: requestId });
            if (result && result.status === 'success') {
                await modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success'});
                fetchRequests(activeTab);
            } else {
                 await modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning'});
            }
        }
    };

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Permintaan Penarikan Dana</h1>
            
            <div className="flex border-b mb-4">
                <TabButton id="PENDING" activeTab={activeTab} setActiveTab={setActiveTab}>Menunggu Persetujuan</TabButton>
                <TabButton id="APPROVED" activeTab={activeTab} setActiveTab={setActiveTab}>Siap Dicairkan</TabButton>
                <TabButton id="COMPLETED" activeTab={activeTab} setActiveTab={setActiveTab}>Selesai</TabButton>
                <TabButton id="REJECTED" activeTab={activeTab} setActiveTab={setActiveTab}>Ditolak</TabButton>
            </div>
            
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Nasabah</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Jumlah</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Rekening Tujuan</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">{activeTab === 'PENDING' ? 'Tanggal Request' : 'Tanggal Proses'}</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {loading ? (
                                <tr><td colSpan="5" className="p-8 text-center">Memuat...</td></tr>
                            ) : requests.length > 0 ? requests.map(req => (
                                <tr key={req.id}>
                                    <td className="p-4 font-medium">{req.customer_name}</td>
                                    <td className="p-4">{formatCurrency(req.amount)}</td>
                                    <td className="p-4 text-sm">{req.bank_name} - {req.account_number} (a/n {req.account_name})</td>
                                    <td className="p-4 text-sm">{new Date(activeTab === 'PENDING' ? req.created_at : req.processed_at).toLocaleString('id-ID')}</td>
                                    <td className="p-4">
                                        {activeTab === 'PENDING' && (
                                            <div className="flex gap-2">
                                                <Button onClick={() => handleProcess(req.id, 'APPROVE')} title="Setujui" className="py-1 px-3 text-sm bg-green-600 hover:bg-green-700"><CheckCircle size={16}/></Button>
                                                <Button onClick={() => handleProcess(req.id, 'REJECT')} title="Tolak" className="py-1 px-3 text-sm bg-red-600 hover:bg-red-700"><XCircle size={16}/></Button>
                                            </div>
                                        )}
                                        {activeTab === 'APPROVED' && (
                                            <Button onClick={() => handleDisburse(req.id)} title="Cairkan Dana" className="py-1 px-3 text-sm bg-blue-600 hover:bg-blue-700"><DollarSign size={16}/> Cairkan</Button>
                                        )}
                                        {(activeTab === 'COMPLETED' || activeTab === 'REJECTED') && (
                                            <span className="text-xs text-gray-500 italic">Selesai</span>
                                        )}
                                    </td>
                                </tr>
                            )) : (
                                <tr><td colSpan="5" className="p-8 text-center text-gray-500">Tidak ada permintaan pada kategori ini.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default AdminWithdrawalRequestsPage;

