import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi.js';
import { useModal } from '../contexts/ModalContext.jsx';
import Button from '../components/ui/Button.jsx';
import StaffModal from '../components/modals/StaffModal.jsx';
import StaffAssignmentModal from '../components/modals/StaffAssignmentModal.jsx';
import { PlusCircle, UserCheck, UserX, Shuffle, Edit } from 'lucide-react';
import { Link } from 'react-router-dom';

const StatusBadge = ({ status }) => (
    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
        status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
    }`}>
        {status === 'ACTIVE' ? 'Aktif' : 'Non-Aktif'}
    </span>
);

const StaffListPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [staffList, setStaffList] = useState([]);
    const [roles, setRoles] = useState([]);
    const [units, setUnits] = useState([]);
    const [isAddModalOpen, setAddModalOpen] = useState(false);
    const [isAssignModalOpen, setAssignModalOpen] = useState(false);
    const [selectedStaff, setSelectedStaff] = useState(null);

    const fetchData = useCallback(async () => {
        const [staffResult, rolesResult, unitsResult] = await Promise.all([
            callApi('admin_get_staff_list.php'),
            callApi('admin_get_roles.php'),
            callApi('admin_get_units.php')
        ]);
        
        if (staffResult && staffResult.status === 'success') setStaffList(staffResult.data);
        if (rolesResult && rolesResult.status === 'success') setRoles(rolesResult.data.filter(r => r.role_name !== 'Nasabah'));
        if (unitsResult && unitsResult.status === 'success') {
            setUnits(unitsResult.data);
        }
        
    }, [callApi]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const handleSave = () => {
        setAddModalOpen(false);
        setAssignModalOpen(false);
        fetchData();
    };

    const handleOpenAssignModal = (staff) => {
        setSelectedStaff(staff);
        setAssignModalOpen(true);
    };

    const handleUpdateStatus = async (staffId, currentStatus) => {
        const newStatus = currentStatus === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        const actionText = newStatus === 'ACTIVE' ? 'mengaktifkan' : 'menonaktifkan';
        
        const confirmed = await modal.showConfirmation({
            title: `Konfirmasi Status`,
            message: `Apakah Anda yakin ingin ${actionText} akun staf ini?`,
            confirmText: `Ya, ${actionText}`
        });

        if (confirmed) {
            const result = await callApi('admin_update_staff_status.php', 'POST', { staff_id: staffId, new_status: newStatus });
            if (result && result.status === 'success') {
                modal.showAlert({ title: 'Berhasil', message: 'Status staf telah diperbarui.', type: 'success' });
                fetchData();
            }
        }
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Manajemen Staf</h1>
                <Button onClick={() => setAddModalOpen(true)} className="py-2 px-4 text-sm flex items-center gap-2">
                    <PlusCircle size={18}/> Tambah Staf
                </Button>
            </div>

            {error && <p className="text-red-500 mb-4">{error}</p>}
            
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full min-w-max">
                        <thead className="bg-gray-50 border-b">
                            <tr>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Nama Lengkap</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Peran</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Cabang</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Unit</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Status</th>
                                <th className="p-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {loading && staffList.length === 0 ? (
                                <tr><td colSpan="6" className="p-8 text-center text-gray-500">Memuat...</td></tr>
                            ) : staffList.map(staff => (
                                <tr key={staff.id}>
                                    <td className="p-4">
                                        <p className="font-medium text-gray-800">{staff.full_name}</p>
                                        <p className="text-xs text-gray-500">{staff.email}</p>
                                    </td>
                                    <td className="p-4 text-gray-600 text-sm">{staff.role_name}</td>
                                    <td className="p-4 text-gray-600 text-sm">{staff.branch_name || '-'}</td>
                                    <td className="p-4 text-gray-600 text-sm">{staff.unit_name || '-'}</td>
                                    <td className="p-4"><StatusBadge status={staff.status} /></td>
                                    <td className="p-4">
                                        <div className="flex gap-2">
                                            {staff.can_edit && (
                                                <Link to={`/admin/staff/${staff.id}/edit`} className="p-2 text-blue-600 hover:bg-blue-100 rounded-full" title="Edit Staf">
                                                    <Edit size={18} />
                                                </Link>
                                            )}
                                            <button 
                                                onClick={() => handleUpdateStatus(staff.id, staff.status)}
                                                className={`p-2 rounded-full ${staff.status === 'ACTIVE' ? 'text-red-600 hover:bg-red-100' : 'text-green-600 hover:bg-green-100'}`}
                                                title={staff.status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan'}
                                            >
                                                {staff.status === 'ACTIVE' ? <UserX size={18} /> : <UserCheck size={18} />}
                                            </button>
                                            <button
                                                onClick={() => handleOpenAssignModal(staff)}
                                                className="p-2 text-indigo-600 hover:bg-indigo-100 rounded-full"
                                                title="Pindahkan Penugasan"
                                            >
                                                <Shuffle size={18} />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {isAddModalOpen && (
                <StaffModal 
                    roles={roles}
                    units={units}
                    onClose={() => setAddModalOpen(false)} 
                    onSave={handleSave}
                />
            )}

            {isAssignModalOpen && (
                <StaffAssignmentModal
                    staff={selectedStaff}
                    units={units}
                    onClose={() => setAssignModalOpen(false)}
                    onSave={handleSave}
                />
            )}
        </div>
    );
};

export default StaffListPage;
