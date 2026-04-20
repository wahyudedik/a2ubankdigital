import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft } from 'lucide-react';
import { AppConfig } from '@/config';

const ForgotPinPage = () => {
    const [email, setEmail] = useState('');
    const [otpCode, setOtpCode] = useState('');
    const [newPin, setNewPin] = useState('');
    const [confirmPin, setConfirmPin] = useState('');
    const [step, setStep] = useState(1); // 1=email, 2=otp+pin baru
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const navigate = useNavigate();

    const handlePinInput = (setter) => (e) => {
        const val = e.target.value;
        if (/^\d*$/.test(val) && val.length <= 6) setter(val);
    };

    const handleRequestOtp = async (e) => {
        e.preventDefault();
        const result = await callApi('/forgot-pin/request', 'POST', { email });
        if (result && result.status === 'success') {
            setStep(2);
        } else {
            modal.showAlert({ title: 'Gagal', message: error || 'Terjadi kesalahan.', type: 'warning' });
        }
    };

    const handleResetPin = async (e) => {
        e.preventDefault();
        if (newPin.length !== 6) {
            modal.showAlert({ title: 'Error', message: 'PIN baru harus 6 digit.', type: 'warning' });
            return;
        }
        if (newPin !== confirmPin) {
            modal.showAlert({ title: 'Error', message: 'Konfirmasi PIN tidak cocok.', type: 'warning' });
            return;
        }
        const result = await callApi('/forgot-pin/reset', 'POST', {
            email,
            otp_code: otpCode,
            new_pin: newPin,
            new_pin_confirmation: confirmPin,
        });
        if (result && result.status === 'success') {
            await modal.showAlert({ title: 'Berhasil', message: 'PIN transaksi berhasil direset.', type: 'success' });
            navigate('/profile/change-pin');
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
                            <h1 className="text-xl font-bold text-gray-800 mb-2">Lupa PIN Transaksi</h1>
                            <p className="text-sm text-gray-500 mb-6">
                                Masukkan email akun Anda. Kami akan mengirimkan kode verifikasi untuk mereset PIN.
                            </p>
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
                                {loading ? 'Mengirim...' : 'Kirim Kode Verifikasi'}
                            </Button>
                        </form>
                    )}

                    {step === 2 && (
                        <form onSubmit={handleResetPin}>
                            <h1 className="text-xl font-bold text-gray-800 mb-2">Reset PIN Transaksi</h1>
                            <p className="text-sm text-gray-500 mb-6">
                                Masukkan kode verifikasi yang dikirim ke{' '}
                                <span className="font-medium text-gray-700">{email}</span>
                            </p>
                            <div className="space-y-4">
                                <Input
                                    id="otp_code"
                                    type="text"
                                    label="Kode Verifikasi (6 digit)"
                                    value={otpCode}
                                    onChange={(e) => setOtpCode(e.target.value)}
                                    placeholder="000000"
                                    maxLength={6}
                                    required
                                />
                                <Input
                                    id="new_pin"
                                    type="password"
                                    label="PIN Baru (6 digit)"
                                    value={newPin}
                                    onChange={handlePinInput(setNewPin)}
                                    placeholder="••••••"
                                    maxLength={6}
                                    required
                                />
                                <Input
                                    id="confirm_pin"
                                    type="password"
                                    label="Konfirmasi PIN Baru"
                                    value={confirmPin}
                                    onChange={handlePinInput(setConfirmPin)}
                                    placeholder="••••••"
                                    maxLength={6}
                                    required
                                />
                            </div>
                            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                            <div className="mt-6">
                                <Button type="submit" fullWidth disabled={loading}>
                                    {loading ? 'Menyimpan...' : 'Reset PIN'}
                                </Button>
                            </div>
                            <button
                                type="button"
                                onClick={() => setStep(1)}
                                className="w-full mt-3 text-sm text-gray-500 hover:text-gray-700 text-center"
                            >
                                Kirim ulang kode
                            </button>
                        </form>
                    )}
                </div>

                <div className="text-center mt-6">
                    <Link
                        href="/profile/change-pin"
                        className={`flex items-center justify-center gap-2 text-sm font-medium ${AppConfig.theme.textPrimaryHover}`}
                    >
                        <ArrowLeft size={16} /> Kembali ke Ubah PIN
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default ForgotPinPage;
