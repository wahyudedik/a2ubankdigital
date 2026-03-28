import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Button from '@/components/ui/Button';
import TopUpRequestDetailModal from '@/components/modals/TopUpRequestDetailModal';
import { Eye } from 'lucide-react';
import Input from '@/components/ui/Input';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const RejectionModal = ({ onSubmit, onCancel, loading }) => {
    const [reason, setReason] = useState('');
    const handleSubmit = (e) => { e.preventDefault(); onSubmit(reason); };
    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow-xl w-full max-w-sm">
                <div className="p-6"><h3 className="text-lg font-bold text-gray-900">Alasan Penolakan</h3><p className="text-sm text-gray-600 mt-2 mb-4">Harap masukkan alasan mengapa permintaan ini ditolak.</p><textarea value={reason} onChange={(e) => setReason(e.target.value)} className="w-full p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-taskora-green-300" rows="3" required /></div>
                <div className="bg-gray-50 px-6 py-3 flex flex-row-reverse gap-3 rounded-b-lg"><Button type="submit" disabled={loading} className="bg-red-600 hover:bg-red-700">{loading ? 'Memproses...' : 'Tolak Permintaan'}</Button><Button type="button" onClick={onCancel} className="bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</Button></div>
            </form>
        </div>
    );
};

const TabButton = ({ id, activeTab, setActiveTab, children }) => (
    <button onClick={() => setActiveTab(id)} className={`px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 transition-colors ${activeTab === id ? 'border-taskora-green-700 text-taskora-green-700' : 'border-transparent text-gray-500 hover:text-gray-700'}`}>{children}</button>
);

const AdminTopUpRequestsPage = () => {
    const { requests, filters } = usePage().props;
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [selectedRequest, setSelectedRequest] = useState(null);
    const [activeTab, setActiveTab] = useState(filters?.status || 'pending');
    const [isRejectionModalOpen, setRejectionModalOpen] = useState(false);
    const [requestToReject, setRequestToReject] = useState(null);

    const handleTabChange = (tab) => { setActiveTab(tab); router.get(window.location.pathname, { status: tab }, { preserveState: true }); };

    const handleProcess = async (requestId, action, reason = null) => {
        if (selectedRequest) setSelectedRequest(null);
        if (isRejectionModalOpen) setRejectionModalOpen(false);
        const payload = { request_id: requestId, action: action.toLowerCase(), admin_notes: reason };
        const result = await callApi('admin_process_topup_request.php', 'POST', payload);
        if (result && result.status === 'success') { await modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); router.reload(); }
        else { await modal.showAlert({ title: 'Gagal', message: error || result?.message || 'Terjadi kesalahan', type: 'warning' }); }
    };

    const openRejectionModal = (request) => { setRequestToReject(request); setRejectionModalOpen(true); };

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Permintaan Isi Saldo</h1>
            <div className="flex border-b mb-4">
                <TabButton id="pending" activeTab={activeTab} setActiveTab={handleTabChange}>Menunggu Persetujuan</TabButton>
                <TabButton id="approved" activeTab={activeTab} setActiveTab={handleTabChange}>Disetujui</TabButton>
                <TabButton id="rejected" activeTab={activeTab} setActiveTab={handleTabChange}>Ditolak</TabButton>
            </div>
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-gray-50"><tr><th className="p-4 text-left text-sm font-semibold text-gray-600">Nasabah</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Jumlah</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Metode</th><th className="p-4 text-left text-sm font-semibold text-gray-600">{activeTab === 'pending' ? 'Tanggal Request' : 'Tanggal Proses'}</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th></tr></thead>
                        <tbody className="divide-y">
                            {(requests || []).length > 0 ? (requests || []).map(req => (
                                <tr key={req.id}>
                                    <td className="p-4 font-medium">{req.customer_name}</td>
                                    <td className="p-4">{formatCurrency(req.amount)}</td>
                                    <td className="p-4">{req.payment_method}</td>
                                    <td className="p-4 text-sm">{new Date(activeTab === 'pending' ? req.created_at : (req.processed_at || req.created_at)).toLocaleString('id-ID')}</td>
                                    <td className="p-4"><Button onClick={() => setSelectedRequest(req)} className="py-1 px-3 text-sm flex items-center gap-1"><Eye size={16} /> Lihat Detail</Button></td>
                                </tr>
                            )) : (<tr><td colSpan="5" className="p-8 text-center text-gray-500">Tidak ada permintaan pada kategori ini.</td></tr>)}
                        </tbody>
                    </table>
                </div>
            </div>
            {selectedRequest && <TopUpRequestDetailModal request={selectedRequest} onClose={() => setSelectedRequest(null)} onApprove={() => handleProcess(selectedRequest.id, 'APPROVE')} onReject={() => openRejectionModal(selectedRequest)} status={activeTab} />}
            {isRejectionModalOpen && <RejectionModal loading={loading} onCancel={() => setRejectionModalOpen(false)} onSubmit={(reason) => handleProcess(requestToReject.id, 'REJECT', reason)} />}
        </div>
    );
};

export default AdminTopUpRequestsPage;
