import React, { useState } from 'react';
import useApi from '../../hooks/useApi.js';
import { useModal } from '../../contexts/ModalContext.jsx';
import Input from '../ui/Input.jsx';
import Button from '../ui/Button.jsx';
import { X } from 'lucide-react';

const StaffModal = ({ roles, units, onClose, onSave }) => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [formData, setFormData] = useState({
        full_name: '',
        email: '',
        role_id: roles[0]?.id || '',
        unit_id: ''
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const result = await callApi('admin_create_staff_user.php', 'POST', formData);
        if (result && result.status === 'success') {
            onSave();
            modal.showAlert({
                title: 'Staf Berhasil Dibuat',
                message: `Akun untuk ${result.data.email} telah dibuat. Password sementara: ${result.data.temporary_password}. Harap catat dan berikan kepada staf terkait.`,
                type: 'success'
            });
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div className="p-6 border-b flex justify-between items-center">
                    <h2 className="text-xl font-bold text-gray-800">Tambah Staf Baru</h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-800">
                        <X size={24} />
                    </button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="p-6 space-y-4">
                        <Input name="full_name" label="Nama Lengkap" value={formData.full_name} onChange={handleChange} required />
                        <Input name="email" type="email" label="Email" value={formData.email} onChange={handleChange} required />
                        <div>
                            <label htmlFor="role_id" className="block mb-2 text-sm font-medium text-gray-700">Peran</label>
                            <select name="role_id" id="role_id" value={formData.role_id} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg">
                                {roles.map(role => (
                                    <option key={role.id} value={role.id}>{role.role_name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="unit_id" className="block mb-2 text-sm font-medium text-gray-700">Penugasan</label>
                            <select name="unit_id" id="unit_id" value={formData.unit_id} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg" required>
                                <option value="" disabled>Pilih Cabang atau Unit...</option>
                                {units.map(branch => (
                                    <optgroup key={branch.id} label={branch.unit_name}>
                                        <option value={branch.id}>{branch.unit_name} (Level Cabang)</option>
                                        {branch.units?.map(unit => (
                                            <option key={unit.id} value={unit.id}>{unit.unit_name}</option>
                                        ))}
                                    </optgroup>
                                ))}
                            </select>
                        </div>
                    </div>
                    {error && <p className="text-red-500 text-sm px-6 pb-4">{error}</p>}
                    <div className="bg-gray-50 px-6 py-4 flex justify-end gap-4 rounded-b-lg">
                        <Button type="button" onClick={onClose} className="bg-gray-200 text-gray-800 hover:bg-gray-300">Batal</Button>
                        <Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan Staf'}</Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default StaffModal;
