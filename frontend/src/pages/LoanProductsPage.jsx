import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import { PlusCircle, Edit, Trash2 } from 'lucide-react';
import Button from '../components/ui/Button';
import LoanProductModal from '../components/modals/LoanProductModal';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

// --- FUNGSI BARU UNTUK FORMAT TENOR ---
const formatTenor = (p) => {
    // Mengubah format unit menjadi lebih ramah dibaca
    const unitMap = { 'HARI': 'Hari', 'MINGGU': 'Minggu', 'BULAN': 'Bulan' };
    const unitDisplay = unitMap[p.tenor_unit] || p.tenor_unit;
    return `${p.min_tenor} - ${p.max_tenor} ${unitDisplay}`;
};

const LoanProductsPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [products, setProducts] = useState([]);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);

    const fetchProducts = useCallback(async () => {
        const result = await callApi('admin_loan_products_get.php');
        if (result && result.status === 'success') {
            setProducts(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchProducts();
    }, [fetchProducts]);

    const handleAdd = () => {
        setSelectedProduct(null);
        setIsModalOpen(true);
    };

    const handleEdit = (product) => {
        setSelectedProduct(product);
        setIsModalOpen(true);
    };

    const handleDelete = async (productId) => {
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Hapus",
            message: "Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.",
            confirmText: "Ya, Hapus"
        });
        if (confirmed) {
            const result = await callApi('admin_loan_products_delete.php', 'POST', { id: productId });
            if (result && result.status === 'success') {
                 modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success'});
                fetchProducts();
            } else {
                 modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning'});
            }
        }
    };

    const handleSave = () => {
        setIsModalOpen(false);
        fetchProducts();
    };

    return (
        <div>
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Manajemen Produk Pinjaman</h1>
                <Button onClick={handleAdd} className="flex items-center gap-2 px-4 py-2 text-base">
                    <PlusCircle size={20} />
                    <span>Tambah Produk</span>
                </Button>
            </div>

            {error && <p className="text-red-500 mb-4">{error}</p>}
            
            <div className="md:hidden space-y-4">
                 {loading ? <p>Memuat...</p> : products.map(p => (
                    <div key={p.id} className="bg-white rounded-lg shadow-md p-4">
                         <div className="flex justify-between items-start">
                            <p className="font-bold text-gray-800">{p.product_name}</p>
                            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${p.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                {p.is_active ? 'Aktif' : 'Non-Aktif'}
                            </span>
                        </div>
                        <div className="mt-4 border-t pt-4 space-y-2 text-sm text-gray-600">
                           <p><strong>Bunga:</strong> {p.interest_rate_pa}% p.a</p>
                           <p><strong>Plafon:</strong> {formatCurrency(p.min_amount)} - {formatCurrency(p.max_amount)}</p>
                           {/* REVISI TAMPILAN TENOR */}
                           <p><strong>Tenor:</strong> {formatTenor(p)}</p>
                           <p><strong>Denda Harian:</strong> {formatCurrency(p.late_payment_fee)}</p>
                        </div>
                        <div className="mt-4 flex gap-2 justify-end">
                            <button onClick={() => handleDelete(p.id)} className="p-2 text-red-600 hover:bg-red-100 rounded-full"><Trash2 size={18}/></button>
                            <button onClick={() => handleEdit(p)} className="p-2 text-blue-600 hover:bg-blue-100 rounded-full"><Edit size={18}/></button>
                        </div>
                    </div>
                 ))}
            </div>

            <div className="hidden md:block bg-white rounded-lg shadow-md overflow-hidden">
                <table className="w-full">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Nama Produk</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Bunga (% p.a)</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Denda Harian</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Plafon</th>
                            {/* REVISI TAMPILAN TENOR */}
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Tenor</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Status</th>
                            <th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {loading ? (
                            <tr><td colSpan="7" className="p-8 text-center">Memuat...</td></tr>
                        ) : products.map(p => (
                            <tr key={p.id}>
                                <td className="p-4 font-medium">{p.product_name}</td>
                                <td className="p-4">{p.interest_rate_pa}%</td>
                                <td className="p-4">{formatCurrency(p.late_payment_fee)}</td>
                                <td className="p-4">{formatCurrency(p.min_amount)} - {formatCurrency(p.max_amount)}</td>
                                {/* REVISI TAMPILAN TENOR */}
                                <td className="p-4">{formatTenor(p)}</td>
                                <td className="p-4">
                                    <span className={p.is_active ? 'text-green-600' : 'text-gray-500'}>
                                        {p.is_active ? 'Aktif' : 'Non-Aktif'}
                                    </span>
                                </td>
                                <td className="p-4">
                                    <div className="flex gap-4">
                                        <button onClick={() => handleEdit(p)} className="text-blue-600 hover:text-blue-800"><Edit size={18}/></button>
                                        <button onClick={() => handleDelete(p.id)} className="text-red-600 hover:text-red-800"><Trash2 size={18}/></button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {isModalOpen && (
                <LoanProductModal 
                    product={selectedProduct} 
                    onClose={() => setIsModalOpen(false)} 
                    onSave={handleSave}
                />
            )}
        </div>
    );
};

export default LoanProductsPage;

