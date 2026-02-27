import React, { useState } from 'react';
import useApi from '../../hooks/useApi.js';
import Button from '../ui/Button.jsx';
import { X } from 'lucide-react';

const StaffAssignmentModal = ({ staff, units, onClose, onSave }) => {
    const { loading, error, callApi } = useApi();
    const [selectedUnitId, setSelectedUnitId] = useState(staff.unit_id || '');

    const handleSubmit = async (e) => {
        e.preventDefault();
        const payload = {
            staff_id: staff.id,
            unit_id: selectedUnitId
        };
        const result = await callApi('admin_update_staff_assignment.php', 'POST', payload);
        if (result && result.status === 'success') {
            onSave();
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div className="p-6 border-b flex justify-between items-center">
                    <h2 className="text-xl font-bold text-gray-800">Ubah Penugasan</h2>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-800">
                        <X size={24} />
                    </button>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="p-6 space-y-4">
                        <p>Ubah penugasan untuk staf: <strong className="font-semibold">{staff.full_name}</strong></p>
                        <div>
                            <label htmlFor="unit_id" className="block mb-2 text-sm font-medium text-gray-700">Penugasan Baru</label>
                            <select 
                                name="unit_id" 
                                id="unit_id" 
                                value={selectedUnitId} 
                                onChange={(e) => setSelectedUnitId(e.target.value)}
                                className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg" 
                                required
                            >
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
                        <Button type="submit" disabled={loading}>{loading ? 'Memperbarui...' : 'Simpan Perubahan'}</Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default StaffAssignmentModal;
