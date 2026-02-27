import React, { useState, useEffect } from 'react';
import { Link, useSearchParams, useNavigate } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';

const ResetPasswordPage = () => {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const modal = useModal();
    const { loading, error, callApi } = useApi();

    const [token, setToken] = useState(null);
    const [formData, setFormData] = useState({ new_password: '', confirm_password: '' });
    const [isSuccess, setIsSuccess] = useState(false);

    useEffect(() => {
        const urlToken = searchParams.get('token');
        if (!urlToken) {
            modal.showAlert({ title: 'Token Tidak Valid', message: 'Tautan reset password tidak valid atau telah kedaluwarsa.', type: 'warning' });
            navigate('/login');
        }
        setToken(urlToken);
    }, [searchParams, navigate, modal]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (formData.new_password !== formData.confirm_password) {
            modal.showAlert({ title: 'Password Tidak Cocok', message: 'Password baru dan konfirmasi tidak cocok.', type: 'warning' });
            return;
        }

        const result = await callApi('auth_forgot_password_reset.php', 'POST', {
            token: token,
            new_password: formData.new_password
        });
        
        if (result && result.status === 'success') {
            setIsSuccess(true);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4">
            <div className="w-full max-w-sm">
                <div className="text-center mb-8">
                    <img src="/a2u-logo.png" alt="A2U Bank Digital" className="w-48 mx-auto mb-4" />
                </div>
                <div className="bg-white p-8 rounded-xl shadow-md">
                    {isSuccess ? (
                         <div className="text-center">
                            <h1 className="text-xl font-bold text-gray-800 mb-2">Password Berhasil Direset</h1>
                            <p className="text-gray-600">Anda sekarang dapat login dengan password baru Anda.</p>
                            <Link to="/login">
                                <Button fullWidth className="mt-6">Lanjutkan ke Login</Button>
                            </Link>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit}>
                            <h1 className="text-xl font-bold text-gray-800 mb-4">Atur Password Baru</h1>
                            <div className="space-y-4">
                                <Input name="new_password" type="password" label="Password Baru" value={formData.new_password} onChange={handleChange} required />
                                <Input name="confirm_password" type="password" label="Konfirmasi Password Baru" value={formData.confirm_password} onChange={handleChange} required />
                            </div>
                            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                            <div className="mt-6">
                                <Button type="submit" fullWidth disabled={loading || !token}>
                                    {loading ? 'Menyimpan...' : 'Simpan Password Baru'}
                                </Button>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ResetPasswordPage;