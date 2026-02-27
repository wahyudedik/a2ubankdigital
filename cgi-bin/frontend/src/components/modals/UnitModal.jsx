import React, { useState, useEffect } from 'react';
import useApi from '../../hooks/useApi';
import Input from '../ui/Input';
import Button from '../ui/Button';
import { X } from 'lucide-react';

const UnitModal = ({ unit, branches, onClose, onSave }) => {
    const { loading, error, callApi } = useApi();
    const [formData, setFormData] = useState({
        unit_name: '',
        unit_type: 'CABANG',
        parent_id: '',
        address: '',
        latitude: '',
        longitude: '',
        is_active: 1,
    });

    const isEditing = !!unit;

    useEffect(() => {
        if (isEditing) {
            setFormData({
                unit_name: unit.unit_name || '',
                unit_type: unit.unit_type || 'UNIT',
                parent_id: unit.parent_id || '',
                address: unit.address || '',
                latitude: unit.latitude || '',
                longitude: unit.longitude || '',
                is_active: unit.is_active,
            });
        }
    }, [unit, isEditing]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        const val = type === 'checkbox' ? (checked ? 1 : 0) : value;
        
        setFormData(prev => {
            const newState = { ...prev, [name]: val };
            if (name === 'unit_type' && val === 'CABANG') {
                newState.parent_id = '';
            }
            return newState;
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const endpoint = isEditing ? 'admin_update_unit.php' : 'admin_add_unit.php';
        const payload = isEditing ? { ...formData, id: unit.id } : formData;
        
        const result = await callApi(endpoint, 'POST', payload);
        if (result && result.status === 'success') {
            onSave();
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 relative">
                <button onClick={onClose} className="absolute top-4 right-4 text-gray-500 hover:text-gray-800">
                    <X size={24} />
                </button>
                <h2 className="text-2xl font-bold mb-6">{isEditing ? 'Edit' : 'Tambah'} Cabang / Unit</h2>
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <Input name="unit_name" label="Nama Cabang / Unit" value={formData.unit_name} onChange={handleChange} required />
                        <div>
                            <label className="block mb-2 text-sm font-medium">Jenis</label>
                            <select name="unit_type" value={formData.unit_type} onChange={handleChange} className="w-full p-2 border rounded">
                                <option value="CABANG">Cabang</option>
                                <option value="UNIT">Unit</option>
                            </select>
                        </div>
                        {formData.unit_type === 'UNIT' && (
                             <div>
                                <label className="block mb-2 text-sm font-medium">Di Bawah Cabang</label>
                                <select name="parent_id" value={formData.parent_id} onChange={handleChange} className="w-full p-2 border rounded" required>
                                    <option value="" disabled>Pilih Cabang Induk...</option>
                                    {branches.map(b => <option key={b.id} value={b.id}>{b.unit_name}</option>)}
                                </select>
                            </div>
                        )}
                        {formData.unit_type === 'CABANG' && (
                            <>
                                <Input name="address" label="Alamat" value={formData.address} onChange={handleChange} />
                                <div className="grid grid-cols-2 gap-4">
                                    <Input name="latitude" label="Latitude (Opsional)" value={formData.latitude} onChange={handleChange} />
                                    <Input name="longitude" label="Longitude (Opsional)" value={formData.longitude} onChange={handleChange} />
                                </div>
                            </>
                        )}
                        <div className="flex items-center gap-2">
                            <input type="checkbox" id="is_active" name="is_active" checked={!!formData.is_active} onChange={handleChange} className="h-4 w-4 rounded"/>
                            <label htmlFor="is_active">Aktif</label>
                        </div>
                    </div>
                    {error && <p className="text-red-500 text-sm mt-4">{error}</p>}
                    <div className="mt-6 flex justify-end gap-4 border-t pt-4">
                        <Button type="button" onClick={onClose} className="bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</Button>
                        <Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan'}</Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default UnitModal;