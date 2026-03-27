import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import { useModal } from '@/contexts/ModalContext.jsx';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const LoanApplicationPage = () => {
    const { product } = usePage().props;
    const navigate = useNavigate();
    const modal = useModal();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [formData, setFormData] = useState({ amount: product?.min_amount || '', tenor: product?.min_tenor || '', purpose: '' });

    const handleChange = (e) => { const { name, value } = e.target; setFormData(prev => ({ ...prev, [name]: value })); };

    const handleSubmit = (e) => {
        e.preventDefault(); setLoading(true); setError(null);
        const payload = { loan_product_id: product?.id, amount: formData.amount, tenor: formData.tenor, purpose: formData.purpose };
        router.post('/loan-application', payload, {
            onSuccess: () => { modal.showAlert({ title: 'Berhasil', message: 'Pengajuan pinjaman berhasil dikirim.', type: 'success' }); navigate('/my-loans'); },
            onError: (errors) => setError(Object.values(errors).flat()[0] || 'Terjadi kesalahan.'),
            onFinish: () => setLoading(false),
        });
    };

    if (!product) return <p className="text-center p-8">Produk pinjaman tidak ditemukan.</p>;

    const getTenorLabel = () => { const unitMap = { 'HARI': 'Hari', 'MINGGU': 'Minggu', 'BULAN': 'Bulan' }; return `Tenor (${product.min_tenor} - ${product.max_tenor} ${unitMap[product.tenor_unit] || product.tenor_unit})`; };

    return (
        <div>
            <Link href="/loan-products" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"><ArrowLeft size={20} /><h1 className="text-2xl font-bold text-gray-800">Form Pengajuan Pinjaman</h1></Link>
            <div className="bg-white p-6 rounded-lg shadow-md">
                <h2 className="text-lg font-bold text-gray-800 mb-1">{product?.product_name}</h2>
                <p className="text-sm text-gray-500 mb-6">Silakan isi jumlah dan tenor pinjaman yang Anda inginkan.</p>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <Input name="amount" type="number" label={`Jumlah Pinjaman (min: ${product?.min_amount}, max: ${product?.max_amount})`} value={formData.amount} onChange={handleChange} required />
                        <Input name="tenor" type="number" label={getTenorLabel()} value={formData.tenor} onChange={handleChange} required />
                        <Input name="purpose" label="Tujuan Pinjaman" value={formData.purpose} onChange={handleChange} placeholder="Contoh: Modal Usaha, Renovasi Rumah" required />
                    </div>
                    {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                    <div className="mt-6 border-t pt-6"><Button type="submit" fullWidth disabled={loading}>{loading ? 'Mengirim...' : 'Kirim Pengajuan'}</Button></div>
                </form>
            </div>
        </div>
    );
};

export default LoanApplicationPage;
