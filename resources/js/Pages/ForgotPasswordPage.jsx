import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft } from 'lucide-react';
import { AppConfig } from '@/config';

const ForgotPasswordPage = () => {
    const [email, setEmail] = useState('');
    const [otpCode, setOtpCode] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const navigate = useNavigate();
    const [step, setStep] = useState(1); // 1=email, 2=otp+password

    const handleRequestOtp = async (e) => {
        e.preventDefault();
        const result = await callApi('auth_forgot_password_request.php', 'POST', { email });
        if (result && result.status === 'success') {
            setStep(2);
        } else {
            modal.showAlert({ title: 'Error', message: error || 'Terjadi kesalahan.', type: 'warning' });
        }
    };

    const handleResetPassword = async (e) => {
        e.preventDefault();
        if (newPassword !== confirmPassword) {
            modal.showAlert({ title: 'Error', message: 'Password baru dan konfirmasi tidak cocok.', type: 'warning' });
            return;
        }
        const result = await callApi('auth_forgot_password_reset.php', 'POST', {
            email,
            otp_code: otpCode,
            new_password: newPassword
        });
        if (result && result.status === 'success') {
            await modal.showAlert({ title: 'Berhasil', message: 'Password berhasil direset. Silakan login dengan password baru.', type: 'success' });
            navigate('/login');
        } else {
            modal.showAlert({ title: 'Gagal', message: error || 'Kode OTP tidak valid atau sudah kadaluarsa.', type: 'warning' });
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4">
            <div className="w-full max-w-sm">
                <div className="text-center mb-8">
                    <img src="/a2u-logo.png" alt="A2U Bank Digital" className="w-48 mx-auto mb-4" />
                </div>

                <div className="bg-white p-8 rounded-xl shadow-md">
                    {step === 1 && (
                        <form onSubmit={handleRequestOtp}>
                            <h1 className="text-xl font-bold text-gray-800 mb-2">Lupa Password</h1>
                            <p className="text-sm text-gray-500 mb-6">Masukkan email Anda untuk menerima kode verifikasi.</p>
                            <div className="mb-4">
                                <Input id="email" type="email" label="Alamat Email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="nama@email.com" required />
                            </div>
                            {error && <p className="text-red-500 text-sm mb-4 text-center">{error}</p>}
                            <Button type="submit" fullWidth disabled={loading}>
                                {loading ? 'Mengirim...' : 'Kirim Kode Verifikasi'}
                            </Button>
                        </form>
                    )}

                    {step === 2 && (
                        <form onSubmit={handleResetPassword}>
                            <h1 className="text-xl font-bold text-gray-800 mb-2">Reset Password</h1>
                            <p className="text-sm text-gray-500 mb-6">Masukkan kode verifikasi yang dikirim ke <span className="font-medium text-gray-700">{email}</span></p>
                            <div className="space-y-4">
                                <Input id="otp" type="text" label="Kode Verifikasi (6 digit)" value={otpCode} onChange={(e) => setOtpCode(e.target.value)} placeholder="000000" maxLength={6} required />
                                <Input id="new_password" type="password" label="Password Baru" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} required />
                                <Input id="confirm_password" type="password" label="Konfirmasi Password Baru" value={confirmPassword} onChange={(e) => setConfirmPassword(e.target.value)} required />
                            </div>
                            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                            <div className="mt-6">
                                <Button type="submit" fullWidth disabled={loading}>
                                    {loading ? 'Menyimpan...' : 'Reset Password'}
                                </Button>
                            </div>
                            <button type="button" onClick={() => setStep(1)} className="w-full mt-3 text-sm text-gray-500 hover:text-gray-700 text-center">
                                Kirim ulang kode
                            </button>
                        </form>
                    )}
                </div>

                <div className="text-center mt-6">
                    <Link href="/login" className={`flex items-center justify-center gap-2 text-sm font-medium ${AppConfig.theme.textPrimaryHover}`}>
                        <ArrowLeft size={16} /> Kembali ke Login
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default ForgotPasswordPage;
