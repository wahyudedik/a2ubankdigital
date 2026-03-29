import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { PlusCircle, Edit, ToggleLeft, ToggleRight, Trash2 } from 'lucide-react';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Button from '@/components/ui/Button';
import UnitModal from '@/components/modals/UnitModal';

const StatusBadge = ({ status }) => {
    const isActive = status === 'ACTIVE';
    return <span className={`px-2 py-1 text-xs font-semibold rounded-full ${isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>{isActive ? 'Aktif' : 'Non-Aktif'}</span>;
};

const AdminUnitsPage = () => {
    const { branches } = usePage().props;
    const { callApi } = useApi();
    const modal = useModal();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedUnit, setSelectedUnit] = useState(null);

    const handleAdd = () => { setSelectedUnit(null); setIsModalOpen(true); };
    const handleEdit = (unitOrBranch) => { setSelectedUnit(unitOrBranch); setIsModalOpen(true); };
    const handleSave = () => { setIsModalOpen(false); router.reload(); };

    const handleToggleStatus = async (unitId, currentStatus) => {
        const newStatus = currentStatus === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        const actionText = newStatus === 'ACTIVE' ? 'mengaktifkan' : 'menonaktifkan';
        const confirmed = await modal.showConfirmation({ title: 'Konfirmasi', message: `Yakin ingin ${actionText} unit ini?`, confirmText: `Ya, ${actionText}` });
        if (confirmed) {
            const result = await callApi('admin_update_unit.php', 'PUT', { id: unitId, status: newStatus });
            if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: `Unit berhasil di-${actionText}.`, type: 'success' }); router.reload(); }
        }
    };

    const handleDelete = async (unitId, unitName) => {
        const confirmed = await modal.showConfirmation({ title: 'Hapus Unit', message: `Yakin ingin menghapus "${unitName}"? Tindakan ini tidak dapat dibatalkan.`, confirmText: 'Ya, Hapus' });
        if (confirmed) {
            const result = await callApi('admin_delete_unit.php', 'DELETE', { id: unitId });
            if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: 'Unit berhasil dihapus.', type: 'success' }); router.reload(); }
            else { modal.showAlert({ title: 'Gagal', message: result?.message || 'Gagal menghapus unit.', type: 'warning' }); }
        }
    };

    const branchList = Array.isArray(branches) ? branches : [];

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Manajemen Cabang & Unit</h1>
                <Button onClick={handleAdd} className="py-2 px-4 text-sm flex items-center gap-2"><PlusCircle size={18} />Tambah Baru</Button>
            </div>
            <div className="space-y-6">
                {branchList.map(branch => (
                    <div key={branch.id} className="bg-white rounded-lg shadow-md">
                        <div className="p-4 bg-gray-50 border-b flex justify-between items-center rounded-t-lg">
                            <div><h2 className="font-bold text-lg text-gray-800">{branch.unit_name}</h2><p className="text-sm text-gray-500">{branch.unit_type}</p></div>
                            <div className="flex items-center gap-3">
                                <StatusBadge status={branch.status} />
                                <button onClick={() => handleToggleStatus(branch.id, branch.status)} className="text-gray-500 hover:text-gray-800" title={branch.status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan'}>
                                    {branch.status === 'ACTIVE' ? <ToggleRight size={22} className="text-green-600" /> : <ToggleLeft size={22} />}
                                </button>
                                <button onClick={() => handleEdit(branch)} className="text-blue-600 hover:text-blue-800"><Edit size={18} /></button>
                                <button onClick={() => handleDelete(branch.id, branch.unit_name)} className="text-red-500 hover:text-red-700"><Trash2 size={18} /></button>
                            </div>
                        </div>
                        <div className="divide-y">
                            {branch.units?.length > 0 ? branch.units.map(unit => (
                                <div key={unit.id} className="p-4 flex justify-between items-center">
                                    <div><p className="font-medium text-gray-700">{unit.unit_name}</p><p className="text-xs text-gray-500">{unit.unit_type}</p></div>
                                    <div className="flex items-center gap-3">
                                        <StatusBadge status={unit.status} />
                                        <button onClick={() => handleToggleStatus(unit.id, unit.status)} className="text-gray-500 hover:text-gray-800" title={unit.status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan'}>
                                            {unit.status === 'ACTIVE' ? <ToggleRight size={22} className="text-green-600" /> : <ToggleLeft size={22} />}
                                        </button>
                                        <button onClick={() => handleEdit(unit)} className="text-blue-600 hover:text-blue-800"><Edit size={18} /></button>
                                        <button onClick={() => handleDelete(unit.id, unit.unit_name)} className="text-red-500 hover:text-red-700"><Trash2 size={18} /></button>
                                    </div>
                                </div>
                            )) : (<p className="p-4 text-sm text-gray-500">Belum ada unit di bawah cabang ini.</p>)}
                        </div>
                    </div>
                ))}
            </div>
            {isModalOpen && <UnitModal unit={selectedUnit} branches={branchList.map(b => ({ id: b.id, unit_name: b.unit_name }))} onClose={() => setIsModalOpen(false)} onSave={handleSave} />}
        </div>
    );
};

export default AdminUnitsPage;
