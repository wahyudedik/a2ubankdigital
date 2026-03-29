import React, { useState, useEffect } from 'react';
import useApi from '@/hooks/useApi';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { X } from 'lucide-react';

const UnitModal = ({ unit, branches, onClose, onSave }) => {
    const { loading, error, callApi } = useApi();
    const [formData, setFormData] = useState({ unit_name: '', unit_type: 'KANTOR_KAS', parent_id: '', address: '', phone: '' });
    const [validationError, setValidationError] = useState('');
    const isEditing = !!unit;
    useEffect(() => { if (isEditing) setFormData({ unit_name: unit.unit_name || '', unit_type: unit.unit_type || 'KANTOR_KAS', parent_id: unit.parent_id || '', address: unit.address || '', phone: unit.phone || '' }); }, [unit, isEditing]);
    const handleChange = (e) => { const { name, value } = e.target; setFormData(prev => ({ ...prev, [name]: value })); setValidationError(''); };
    const handleSubmit = async (e) => {
        e.preventDefault();
        setValidationError('');

        // Client-side validation for parent_id
        if (formData.unit_type === 'KANTOR_KAS' && !formData.parent_id) {
            setValidationError('Cabang Induk harus dipilih untuk Kantor Kas');
            return;
        }

        // Prepare payload - ensure parent_id is properly handled
        const payload = {
            unit_name: formData.unit_name,
            unit_type: formData.unit_type,
            address: formData.address,
            phone: formData.phone
        };

        // Include parent_id only if it's set (for KANTOR_KAS)
        if (formData.parent_id) {
            payload.parent_id = formData.parent_id;
        }

        let result;
        if (isEditing) {
            result = await callApi('admin_update_unit.php', 'PUT', { ...payload, id: unit.id });
        } else {
            result = await callApi('admin_add_unit.php', 'POST', payload);
        }
        if (result && result.status === 'success') onSave();
    };
    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div className="p-6 border-b flex justify-between items-center"><h2 className="text-xl font-bold text-gray-800">{isEditing ? 'Edit' : 'Tambah'} Unit</h2><button onClick={onClose} className="text-gray-500 hover:text-gray-800"><X size={24} /></button></div>
                <form onSubmit={handleSubmit}>
                    <div className="p-6 space-y-4">
                        <Input name="unit_name" label="Nama Unit" value={formData.unit_name} onChange={handleChange} required />
                        <div><label className="block mb-2 text-sm font-medium text-gray-700">Tipe</label>
                            <select name="unit_type" value={formData.unit_type} onChange={handleChange} className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg">
                                <option value="KANTOR_CABANG">Kantor Cabang</option>
                                <option value="KANTOR_KAS">Kantor Kas / Unit</option>
                            </select>
                        </div>
                        {formData.unit_type === 'KANTOR_KAS' && (
                            <div><label className="block mb-2 text-sm font-medium text-gray-700">Cabang Induk</label>
                                <select name="parent_id" value={formData.parent_id} onChange={handleChange} className="w-full px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg" required>
                                    <option value="">Pilih Cabang...</option>
                                    {branches.map(b => (<option key={b.id} value={b.id}>{b.unit_name}</option>))}
                                </select>
                            </div>
                        )}
                        <Input name="address" label="Alamat" value={formData.address} onChange={handleChange} />
                        <Input name="phone" label="Telepon" value={formData.phone} onChange={handleChange} />
                    </div>
                    {error && <p className="text-red-500 text-sm px-6 pb-4">{error}</p>}
                    {validationError && <p className="text-red-500 text-sm px-6 pb-4">{validationError}</p>}
                    <div className="bg-gray-50 px-6 py-4 flex justify-end gap-4 rounded-b-lg">
                        <Button type="button" onClick={onClose} className="bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</Button>
                        <Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan'}</Button>
                    </div>
                </form>
            </div>
        </div>
    );
};
export default UnitModal;
