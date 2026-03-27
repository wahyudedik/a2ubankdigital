import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, PiggyBank } from 'lucide-react';
import Button from '@/components/ui/Button';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const formatTenor = (p) => { const unitMap = { 'HARI': 'Hari', 'MINGGU': 'Minggu', 'BULAN': 'Bulan' }; return `${p.min_tenor} - ${p.max_tenor} ${unitMap[p.tenor_unit] || p.tenor_unit}`; };

const LoanProductsListPage = () => {
    const { products } = usePage().props;

    return (
        <div>
            <Link href="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"><ArrowLeft size={20} /><h1 className="text-2xl font-bold text-gray-800">Pilih Produk Pinjaman</h1></Link>
            <div className="space-y-4">
                {(products || []).map(product => (
                    <div key={product.id} className="bg-white rounded-lg shadow-md p-4 border border-gray-200">
                        <div className="flex items-center mb-3"><div className="p-2 bg-taskora-green-100 rounded-full mr-3"><PiggyBank className="text-taskora-green-700" size={20} /></div><h2 className="text-lg font-bold text-gray-800">{product.product_name}</h2></div>
                        <div className="text-sm text-gray-600 space-y-2"><p><strong>Bunga:</strong> {product.interest_rate_pa}% per tahun</p><p><strong>Plafon:</strong> {formatCurrency(product.min_amount)} - {formatCurrency(product.max_amount)}</p><p><strong>Tenor:</strong> {formatTenor(product)}</p></div>
                        <div className="mt-4"><Link href={`/loan-application/${product.id}`}><Button fullWidth>Ajukan Sekarang</Button></Link></div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default LoanProductsListPage;
