import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import { ArrowLeft, Check, Clock } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

// --- FUNGSI BARU UNTUK FORMAT TENOR ---
const formatTenorUnit = (unit) => {
    const unitMap = { 'HARI': 'Hari', 'MINGGU': 'Minggu', 'BULAN': 'Bulan' };
    return unitMap[unit] || unit;
};

const MyLoanDetailPage = () => {
    const { loanId } = useParams();
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [detail, setDetail] = useState(null);

    const fetchDetail = useCallback(async () => {
        const result = await callApi(`user_get_loan_detail.php?id=${loanId}`);
        if (result && result.status === 'success') {
            setDetail(result.data);
        }
    }, [callApi, loanId]);

    useEffect(() => {
        fetchDetail();
    }, [fetchDetail]);

    const handlePay = async (installment) => {
        // REVISI: Menghitung total tagihan termasuk denda
        const totalDue = parseFloat(installment.amount_due) + parseFloat(installment.penalty_amount || 0);
        
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Pembayaran",
            message: `Anda akan membayar angsuran sebesar ${formatCurrency(totalDue)} (termasuk denda jika ada). Lanjutkan?`,
            confirmText: "Ya, Bayar Sekarang"
        });

        if (confirmed) {
            const result = await callApi('user_pay_installment.php', 'POST', { installment_id: installment.id });
            if (result && result.status === 'success') {
                await modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' });
                fetchDetail();
            } else {
                await modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning' });
            }
        }
    };
    
    if (loading && !detail) return <div className="p-4 text-center">Memuat detail pinjaman...</div>;
    if (error) return <div className="p-4 text-center text-red-500">Error: {error}</div>;
    if (!detail) return <div className="p-4 text-center">Data pinjaman tidak ditemukan.</div>;

    const nextUnpaidInstallment = detail.installments?.find(i => i.status === 'PENDING' || i.status === 'OVERDUE');

    return (
        <div>
            <Link to="/my-loans" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Detail Pinjaman</h1>
            </Link>

            <div className="bg-white rounded-lg shadow-md mb-6 p-4">
                <h2 className="font-bold text-lg">{detail.product_name}</h2>
                <p className="text-sm text-gray-500">Total Pinjaman: {formatCurrency(detail.loan_amount)}</p>
                {/* REVISI: Menampilkan tenor dengan satuan */}
                <p className="text-sm text-gray-500">Tenor: {detail.tenor} {formatTenorUnit(detail.tenor_unit)}</p>
            </div>

            {nextUnpaidInstallment && (
                <div className="bg-white rounded-lg shadow-md mb-6 p-4">
                    <h3 className="font-semibold text-gray-800">Angsuran Berikutnya</h3>
                    <div className="flex justify-between items-center mt-2">
                        <div>
                            {/* REVISI: Menampilkan total tagihan (termasuk denda) */}
                            <p className="font-bold text-xl text-taskora-green-700">
                                {formatCurrency(parseFloat(nextUnpaidInstallment.amount_due) + parseFloat(nextUnpaidInstallment.penalty_amount || 0))}
                            </p>
                            <p className="text-sm text-gray-500">Jatuh tempo: {new Date(nextUnpaidInstallment.due_date).toLocaleDateString('id-ID')}</p>
                            {parseFloat(nextUnpaidInstallment.penalty_amount || 0) > 0 && (
                                 <p className="text-xs text-red-600">Termasuk denda {formatCurrency(nextUnpaidInstallment.penalty_amount)}</p>
                            )}
                        </div>
                        <Button onClick={() => handlePay(nextUnpaidInstallment)} disabled={loading}>
                            Bayar Sekarang
                        </Button>
                    </div>
                </div>
            )}

            <div className="bg-white rounded-lg shadow-md p-4">
                <h3 className="font-semibold text-gray-800 mb-4">Riwayat Angsuran</h3>
                <ul className="divide-y divide-gray-200">
                    {detail.installments?.length > 0 ? detail.installments.map(item => (
                        <li key={item.id} className="py-3 flex justify-between items-center">
                            <div>
                                <p className="font-medium">Angsuran ke-{item.installment_number}</p>
                                <p className="text-sm text-gray-500">Jatuh tempo: {new Date(item.due_date).toLocaleDateString('id-ID')}</p>
                            </div>
                            {item.status === 'PAID' ? (
                                <span className="flex items-center gap-1 text-sm text-green-600 font-semibold"><Check size={16}/> Lunas</span>
                            ) : (
                                <div className="text-right">
                                    <span className="flex items-center justify-end gap-1 text-sm text-yellow-600 font-semibold"><Clock size={16}/> {formatCurrency(item.amount_due)}</span>
                                    {parseFloat(item.penalty_amount || 0) > 0 && (
                                         <p className="text-xs text-red-600">+ Denda {formatCurrency(item.penalty_amount)}</p>
                                    )}
                                </div>
                            )}
                        </li>
                    )) : <p className="text-sm text-gray-500 text-center py-4">Jadwal angsuran belum tersedia.</p>}
                </ul>
            </div>
        </div>
    );
};

export default MyLoanDetailPage;

