import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { ArrowLeft } from 'lucide-react';
import { AppConfig } from '../config';

const ForgotPasswordPage = () => {
    const [email, setEmail] = useState('');
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [isSubmitted, setIsSubmitted] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        const result = await callApi('auth_forgot_password_request.php', 'POST', { email });
        if (result && result.status === 'success') {
            setIsSubmitted(true);
        } else {
             modal.showAlert({ title: 'Error', message: error || 'Terjadi kesalahan.', type: 'warning' });
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4">
            <div className="w-full max-w-sm">
                <div className="text-center mb-8">
                    <img src="/a2u-logo.png" alt="A2U Bank Digital" className="w-48 mx-auto mb-4" />
                </div>
                
                <div className="bg-white p-8 rounded-xl shadow-md">
                    {isSubmitted ? (
                        <div className="text-center">
                            <h1 className="text-xl font-bold text-gray-800 mb-2">Periksa Email Anda</h1>
                            <p className="text-gray-600">Jika email yang Anda masukkan terdaftar, kami telah mengirimkan tautan untuk mereset password Anda.</p>
                            <Link to="/login">
                                <Button fullWidth className="mt-6">Kembali ke Login</Button>
                            </Link>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit}>
                            <h1 className="text-xl font-bold text-gray-800 mb-2">Lupa Password</h1>
                            <p className="text-sm text-gray-500 mb-6">Masukkan email Anda untuk menerima instruksi reset password.</p>
                            <div className="mb-4">
                                <Input
                                    id="email"
                                    type="email"
                                    label="Alamat Email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="nama@email.com"
                                    required
                                />
                            </div>
                            {error && <p className="text-red-500 text-sm mb-4 text-center">{error}</p>}
                            <Button type="submit" fullWidth disabled={loading}>
                                {loading ? 'Mengirim...' : 'Kirim Instruksi'}
                            </Button>
                        </form>
                    )}
                </div>
                 <div className="text-center mt-6">
                    <Link to="/login" className={`flex items-center justify-center gap-2 text-sm font-medium ${AppConfig.theme.textPrimaryHover}`}>
                        <ArrowLeft size={16}/> Kembali ke Login
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default ForgotPasswordPage;

