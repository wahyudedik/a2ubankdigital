import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import useApi from '../hooks/useApi.js';
import { useModal } from '../contexts/ModalContext.jsx';
import Input from '../components/ui/Input.jsx';
import Button from '../components/ui/Button.jsx';
import { ArrowLeft, User, KeyRound } from 'lucide-react';

const StaffEditPage = () => {
    const { staffId } = useParams();
    const navigate = useNavigate();
    const modal = useModal();
    const { loading, error, callApi } = useApi();
    
    const [roles, setRoles] = useState([]);
    const [formData, setFormData] = useState(null);

    const fetchData = useCallback(async () => {
        const [staffResult, rolesResult] = await Promise.all([
            callApi(`admin_get_staff_detail.php?id=${staffId}`),
            callApi('admin_get_roles.php')
        ]);
        
        if (staffResult && staffResult.status === 'success') {
            setFormData(staffResult.data);
        }
        if (rolesResult && rolesResult.status === 'success') {
            setRoles(rolesResult.data.filter(r => r.role_name !== 'Nasabah'));
        }
    }, [callApi, staffId]);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleUpdateProfile = async (e) => {
        e.preventDefault();
        const payload = {
            staff_id: staffId,
            full_name: formData.full_name,
            email: formData.email,
            role_id: formData.role_id,
        };
        const result = await callApi('admin_edit_staff.php', 'POST', payload);
        if (result && result.status === 'success') {
            modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' });
            navigate('/admin/staff');
        }
    };

    const handleResetPassword = async () => {
        const confirmed = await modal.showConfirmation({
            title: 'Konfirmasi Reset Password',
            message: `Anda yakin ingin mereset password untuk ${formData.full_name}? Password baru akan ditampilkan setelahnya.`,
            confirmText: 'Ya, Reset Password'
        });

        if (confirmed) {
            const result = await callApi('admin_reset_staff_password.php', 'POST', { staff_id: staffId });
            if (result && result.status === 'success') {
                modal.showAlert({
                    title: 'Password Berhasil Direset',
                    message: `Password sementara baru adalah: ${result.data.temporary_password}. Harap segera berikan kepada staf yang bersangkutan.`,
                    type: 'success'
                });
            }
        }
    };

    if (loading && !formData) return <div className="text-center p-8">Memuat data staf...</div>;
    if (error && !formData) return <div className="text-center p-8 text-red-500">{error}</div>;
    if (!formData) return null;

    return (
        <div>
            <Link to="/admin/staff" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Edit Staf</h1>
            </Link>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-lg shadow-md">
                    <h2 className="text-xl font-semibold text-gray-700 mb-4 flex items-center"><User size={20} className="mr-2"/> Informasi Profil</h2>
                    <form onSubmit={handleUpdateProfile}>
                        <div className="space-y-4">
                            <Input name="full_name" label="Nama Lengkap" value={formData.full_name} onChange={handleChange} required />
                            <Input name="email" type="email" label="Email" value={formData.email} onChange={handleChange} required />
                            <div>
                                <label className="block mb-2 text-sm font-medium text-gray-700">Peran</label>
                                <select name="role_id" value={formData.role_id} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg">
                                    {roles.map(role => <option key={role.id} value={role.id}>{role.role_name}</option>)}
                                </select>
                            </div>
                        </div>
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-6 flex justify-end">
                            <Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan Perubahan'}</Button>
                        </div>
                    </form>
                </div>

                 <div className="bg-white p-6 rounded-lg shadow-md">
                    <h2 className="text-xl font-semibold text-gray-700 mb-4 flex items-center"><KeyRound size={20} className="mr-2"/> Keamanan Akun</h2>
                    <p className="text-sm text-gray-600 mb-4">
                        Klik tombol di bawah ini untuk mereset password staf. Sebuah password sementara akan dibuat dan harus segera diberikan kepada staf yang bersangkutan untuk login.
                    </p>
                    <Button onClick={handleResetPassword} disabled={loading} className="w-full bg-yellow-500 hover:bg-yellow-600">
                        {loading ? 'Memproses...' : 'Reset Password'}
                    </Button>
                </div>
            </div>
        </div>
    );
};

export default StaffEditPage;
