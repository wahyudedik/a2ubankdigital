import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import { ArrowLeft, Loader2, Info } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const BillPaymentPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const navigate = useNavigate();
    
    const [step, setStep] = useState(1);
    const [products, setProducts] = useState([]);
    const [groupedProducts, setGroupedProducts] = useState({});
    
    const [selectedCategory, setSelectedCategory] = useState('');

    // Menggunakan satu state object untuk form
    const [formData, setFormData] = useState({
        product_code: '', // Mengganti nama dari product_id
        customer_no: ''     // Mengganti nama dari customer_id
    });
    
    const [inquiryResult, setInquiryResult] = useState(null);
    const [pin, setPin] = useState('');

    const fetchProducts = useCallback(async () => {
        const result = await callApi('utility_get_billers.php');
        if (result && result.status === 'success' && Array.isArray(result.data)) {
            // Menyaring produk GAME dan mengelompokkan
            const activeProducts = result.data.filter(p => p.category !== 'Games');
            setProducts(activeProducts);
            const grouped = activeProducts.reduce((acc, product) => {
                const category = product.category || 'Lainnya';
                if (!acc[category]) acc[category] = [];
                acc[category].push(product);
                return acc;
            }, {});
            setGroupedProducts(grouped);
        }
    }, [callApi]);

    useEffect(() => {
        fetchProducts();
    }, [fetchProducts]);
    
    // Fungsi untuk menangani perubahan input form
    const handleChange = (e) => {
        setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const handleCategoryChange = (e) => {
        setSelectedCategory(e.target.value);
        // Reset pilihan produk saat kategori berubah
        setFormData(prev => ({ ...prev, product_code: '' }));
    };

    const handleInquiry = async (e) => {
        e.preventDefault();
        const selectedProduct = products.find(p => p.buyer_sku_code === formData.product_code);
        
        if (!selectedProduct) {
            modal.showAlert({ title: "Error", message: "Silakan pilih produk yang valid.", type: "warning" });
            return;
        }
        
        // Cek tipe produk, Pascabayar butuh inquiry, Prabayar tidak
        if (selectedProduct.type.toLowerCase() === 'pasca') {
            const result = await callApi('bill_payment_inquiry.php', 'POST', formData);
            if (result && result.status === 'success') {
                setInquiryResult({...result.data, product_code: formData.product_code});
                setStep(2);
            }
        } else { // Untuk produk Prabayar
            setInquiryResult({
                product_name: selectedProduct.product_name,
                customer_name: 'N/A', // Nama tidak tersedia untuk prabayar
                customer_no: formData.customer_no,
                selling_price: selectedProduct.price,
                admin_fee: 0,
                total_amount: selectedProduct.price,
                product_code: formData.product_code, // Teruskan product code
                description: selectedProduct.product_name, // Gunakan nama produk sbg deskripsi
            });
            setStep(2);
        }
    };
    
    const handlePayment = async (e) => {
        e.preventDefault();
        // Mengirim payload yang dibutuhkan oleh backend
        const payload = { 
            pin: pin,
            buyer_sku_code: inquiryResult.product_code,
            customer_no: inquiryResult.customer_no,
            amount: inquiryResult.selling_price,
            fee: inquiryResult.admin_fee,
            total: inquiryResult.total_amount,
            description: inquiryResult.product_name
        };
        const result = await callApi('bill_payment_execute.php', 'POST', payload);
        if (result && result.status === 'success') {
            await modal.showAlert({ title: "Permintaan Terkirim", message: result.message, type: "success" });
            navigate('/dashboard');
        }
    };

    return (
        <div>
            <Link to="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Bayar & Beli</h1>
            </Link>

            <div className="bg-white p-6 rounded-lg shadow-md max-w-md mx-auto">
                {step === 1 && (
                    <form onSubmit={handleInquiry} className="space-y-4">
                        <h2 className="text-lg font-semibold text-gray-800">Langkah 1: Pilih Produk & Tujuan</h2>
                        <div>
                            <label className="block mb-2 text-sm font-medium text-gray-700">Pilih Kategori</label>
                            <select value={selectedCategory} onChange={handleCategoryChange} className="w-full p-2 border rounded-lg bg-gray-50" required>
                                <option value="" disabled>-- Pilih Kategori --</option>
                                {Object.keys(groupedProducts).sort().map(category => (
                                    <option key={category} value={category}>{category}</option>
                                ))}
                            </select>
                        </div>

                        {selectedCategory && (
                            <div>
                                <label className="block mb-2 text-sm font-medium text-gray-700">Pilih Produk</label>
                                <select name="product_code" value={formData.product_code} onChange={handleChange} className="w-full p-2 border rounded-lg bg-gray-50" required>
                                    <option value="" disabled>-- Pilih Produk --</option>
                                    {groupedProducts[selectedCategory].map(p => (
                                        <option key={p.buyer_sku_code} value={p.buyer_sku_code}>{p.product_name}</option>
                                    ))}
                                </select>
                            </div>
                        )}
                        <Input name="customer_no" label="Nomor Tujuan / ID Pelanggan" value={formData.customer_no} onChange={handleChange} required />
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-6">
                            <Button type="submit" fullWidth disabled={loading}>{loading ? <><Loader2 className="animate-spin mr-2"/> Mengecek...</> : 'Lanjutkan'}</Button>
                        </div>
                    </form>
                )}

                {step === 2 && inquiryResult && (
                    <form onSubmit={handlePayment} className="space-y-4">
                        <h2 className="text-lg font-semibold text-gray-800">Langkah 2: Konfirmasi Pembayaran</h2>
                        <div className="bg-gray-50 p-4 rounded-lg text-sm space-y-2">
                            <div className="flex justify-between"><span className="text-gray-500">Produk</span><span className="font-semibold text-right">{inquiryResult.product_name}</span></div>
                            {inquiryResult.customer_name && inquiryResult.customer_name !== 'N/A' && <div className="flex justify-between"><span className="text-gray-500">Nama Pelanggan</span><span className="font-semibold text-right">{inquiryResult.customer_name}</span></div>}
                            <div className="flex justify-between"><span className="text-gray-500">Nomor Tujuan</span><span className="font-semibold text-right">{inquiryResult.customer_no}</span></div>
                            <div className="flex justify-between"><span className="text-gray-500">Harga</span><span className="font-semibold text-right">{formatCurrency(inquiryResult.selling_price)}</span></div>
                            <div className="flex justify-between"><span className="text-gray-500">Biaya Admin</span><span className="font-semibold text-right">{formatCurrency(inquiryResult.admin_fee)}</span></div>
                            <div className="flex justify-between font-bold text-lg border-t pt-2 mt-2"><span>Total Bayar</span><span className="text-taskora-green-700">{formatCurrency(inquiryResult.total_amount)}</span></div>
                        </div>
                        <Input name="pin" type="password" label="Masukkan PIN" value={pin} onChange={(e) => setPin(e.target.value)} required maxLength="6" />
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-6 flex gap-2">
                             <Button type="button" onClick={() => setStep(1)} className="bg-gray-200 text-gray-800">Kembali</Button>
                             <Button type="submit" fullWidth disabled={loading}>{loading ? <><Loader2 className="animate-spin mr-2"/> Memproses...</> : 'Bayar Sekarang'}</Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
};

export default BillPaymentPage;

