import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import { useModal } from '@/contexts/ModalContext.jsx';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const ChangePasswordPage = () => {
    const navigate = useNavigate();
    const modal = useModal();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [formData, setFormData] = useState({ current_password: '', new_password: '', confirm_password: '' });

    const handleChange = (e) => { const { name, value } = e.target; setFormData(prev => ({ ...prev, [name]: value })); };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (formData.new_password !== formData.confirm_password) { modal.showAlert({ title: 'Error', message: 'Password baru dan konfirmasi tidak cocok.', type: 'warning' }); return; }
        setLoading(true); setError(null);
        router.post('/profile/change-password', { current_password: formData.current_password, new_password: formData.new_password }, {
            onSuccess: () => { modal.showAlert({ title: 'Berhasil', message: 'Password Anda telah berhasil diperbarui.', type: 'success' }); navigate('/profile'); },
            onError: (errors) => setError(Object.values(errors).flat()[0] || 'Terjadi kesalahan.'),
            onFinish: () => setLoading(false),
        });
    };

    return (
        <div>
            <Link href="/profile" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"><ArrowLeft size={20} /><h1 className="text-2xl font-bold text-gray-800">Ubah Password</h1></Link>
            <div className="bg-white p-6 rounded-lg shadow-md">
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <Input name="current_password" type="password" label="Password Saat Ini" value={formData.current_password} onChange={handleChange} placeholder="Masukkan password Anda saat ini" required />
                        <Input name="new_password" type="password" label="Password Baru" value={formData.new_password} onChange={handleChange} placeholder="Minimal 8 karakter" required />
                        <Input name="confirm_password" type="password" label="Konfirmasi Password Baru" value={formData.confirm_password} onChange={handleChange} placeholder="Ketik ulang password baru" required />
                    </div>
                    {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                    <div className="mt-6 border-t pt-6"><Button type="submit" fullWidth disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan Password'}</Button></div>
                </form>
            </div>
        </div>
    );
};

export default ChangePasswordPage;
