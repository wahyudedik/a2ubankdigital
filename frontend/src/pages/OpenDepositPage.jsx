import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import { ArrowLeft, Database } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const OpenDepositPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const navigate = useNavigate();
    const [products, setProducts] = useState([]);
    const [selectedProductId, setSelectedProductId] = useState('');
    const [amount, setAmount] = useState('');

    useEffect(() => {
        const fetchProducts = async () => {
            const result = await callApi('deposit_products_get_list.php');
            if (result && result.status === 'success') {
                setProducts(result.data);
                if (result.data.length > 0) {
                    setSelectedProductId(result.data[0].id); // Pilih produk pertama sebagai default
                }
            }
        };
        fetchProducts();
    }, [callApi]);

    const selectedProduct = products.find(p => p.id.toString() === selectedProductId.toString());

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!selectedProduct) return;

        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Pembukaan Deposito",
            message: `Anda akan menempatkan dana sebesar ${formatCurrency(amount)} pada produk ${selectedProduct.product_name}. Dana akan diambil dari rekening tabungan Anda. Lanjutkan?`,
            confirmText: "Ya, Lanjutkan"
        });

        if (confirmed) {
            const payload = { product_id: selectedProductId, amount };
            const result = await callApi('deposit_account_create.php', 'POST', payload);
            if (result && result.status === 'success') {
                await modal.showAlert({ title: "Berhasil", message: result.message, type: "success"});
                navigate('/deposits');
            } else {
                modal.showAlert({ title: "Gagal", message: error || result?.message, type: "warning"});
            }
        }
    };

    return (
        <div>
            <Link to="/deposits" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Buka Deposito Baru</h1>
            </Link>

            <div className="bg-white p-6 rounded-lg shadow-md">
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <div>
                            <label htmlFor="product_id" className="block mb-2 text-sm font-medium text-gray-700">Pilih Produk</label>
                            <select
                                id="product_id"
                                name="product_id"
                                value={selectedProductId}
                                onChange={(e) => setSelectedProductId(e.target.value)}
                                className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-taskora-green-300"
                            >
                                {products.map(p => (
                                    <option key={p.id} value={p.id}>{p.product_name} ({p.interest_rate_pa}% p.a)</option>
                                ))}
                            </select>
                        </div>
                        
                        {selectedProduct && (
                             <div className="text-sm text-gray-500 bg-gray-50 p-3 rounded-md">
                                Tenor: {selectedProduct.tenor_months} bulan | Minimum: {formatCurrency(selectedProduct.min_amount)}
                            </div>
                        )}

                        <Input 
                            name="amount" 
                            type="number"
                            label="Jumlah Penempatan Dana (Rp)"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            placeholder="Contoh: 5000000"
                            required
                        />
                    </div>
                    {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                    <div className="mt-6 border-t pt-6">
                        <Button type="submit" fullWidth disabled={loading || !selectedProductId}>
                            {loading ? 'Memproses...' : 'Buka Deposito'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default OpenDepositPage;
