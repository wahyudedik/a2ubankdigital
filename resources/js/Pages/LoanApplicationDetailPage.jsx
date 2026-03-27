import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

const DetailItem = ({ label, value }) => (
    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt className="text-sm font-medium text-gray-500">{label}</dt>
        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{value || '-'}</dd>
    </div>
);

const formatTenorUnit = (unit) => {
    const unitMap = { 'HARI': 'Hari', 'MINGGU': 'Minggu', 'BULAN': 'Bulan' };
    return unitMap[unit] || unit;
};

const LoanApplicationDetailPage = () => {
    const { loan } = usePage().props;
    const detail = loan;
    const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

    if (!detail) return <p>Data tidak ditemukan.</p>;

    return (
        <div>
            <Link href="/admin/loan-applications" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Detail Pengajuan Pinjaman</h1>
            </Link>
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="p-6 border-b">
                    <h3 className="text-lg font-medium text-gray-900">Informasi Nasabah</h3>
                    <dl className="mt-4 divide-y">
                        <DetailItem label="Nama" value={detail.customer_name} />
                        <DetailItem label="Email" value={detail.email} />
                        <DetailItem label="Telepon" value={detail.phone_number} />
                    </dl>
                </div>
                <div className="p-6">
                    <h3 className="text-lg font-medium text-gray-900">Informasi Pinjaman</h3>
                    <dl className="mt-4 divide-y">
                        <DetailItem label="Produk" value={detail.product_name} />
                        <DetailItem label="Jumlah Pinjaman" value={formatCurrency(detail.loan_amount)} />
                        <DetailItem label="Tenor" value={`${detail.tenor} ${formatTenorUnit(detail.tenor_unit)}`} />
                        <DetailItem label="Status" value={detail.status} />
                        <DetailItem label="Tanggal Pengajuan" value={new Date(detail.application_date).toLocaleString('id-ID')} />
                        {detail.approver_name && <DetailItem label="Disetujui Oleh" value={detail.approver_name} />}
                        {detail.approval_date && <DetailItem label="Tanggal Persetujuan" value={new Date(detail.approval_date).toLocaleString('id-ID')} />}
                        {detail.disbursement_date && <DetailItem label="Tanggal Pencairan" value={new Date(detail.disbursement_date).toLocaleString('id-ID')} />}
                    </dl>
                </div>
                {detail.installments && (
                    <div className="p-6 border-t">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Jadwal Angsuran</h3>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50"><tr><th className="p-2">Angsuran Ke-</th><th className="p-2">Jatuh Tempo</th><th className="p-2">Jumlah</th><th className="p-2">Pokok</th><th className="p-2">Bunga</th><th className="p-2">Denda</th><th className="p-2">Status</th></tr></thead>
                                <tbody className="divide-y">
                                    {detail.installments.map(i => (
                                        <tr key={i.id}>
                                            <td className="p-2 text-center">{i.installment_number}</td>
                                            <td className="p-2">{new Date(i.due_date).toLocaleDateString('id-ID')}</td>
                                            <td className="p-2">{formatCurrency(i.amount_due)}</td>
                                            <td className="p-2">{formatCurrency(i.principal_amount)}</td>
                                            <td className="p-2">{formatCurrency(i.interest_amount)}</td>
                                            <td className="p-2 text-red-600">{formatCurrency(i.penalty_amount || 0)}</td>
                                            <td className="p-2">{i.status}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default LoanApplicationDetailPage;
