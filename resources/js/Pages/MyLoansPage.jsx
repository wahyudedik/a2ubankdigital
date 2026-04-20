import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Landmark, ChevronRight, PlusCircle } from 'lucide-react';
import Button from '@/components/ui/Button';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const formatDate = (dateStr) => new Date(dateStr).toLocaleDateString('id-ID');

const STATUS_BADGE = {
    SUBMITTED: { label: 'Diajukan', className: 'bg-yellow-100 text-yellow-800' },
    APPROVED: { label: 'Disetujui', className: 'bg-blue-100 text-blue-800' },
    REJECTED: { label: 'Ditolak', className: 'bg-red-100 text-red-800' },
    DISBURSED: { label: 'Aktif', className: 'bg-green-100 text-green-800' },
    ACTIVE: { label: 'Aktif', className: 'bg-green-100 text-green-800' },
    COMPLETED: { label: 'Lunas', className: 'bg-gray-100 text-gray-600' },
};

const StatusBadge = ({ status }) => {
    const badge = STATUS_BADGE[status] || { label: status, className: 'bg-gray-100 text-gray-600' };
    return <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${badge.className}`}>{badge.label}</span>;
};

const LoanDateInfo = ({ loan }) => {
    if (loan.disbursed_at) {
        return <p className="text-xs text-gray-500">Dicairkan pada: {formatDate(loan.disbursed_at)}</p>;
    }
    return <p className="text-xs text-gray-500">Diajukan: {formatDate(loan.created_at)}</p>;
};

const MyLoansPage = () => {
    const { loans } = usePage().props;

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <Link href="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900"><ArrowLeft size={20} /><h1 className="text-2xl font-bold text-gray-800">Pinjaman Saya</h1></Link>
                <Link href="/loan-products"><Button className="py-2 px-4 text-sm flex items-center gap-2"><PlusCircle size={18} /><span>Ajukan Pinjaman</span></Button></Link>
            </div>
            <div className="space-y-4">
                {(loans || []).length > 0 ? (loans || []).map(loan => (
                    <Link href={`/my-loans/${loan.id}`} key={loan.id} className="block bg-white rounded-lg shadow-md p-4 border border-gray-200 hover:bg-gray-50">
                        <div className="flex justify-between items-center">
                            <div>
                                <div className="flex items-center gap-2 mb-1">
                                    <p className="font-bold text-gray-800">{loan.product_name}</p>
                                    <StatusBadge status={loan.status} />
                                </div>
                                <p className="text-sm text-gray-600">Total Pinjaman: {formatCurrency(loan.loan_amount)}</p>
                                <LoanDateInfo loan={loan} />
                            </div>
                            <ChevronRight className="text-gray-400" />
                        </div>
                    </Link>
                )) : (
                    <div className="text-center text-gray-500 py-8"><Landmark size={48} className="mx-auto text-gray-300 mb-4" /><p>Anda tidak memiliki pinjaman aktif saat ini.</p></div>
                )}
            </div>
        </div>
    );
};

export default MyLoansPage;
