import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import { CheckCircle } from 'lucide-react';
import Button from '../components/ui/Button';

const CardRequestsPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [requests, setRequests] = useState([]);

    const fetchRequests = useCallback(async () => {
        const result = await callApi('admin_get_card_requests.php');
        if (result && result.status === 'success') {
            setRequests(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchRequests();
    }, [fetchRequests]);

    const handleApprove = async (cardId) => {
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Aktivasi Kartu",
            message: "Anda akan mengaktifkan kartu ini. Pastikan kartu fisik sudah diterima oleh nasabah. Lanjutkan?",
            confirmText: "Ya, Aktifkan"
        });

        if (confirmed) {
            const result = await callApi('admin_process_card_request.php', 'POST', { card_id: cardId, action: 'APPROVE' });
            if (result && result.status === 'success') {
                modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' });
                fetchRequests();
            } else {
                modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning' });
            }
        }
    };

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Pengajuan Kartu Debit</h1>

            {error && <p className="text-red-500 mb-4">{error}</p>}
            
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <table className="w-full">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Nama Nasabah</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">No. Rekening</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Tanggal Pengajuan</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Status</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {loading ? (
                            <tr><td colSpan="5" className="p-8 text-center">Memuat...</td></tr>
                        ) : requests.length === 0 ? (
                            <tr><td colSpan="5" className="p-8 text-center">Tidak ada pengajuan kartu baru.</td></tr>
                        ) : requests.map(req => (
                            <tr key={req.id}>
                                <td className="p-4 font-medium">{req.customer_name}</td>
                                <td className="p-4 text-sm text-gray-600">{req.account_number}</td>
                                <td className="p-4 text-sm text-gray-600">{new Date(req.requested_at).toLocaleString('id-ID')}</td>
                                <td className="p-4">
                                     <span className="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        {req.status}
                                    </span>
                                </td>
                                <td className="p-4">
                                    <Button onClick={() => handleApprove(req.id)} className="text-sm py-1 px-3">
                                        <CheckCircle size={16} className="inline mr-1"/> Aktifkan
                                    </Button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default CardRequestsPage;
