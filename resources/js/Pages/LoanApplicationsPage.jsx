import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import { CheckCircle, XCircle, DollarSign, Eye } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatTenor = (tenor, unit) => {
    if (!tenor || !unit) return '-';
    const unitText = { 'HARI': 'Hari', 'MINGGU': 'Minggu', 'BULAN': 'Bulan' };
    return `${tenor} ${unitText[unit] || unit}`;
};

const StatusBadge = ({ status }) => {
    const baseClass = "px-2 py-1 text-xs font-semibold rounded-full";
    const statusMap = { 'SUBMITTED': 'bg-blue-100 text-blue-800', 'ANALYZING': 'bg-yellow-100 text-yellow-800', 'APPROVED': 'bg-green-100 text-green-800', 'DISBURSED': 'bg-teal-100 text-teal-800', 'REJECTED': 'bg-red-100 text-red-800', 'COMPLETED': 'bg-gray-100 text-gray-800' };
    return <span className={`${baseClass} ${statusMap[status] || 'bg-gray-200'}`}>{status}</span>;
};

const LoanApplicationsPage = () => {
    const { loans, filters } = usePage().props;
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [statusFilter, setStatusFilter] = useState(filters?.status || 'new');

    const handleTabChange = (tab) => {
        setStatusFilter(tab);
        router.get(window.location.pathname, { status: tab }, { preserveState: true });
    };

    const handleUpdateStatus = async (loanId, newStatus) => {
        const actionText = newStatus === 'APPROVED' ? 'MENYETUJUI' : 'MENOLAK';
        const confirmed = await modal.showConfirmation({ title: `Konfirmasi ${actionText}`, message: `Apakah Anda yakin ingin ${actionText.toLowerCase()} pengajuan pinjaman ini?`, confirmText: `Ya, ${actionText}` });
        if (confirmed) {
            const result = await callApi('admin_loan_application_update_status.php', 'PUT', { loan_id: loanId, status: newStatus });
            if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); router.reload(); }
            else { modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning' }); }
        }
    };

    const handleDisburse = async (loanId) => {
        const confirmed = await modal.showConfirmation({ title: "Konfirmasi Pencairan", message: "Anda akan mencairkan dana ke rekening nasabah. Tindakan ini akan membuat jadwal angsuran dan tidak dapat dibatalkan. Lanjutkan?", confirmText: "Ya, Cairkan Dana" });
        if (confirmed) {
            const result = await callApi('admin_loan_disburse.php', 'POST', { loan_id: loanId });
            if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); router.reload(); }
            else { modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning' }); }
        }
    };

    const applications = loans || [];

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Manajemen Pengajuan Pinjaman</h1>
            <div className="flex border-b mb-4">
                <button onClick={() => handleTabChange('new')} className={`px-4 py-2 font-semibold ${statusFilter === 'new' ? 'border-b-2 border-taskora-green-700 text-taskora-green-700' : 'text-gray-500'}`}>Pengajuan Baru</button>
                <button onClick={() => handleTabChange('approved')} className={`px-4 py-2 font-semibold ${statusFilter === 'approved' ? 'border-b-2 border-taskora-green-700 text-taskora-green-700' : 'text-gray-500'}`}>Siap Dicairkan</button>
            </div>
            <div className="md:hidden space-y-4">
                {applications.map(app => (
                    <div key={app.id} className="bg-white rounded-lg shadow-md p-4">
                        <div className="flex justify-between items-start"><div><p className="font-bold text-gray-800">{app.customer_name}</p><p className="text-sm text-gray-500">{app.product_name}</p></div><StatusBadge status={app.status} /></div>
                        <div className="mt-4 border-t pt-4 space-y-2 text-sm text-gray-600">
                            <p><strong>Jumlah:</strong> {formatCurrency(app.loan_amount)}</p>
                            <p><strong>Tenor:</strong> {formatTenor(app.tenor, app.tenor_unit)}</p>
                            <p><strong>Tanggal:</strong> {new Date(app.application_date).toLocaleDateString('id-ID')}</p>
                        </div>
                        <div className="mt-4 flex gap-2 justify-end">
                            <Link href={`/admin/loan-applications/${app.id}`} title="Lihat Detail" className="p-2 text-gray-500 hover:bg-gray-100 rounded-full"><Eye size={18} /></Link>
                            {app.status === 'SUBMITTED' && (<><button onClick={() => handleUpdateStatus(app.id, 'REJECTED')} className="p-2 text-red-600 hover:bg-red-100 rounded-full" title="Tolak"><XCircle size={18} /></button><button onClick={() => handleUpdateStatus(app.id, 'APPROVED')} className="p-2 text-green-600 hover:bg-green-100 rounded-full" title="Setujui"><CheckCircle size={18} /></button></>)}
                            {app.status === 'APPROVED' && (<button onClick={() => handleDisburse(app.id)} className="p-2 text-blue-600 hover:bg-blue-100 rounded-full" title="Cairkan Dana"><DollarSign size={18} /></button>)}
                        </div>
                    </div>
                ))}
            </div>
            <div className="hidden md:block bg-white rounded-lg shadow-md overflow-hidden">
                <table className="w-full">
                    <thead className="bg-gray-50"><tr><th className="p-4 text-left text-sm font-semibold text-gray-600">Nama Nasabah</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Produk</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Jumlah</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Tenor</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Tanggal</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Status</th><th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th></tr></thead>
                    <tbody className="divide-y">
                        {applications.map(app => (
                            <tr key={app.id}>
                                <td className="p-4 font-medium">{app.customer_name}</td>
                                <td className="p-4 text-sm text-gray-600">{app.product_name}</td>
                                <td className="p-4 text-sm text-gray-600">{formatCurrency(app.loan_amount)}</td>
                                <td className="p-4 text-sm text-gray-600">{formatTenor(app.tenor, app.tenor_unit)}</td>
                                <td className="p-4 text-sm text-gray-600">{new Date(app.application_date).toLocaleDateString('id-ID')}</td>
                                <td className="p-4"><StatusBadge status={app.status} /></td>
                                <td className="p-4">
                                    <div className="flex gap-2">
                                        <Link href={`/admin/loan-applications/${app.id}`} title="Lihat Detail" className="p-2 text-gray-500 hover:bg-gray-100 rounded-full"><Eye size={18} /></Link>
                                        {app.status === 'SUBMITTED' && (<><button onClick={() => handleUpdateStatus(app.id, 'APPROVED')} title="Setujui" className="p-2 text-green-600 hover:bg-green-100 rounded-full"><CheckCircle size={18} /></button><button onClick={() => handleUpdateStatus(app.id, 'REJECTED')} title="Tolak" className="p-2 text-red-600 hover:bg-red-100 rounded-full"><XCircle size={18} /></button></>)}
                                        {app.status === 'APPROVED' && (<button onClick={() => handleDisburse(app.id)} title="Cairkan Dana" className="p-2 text-blue-600 hover:bg-blue-100 rounded-full"><DollarSign size={18} /></button>)}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default LoanApplicationsPage;
