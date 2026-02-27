import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 2 }).format(amount);

const DetailItem = ({ label, value }) => (
    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt className="text-sm font-medium text-gray-500">{label}</dt>
        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{value || '-'}</dd>
    </div>
);

const DepositDetailPage = () => {
    const { depositId } = useParams();
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [detail, setDetail] = useState(null);

    const fetchDetail = useCallback(async () => {
        const result = await callApi(`user_get_deposit_detail.php?id=${depositId}`);
        if (result && result.status === 'success') {
            setDetail(result.data);
        }
    }, [callApi, depositId]);

    useEffect(() => {
        fetchDetail();
    }, [fetchDetail]);

    const handleDisburse = async () => {
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Pencairan Deposito",
            message: "Anda akan mencairkan dana pokok beserta bunga ke rekening tabungan Anda. Lanjutkan?",
            confirmText: "Ya, Cairkan Dana"
        });
        if (confirmed) {
            const result = await callApi('deposit_account_disburse.php', 'POST', { deposit_id: depositId });
            if (result && result.status === 'success') {
                await modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' });
                fetchDetail(); // Refresh data untuk melihat status baru
            } else {
                modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning' });
            }
        }
    };
    
    if (loading && !detail) return <div className="p-4 text-center">Memuat detail...</div>;
    if (error) return <div className="p-4 text-center text-red-500">{error}</div>;
    if (!detail) return <div className="p-4 text-center">Data tidak ditemukan.</div>;

    const isMatured = new Date(detail.maturity_date) <= new Date();

    return (
        <div>
            <Link to="/deposits" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Detail Deposito</h1>
            </Link>

            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="p-4 border-b">
                    <h3 className="text-lg font-medium text-gray-900">{detail.product_name}</h3>
                    <p className="text-sm text-gray-500">{detail.account_number}</p>
                </div>
                <div className="p-4">
                    <dl className="divide-y divide-gray-200">
                        <DetailItem label="Status" value={detail.status} />
                        <DetailItem label="Pokok Penempatan" value={formatCurrency(detail.principal)} />
                        <DetailItem label="Suku Bunga" value={`${detail.interest_rate_pa}% per tahun`} />
                        <DetailItem label="Tanggal Penempatan" value={new Date(detail.placement_date).toLocaleDateString('id-ID')} />
                        <DetailItem label="Tanggal Jatuh Tempo" value={new Date(detail.maturity_date).toLocaleDateString('id-ID')} />
                        <DetailItem label="Estimasi Bunga Diperoleh" value={formatCurrency(detail.interest_earned)} />
                    </dl>
                </div>
                {detail.status === 'ACTIVE' && isMatured && (
                    <div className="p-4 bg-gray-50 border-t">
                        <Button onClick={handleDisburse} disabled={loading} fullWidth>
                            {loading ? 'Memproses...' : 'Cairkan Dana'}
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
};

export default DepositDetailPage;
