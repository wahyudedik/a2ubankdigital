import React, { useState, useEffect } from 'react';
import useApi from '@/hooks/useApi';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { X } from 'lucide-react';

const LoanProductModal = ({ product, onClose, onSave }) => {
    const { loading, error, callApi } = useApi();
    const [formData, setFormData] = useState({ product_name: '', interest_rate_pa: '', min_amount: '', max_amount: '', min_tenor: '', max_tenor: '', tenor_unit: 'BULAN', is_active: 1, late_payment_fee: '0' });
    const isEditing = !!product;
    useEffect(() => { if (isEditing) setFormData({ product_name: product.product_name || '', interest_rate_pa: product.interest_rate_pa || '', min_amount: product.min_amount || '', max_amount: product.max_amount || '', min_tenor: product.min_tenor || '', max_tenor: product.max_tenor || '', tenor_unit: product.tenor_unit || 'BULAN', is_active: product.is_active, late_payment_fee: product.late_payment_fee || '0' }); }, [product, isEditing]);
    const handleChange = (e) => { const { name, value, type, checked } = e.target; setFormData(prev => ({ ...prev, [name]: type === 'checkbox' ? (checked ? 1 : 0) : value })); };
    const handleSubmit = async (e) => {
        e.preventDefault();
        const endpoint = isEditing ? 'admin_loan_products_edit.php' : 'admin_loan_products_add.php';
        const method = isEditing ? 'PUT' : 'POST';
        const numericPayload = {
            product_name: formData.product_name,
            interest_rate_pa: parseFloat(formData.interest_rate_pa),
            late_payment_fee: parseFloat(formData.late_payment_fee) || 0,
            min_amount: parseFloat(formData.min_amount),
            max_amount: parseFloat(formData.max_amount),
            min_tenor: parseInt(formData.min_tenor, 10),
            max_tenor: parseInt(formData.max_tenor, 10),
            tenor_unit: formData.tenor_unit,
            is_active: formData.is_active,
        };
        const payload = isEditing ? { ...numericPayload, id: product.id } : numericPayload;
        const result = await callApi(endpoint, method, payload);
        if (result && result.status === 'success') onSave();
    };
    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 relative">
                <button onClick={onClose} className="absolute top-4 right-4 text-gray-500 hover:text-gray-800"><X size={24} /></button>
                <h2 className="text-2xl font-bold mb-6">{isEditing ? 'Edit' : 'Tambah'} Produk Pinjaman</h2>
                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input name="product_name" label="Nama Produk" value={formData.product_name} onChange={handleChange} required className="md:col-span-2" />
                        <Input name="interest_rate_pa" type="number" label="Bunga (% per Tahun)" value={formData.interest_rate_pa} onChange={handleChange} required step="0.01" />
                        <Input name="late_payment_fee" type="number" label="Denda Harian (Rp)" value={formData.late_payment_fee} onChange={handleChange} required />
                        <Input name="min_amount" type="number" label="Plafon Minimum" value={formData.min_amount} onChange={handleChange} required />
                        <Input name="max_amount" type="number" label="Plafon Maksimum" value={formData.max_amount} onChange={handleChange} required />
                        <Input name="min_tenor" type="number" label="Tenor Minimum" value={formData.min_tenor} onChange={handleChange} required />
                        <div className="grid grid-cols-2 gap-2">
                            <Input name="max_tenor" type="number" label="Tenor Maksimum" value={formData.max_tenor} onChange={handleChange} required />
                            <div><label className="block mb-2 text-sm font-medium">Satuan</label><select name="tenor_unit" value={formData.tenor_unit} onChange={handleChange} className="w-full p-2 border rounded-lg bg-gray-50 h-[42px]"><option value="BULAN">Bulan</option><option value="TAHUN">Tahun</option></select></div>
                        </div>
                        <div className="flex items-center gap-2 mt-2 md:col-span-2"><input type="checkbox" id="is_active" name="is_active" checked={!!formData.is_active} onChange={handleChange} className="h-4 w-4 rounded border-gray-300" /><label htmlFor="is_active" className="text-sm font-medium text-gray-700">Aktifkan Produk</label></div>
                    </div>
                    {error && <p className="text-red-500 text-sm mt-4">{error}</p>}
                    <div className="mt-6 flex justify-end gap-4 border-t pt-4"><Button type="button" onClick={onClose} className="bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</Button><Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan'}</Button></div>
                </form>
            </div>
        </div>
    );
};
export default LoanProductModal;
