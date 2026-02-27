import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const LoanApplicationPage = () => {
    const { productId } = useParams();
    const navigate = useNavigate();
    const modal = useModal();
    const { loading, error, callApi } = useApi();
    const [product, setProduct] = useState(null);
    const [formData, setFormData] = useState({
        amount: '', // REVISI: Mengganti nama state
        tenor: '',  // REVISI: Mengganti nama state
        purpose: ''
    });

    useEffect(() => {
        const fetchProducts = async () => {
            const result = await callApi('user_loan_products_get.php');
            if (result && result.status === 'success') {
                const selected = result.data.find(p => p.id.toString() === productId);
                if (selected) {
                    setProduct(selected);
                    setFormData(prev => ({
                        ...prev,
                        amount: selected.min_amount || '',
                        tenor: selected.min_tenor || '' // REVISI: Menggunakan kolom baru
                    }));
                }
            }
        };
        fetchProducts();
    }, [callApi, productId]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const payload = {
            loan_product_id: productId,
            amount: formData.amount,
            tenor: formData.tenor,
            purpose: formData.purpose,
        };
        const result = await callApi('user_loan_application_create.php', 'POST', payload);
        if (result && result.status === 'success') {
            await modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success'});
            navigate('/my-loans');
        }
    };
    
    if (loading && !product) return <p className="text-center p-8">Memuat detail produk...</p>;
    if (!product) return <p className="text-center p-8">Produk pinjaman tidak ditemukan.</p>;

    // --- FUNGSI BARU UNTUK FORMAT LABEL TENOR ---
    const getTenorLabel = () => {
        const unitMap = { 'HARI': 'Hari', 'MINGGU': 'Minggu', 'BULAN': 'Bulan' };
        const unitDisplay = unitMap[product.tenor_unit] || product.tenor_unit;
        return `Tenor (${product.min_tenor} - ${product.max_tenor} ${unitDisplay})`;
    };

    return (
        <div>
            <Link to="/loan-products" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Form Pengajuan Pinjaman</h1>
            </Link>

            <div className="bg-white p-6 rounded-lg shadow-md">
                <h2 className="text-lg font-bold text-gray-800 mb-1">{product?.product_name}</h2>
                <p className="text-sm text-gray-500 mb-6">Silakan isi jumlah dan tenor pinjaman yang Anda inginkan.</p>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <Input 
                            name="amount" 
                            type="number" 
                            label={`Jumlah Pinjaman (min: ${product?.min_amount}, max: ${product?.max_amount})`} 
                            value={formData.amount} 
                            onChange={handleChange} 
                            required 
                        />
                        {/* REVISI: Menggunakan label dinamis */}
                        <Input 
                            name="tenor" 
                            type="number" 
                            label={getTenorLabel()}
                            value={formData.tenor} 
                            onChange={handleChange} 
                            required 
                        />
                        <Input 
                            name="purpose"
                            label="Tujuan Pinjaman"
                            value={formData.purpose}
                            onChange={handleChange}
                            placeholder="Contoh: Modal Usaha, Renovasi Rumah"
                            required
                        />
                    </div>
                    {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                    <div className="mt-6 border-t pt-6">
                        <Button type="submit" fullWidth disabled={loading}>
                            {loading ? 'Mengirim...' : 'Kirim Pengajuan'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default LoanApplicationPage;
