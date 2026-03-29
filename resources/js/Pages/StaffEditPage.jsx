import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft, User, KeyRound } from 'lucide-react';

const StaffEditPage = () => {
    const { staff, roles: allRoles } = usePage().props;
    const staffId = staff?.id;
    const navigate = useNavigate();
    const modal = useModal();
    const { loading: resetLoading, callApi } = useApi();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const roles = (allRoles || []).filter(r => r.role_name !== 'Nasabah');
    const [formData, setFormData] = useState({
        full_name: staff?.full_name || '', email: staff?.email || '', role_id: staff?.role_id || '',
    });

    const handleChange = (e) => { const { name, value } = e.target; setFormData(prev => ({ ...prev, [name]: value })); };

    const handleUpdateProfile = (e) => {
        e.preventDefault();
        setLoading(true); setError(null);
        router.post(`/admin/staff/${staffId}`, { full_name: formData.full_name, email: formData.email, role_id: formData.role_id }, {
            onSuccess: () => { modal.showAlert({ title: 'Berhasil', message: 'Profil staf berhasil diperbarui.', type: 'success' }); navigate('/admin/staff'); },
            onError: (errors) => setError(Object.values(errors).flat()[0] || 'Terjadi kesalahan.'),
            onFinish: () => setLoading(false),
        });
    };

    const handleResetPassword = async () => {
        const confirmed = await modal.showConfirmation({ title: 'Konfirmasi Reset Password', message: `Anda yakin ingin mereset password untuk ${formData.full_name}? Password baru akan ditampilkan setelahnya.`, confirmText: 'Ya, Reset Password' });
        if (confirmed) {
            const result = await callApi('admin_reset_staff_password.php', 'POST', { staff_id: staffId });
            if (result && result.status === 'success') {
                modal.showAlert({ title: 'Password Berhasil Direset', message: `Password sementara baru adalah: ${result.data.temporary_password}. Harap segera berikan kepada staf yang bersangkutan.`, type: 'success' });
            }
        }
    };

    if (!staff) return <div className="text-center p-8">Data staf tidak ditemukan.</div>;

    return (
        <div>
            <Link href="/admin/staff" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"><ArrowLeft size={20} /><h1 className="text-2xl md:text-3xl font-bold text-gray-800">Edit Staf</h1></Link>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-lg shadow-md">
                    <h2 className="text-xl font-semibold text-gray-700 mb-4 flex items-center"><User size={20} className="mr-2" /> Informasi Profil</h2>
                    <form onSubmit={handleUpdateProfile}>
                        <div className="space-y-4">
                            <Input name="full_name" label="Nama Lengkap" value={formData.full_name} onChange={handleChange} required />
                            <Input name="email" type="email" label="Email" value={formData.email} onChange={handleChange} required />
                            <div><label className="block mb-2 text-sm font-medium text-gray-700">Peran</label><select name="role_id" value={formData.role_id} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg">{roles.map(role => <option key={role.id} value={role.id}>{role.role_name}</option>)}</select></div>
                        </div>
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-6 flex justify-end"><Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan Perubahan'}</Button></div>
                    </form>
                </div>
                <div className="bg-white p-6 rounded-lg shadow-md">
                    <h2 className="text-xl font-semibold text-gray-700 mb-4 flex items-center"><KeyRound size={20} className="mr-2" /> Keamanan Akun</h2>
                    <p className="text-sm text-gray-600 mb-4">Klik tombol di bawah ini untuk mereset password staf. Sebuah password sementara akan dibuat dan harus segera diberikan kepada staf yang bersangkutan untuk login.</p>
                    <Button onClick={handleResetPassword} disabled={resetLoading} className="w-full bg-yellow-500 hover:bg-yellow-600">{resetLoading ? 'Memproses...' : 'Reset Password'}</Button>
                </div>
            </div>
        </div>
    );
};

export default StaffEditPage;
