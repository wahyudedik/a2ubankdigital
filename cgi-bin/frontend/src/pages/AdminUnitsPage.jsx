import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { PlusCircle, Edit } from 'lucide-react';
import Button from '../components/ui/Button';
import UnitModal from '../components/modals/UnitModal';

const StatusBadge = ({ isActive }) => (
    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
        {isActive ? 'Aktif' : 'Non-Aktif'}
    </span>
);

const AdminUnitsPage = () => {
    const { loading, error, callApi } = useApi();
    const [branches, setBranches] = useState([]);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedUnit, setSelectedUnit] = useState(null);

    const fetchUnits = useCallback(async () => {
        const result = await callApi('admin_get_units.php');
        if (result && result.status === 'success') {
            setBranches(Array.isArray(result.data) ? result.data : []);
        }
    }, [callApi]);

    useEffect(() => {
        fetchUnits();
    }, [fetchUnits]);

    const handleAdd = () => {
        setSelectedUnit(null);
        setIsModalOpen(true);
    };

    const handleEdit = (unitOrBranch, isBranch = false) => {
        if (isBranch) {
            setSelectedUnit({ ...unitOrBranch, parent_id: null });
        } else {
            setSelectedUnit(unitOrBranch);
        }
        setIsModalOpen(true);
    };

    const handleSave = () => {
        setIsModalOpen(false);
        fetchUnits();
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Manajemen Cabang & Unit</h1>
                <Button onClick={handleAdd} className="py-2 px-4 text-sm flex items-center gap-2">
                    <PlusCircle size={18} />
                    Tambah Baru
                </Button>
            </div>

            {loading && <p>Memuat data...</p>}
            {error && <p className="text-red-500">{error}</p>}

            <div className="space-y-6">
                {Array.isArray(branches) && branches.map(branch => (
                    <div key={branch.id} className="bg-white rounded-lg shadow-md">
                        <div className="p-4 bg-gray-50 border-b flex justify-between items-center rounded-t-lg">
                            <div>
                                <h2 className="font-bold text-lg text-gray-800">{branch.unit_name}</h2>
                                <p className="text-sm text-gray-500">CABANG</p>
                            </div>
                            <div className="flex items-center gap-4">
                                <StatusBadge isActive={branch.is_active} />
                                <button onClick={() => handleEdit(branch, true)} className="text-blue-600 hover:text-blue-800">
                                    <Edit size={18} />
                                </button>
                            </div>
                        </div>
                        <div className="divide-y">
                            {branch.units?.length > 0 ? branch.units.map(unit => (
                                <div key={unit.id} className="p-4 flex justify-between items-center">
                                    <div>
                                        <p className="font-medium text-gray-700">{unit.unit_name}</p>
                                        <p className="text-xs text-gray-500">{unit.unit_type}</p>
                                    </div>
                                     <div className="flex items-center gap-4">
                                        <StatusBadge isActive={unit.is_active} />
                                        <button onClick={() => handleEdit(unit)} className="text-blue-600 hover:text-blue-800">
                                            <Edit size={18} />
                                        </button>
                                    </div>
                                </div>
                            )) : (
                                <p className="p-4 text-sm text-gray-500">Belum ada unit di bawah cabang ini.</p>
                            )}
                        </div>
                    </div>
                ))}
            </div>
            {isModalOpen && (
                <UnitModal 
                    unit={selectedUnit}
                    branches={branches.map(b => ({ id: b.id, unit_name: b.unit_name }))}
                    onClose={() => setIsModalOpen(false)}
                    onSave={handleSave}
                />
            )}
        </div>
    );
};

export default AdminUnitsPage;
