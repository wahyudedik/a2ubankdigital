import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import { PlusCircle, Edit, Trash2 } from 'lucide-react';
import Button from '../components/ui/Button';
import DepositProductModal from '../components/modals/DepositProductModal';
import AlertDialog from '../components/modals/AlertDialog';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const DepositProductsPage = () => {
    const { loading, error, callApi } = useApi();
    const { showModal, hideModal } = useModal();
    const [products, setProducts] = useState([]);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);

    const fetchProducts = useCallback(async () => {
        const result = await callApi('admin_get_deposit_products.php');
        if (result && result.status === 'success') {
            setProducts(result.data || []);
        }
    }, [callApi]);

    useEffect(() => {
        fetchProducts();
    }, [fetchProducts]);

    const handleAdd = () => {
        setSelectedProduct(null);
        setIsEditModalOpen(true);
    };

    const handleEdit = (product) => {
        setSelectedProduct(product);
        setIsEditModalOpen(true);
    };

    const handleSaveSuccess = () => {
        setIsEditModalOpen(false);
        fetchProducts();
    };

    const handleDelete = async (productId) => {
      try {
          hideModal(); // Tutup dialog konfirmasi dulu
          const result = await callApi('admin_loan_products_delete.php', 'post', { id: productId });
          if (result.status === 'success') {
              fetchProducts(); // Muat ulang data setelah berhasil
          } else {
              showModal(
                  <AlertDialog
                    title="Gagal Menghapus"
                    message={result.message || "Gagal menghapus produk."}
                    onConfirm={hideModal}
                  />
              );
          }
      } catch (err) {
          showModal(
              <AlertDialog
                title="Error"
                message={"Gagal menghapus produk. Kemungkinan produk ini masih terkait dengan data pinjaman lain."}
                onConfirm={hideModal}
              />
          );
      }
    };
    
    const confirmDelete = (productId) => {
        showModal(
          <AlertDialog
            title="Konfirmasi Hapus"
            message="Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan."
            onConfirm={() => handleDelete(productId)}
            onCancel={hideModal}
          />
        );
    };

    return (
        <div className="p-4 md:p-6 bg-gray-50 min-h-screen">
            <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                <h1 className="text-2xl font-bold text-gray-800">Manajemen Produk Deposito</h1>
                <Button onClick={handleAdd} className="w-full sm:w-auto">
                    <PlusCircle className="mr-2 h-4 w-4" /> Tambah Produk
                </Button>
            </div>

            {error && <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">{error}</div>}
            
            <div className="bg-white rounded-lg shadow-md overflow-hidden overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bunga (% p.a)</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenor (Bulan)</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minimum Deposit</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                        {loading ? (
                            <tr><td colSpan="6" className="p-8 text-center text-gray-500">Memuat...</td></tr>
                        ) : products.length > 0 ? products.map(p => (
                            <tr key={p.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{p.product_name}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{p.interest_rate_pa}%</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{p.tenor_months}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{formatCurrency(p.min_amount)}</td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${p.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                        {p.is_active ? 'Aktif' : 'Non-Aktif'}
                                    </span>
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div className="flex justify-end gap-2">
                                        <Button variant="outline" size="sm" onClick={() => handleEdit(p)}><Edit size={16}/></Button>
                                        <Button variant="destructive" size="sm" onClick={() => confirmDelete(p.id)}><Trash2 size={16}/></Button>
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr><td colSpan="6" className="p-8 text-center text-gray-500">Tidak ada produk yang ditemukan.</td></tr>
                        )}
                    </tbody>
                </table>
            </div>

            {isEditModalOpen && (
                <DepositProductModal 
                    product={selectedProduct} 
                    onClose={() => setIsEditModalOpen(false)} 
                    onSuccess={handleSaveSuccess}
                />
            )}
        </div>
    );
};

export default DepositProductsPage;

