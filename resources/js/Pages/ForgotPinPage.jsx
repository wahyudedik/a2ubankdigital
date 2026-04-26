import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import useApi from '@/hooks/useApi';
import useNavigate from '@/hooks/useNavigate';
import { useModal } from '@/contexts/ModalContext.jsx';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const ForgotPinPage = () => {
    const navigate = useNavigate();
    const modal = useModal();
    const { loading, error, callApi, setError } = useApi();
    const [step, setStep] = useState(1);
    const [formData, setFormData] = useState({
        password: '',
        otp: '',
        new_pin: '',
        confirm_pin: ''
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        if (name === 'new_pin' || name === 'confirm_pin' || name === 'otp') {
            if (/^\d*$/.test(value) && value.length <= 6) {
                setFormData(prev => ({ ...prev, [name]: value }));
            }
        } else {
            setFormData(prev => ({ ...prev, [name]: value }));
        }
    };

    const handleRequestOtp = async (e) => {
        e.preventDefault();
        setError(null);

        const result = await callApi('/user/security/forgot-pin/request-otp', 'POST', {
            password: formData.password
        });

        if (result && result.status === 'success') {
            await modal.showAlert({
                title: 'OTP Terkirim',
                message: 'Kode OTP telah dikirim ke email Anda. Silakan cek inbox atau folder spam.',
                type: 'success'
            });
            setStep(2);
        }
    };

    const handleResetPin = async (e) => {
        e.preventDefault();
        setError(null);

        if (formData.new_pin !== formData.confirm_pin) {
            setError('PIN baru dan konfirmasi PIN tidak cocok.');
            return;
        }

        const result = await callApi('/user/security/forgot-pin/reset', 'POST', {
            otp: formData.otp,
            new_pin: formData.new_pin,
            confirm_pin: formData.confirm_pin
        });

        if (result && result.status === 'success') {
            await modal.showAlert({
                title: 'Berhasil',
                message: 'PIN transaksi Anda telah berhasil direset.',
                type: 'success'
            });
            navigate('/profile');
        }
    };

    return (
        <div>
            <Link href="/profile/change-pin" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Reset PIN Transaksi</h1>
            </Link>

            <div className="bg-white p-6 rounded-lg shadow-md">
                {step === 1 ? (
                    <form onSubmit={handleRequestOtp}>
                        <div className="space-y-4">
                            <Input
                                name="password"
                                type="password"
                                label="Password Akun (untuk verifikasi)"
                                value={formData.password}
                                onChange={handleChange}
                                placeholder="Masukkan password akun Anda"
                                required
                            />
                        </div>
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-6 border-t pt-6">
                            <Button type="submit" fullWidth disabled={loading}>
                                {loading ? 'Mengirim OTP...' : 'Kirim Kode OTP'}
                            </Button>
                        </div>
                    </form>
                ) : (
                    <form onSubmit={handleResetPin}>
                        <div className="space-y-4">
                            <Input
                                name="otp"
                                type="text"
                                label="Kode OTP (dikirim ke email)"
                                value={formData.otp}
                                onChange={handleChange}
                                placeholder="6 digit kode OTP"
                                required
                            />
                            <Input
                                name="new_pin"
                                type="password"
                                label="PIN Baru"
                                value={formData.new_pin}
                                onChange={handleChange}
                                placeholder="6 digit PIN baru"
                                required
                            />
                            <Input
                                name="confirm_pin"
                                type="password"
                                label="Konfirmasi PIN Baru"
                                value={formData.confirm_pin}
                                onChange={handleChange}
                                placeholder="Ketik ulang 6 digit PIN baru"
                                required
                            />
                        </div>
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-6 border-t pt-6">
                            <Button type="submit" fullWidth disabled={loading}>
                                {loading ? 'Memproses...' : 'Reset PIN'}
                            </Button>
                        </div>
                        <div className="mt-3 text-center">
                            <button
                                type="button"
                                onClick={() => { setStep(1); setFormData({ ...formData, otp: '', new_pin: '', confirm_pin: '' }); }}
                                className="text-sm text-blue-600 hover:text-blue-800"
                            >
                                Kirim ulang OTP
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
};

export default ForgotPinPage;
