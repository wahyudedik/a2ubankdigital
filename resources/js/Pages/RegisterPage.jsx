import React, { useState, useEffect, useCallback } from 'react';
import { Link } from '@inertiajs/react'
import useNavigate from '@/hooks/useNavigate';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { AppConfig } from '@/config';
import { UploadCloud, Info } from 'lucide-react';

const KTP_MIN_WIDTH = 600;
const KTP_MIN_HEIGHT = 400;
const SELFIE_MIN_WIDTH = 400;
const SELFIE_MIN_HEIGHT = 400;

const validateImageDimensions = (file, minWidth, minHeight) => {
    return new Promise((resolve) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img.width >= minWidth && img.height >= minHeight);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(false);
        };
        img.src = url;
    });
};

const ImageUpload = ({ label, name, required, onChange, previewSrc, guide = [] }) => (
    <div className="md:col-span-2">
        <label className="block mb-2 text-sm font-medium text-gray-700">{label} {required && <span className="text-red-500">*</span>}</label>
        <div className="mt-1 flex items-center gap-4">
            <span className="h-20 w-32 overflow-hidden rounded-lg bg-gray-100 flex items-center justify-center">
                {previewSrc ? (
                    <img src={previewSrc} alt="Preview" className="h-full w-full object-cover" />
                ) : (
                    <UploadCloud className="h-8 w-8 text-gray-400" />
                )}
            </span>
            <label htmlFor={name} className="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50">
                <span>Pilih File</span>
                <input id={name} name={name} type="file" className="sr-only" onChange={onChange} accept="image/png, image/jpeg" required={required} />
            </label>
        </div>
        <p className="text-xs text-gray-500 mt-1">PNG atau JPG, maks 2MB, minimal resolusi {name === 'ktp_image' ? '600×400' : '400×400'} px.</p>
        {guide.length > 0 && (
            <div className="mt-2 p-2.5 bg-blue-50 rounded-lg border border-blue-100">
                <div className="flex items-start gap-2">
                    <Info className="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" />
                    <div className="text-xs text-blue-800 space-y-0.5">
                        {guide.map((tip, i) => (
                            <p key={i}>• {tip}</p>
                        ))}
                    </div>
                </div>
            </div>
        )}
    </div>
);

const RegisterPage = () => {
    const navigate = useNavigate();
    const modal = useModal();
    const { loading, error, callApi, setLoading, setError } = useApi();
    const [step, setStep] = useState(1);
    const [nearestLocations, setNearestLocations] = useState([]);
    const [resendLoading, setResendLoading] = useState(false);
    const [cooldown, setCooldown] = useState(0);

    const [formData, setFormData] = useState({
        full_name: '', email: '', password: '', phone_number: '',
        nik: '', mother_maiden_name: '', pob: '', dob: '',
        gender: 'L', address_ktp: '', unit_id: ''
    });

    const [ktpImage, setKtpImage] = useState(null);
    const [selfieImage, setSelfieImage] = useState(null);
    const [ktpPreview, setKtpPreview] = useState(null);
    const [selfiePreview, setSelfiePreview] = useState(null);

    const fetchNearestLocations = useCallback(async (lat, lon) => {
        const result = await callApi(`/utility/nearest-units?lat=${lat}&lon=${lon}`);
        if (result && result.status === 'success' && result.data) {
            const locations = Array.isArray(result.data) ? result.data : [];
            setNearestLocations(locations);
            if (locations.length > 0) {
                setFormData(prev => ({ ...prev, unit_id: String(locations[0].id) }));
            }
        }
    }, [callApi]);

    useEffect(() => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => fetchNearestLocations(position.coords.latitude, position.coords.longitude),
                () => fetchNearestLocations(-6.1945, 106.8224)
            );
        } else {
            fetchNearestLocations(-6.1945, 106.8224);
        }
    }, [fetchNearestLocations]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleFileChange = async (e, fileType) => {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size >= 2097152) { // 2MB
            modal.showAlert({ title: 'Ukuran File Terlalu Besar', message: 'Ukuran file maksimal adalah 2MB.', type: 'warning' });
            return;
        }

        const minWidth = fileType === 'ktp' ? KTP_MIN_WIDTH : SELFIE_MIN_WIDTH;
        const minHeight = fileType === 'ktp' ? KTP_MIN_HEIGHT : SELFIE_MIN_HEIGHT;
        const label = fileType === 'ktp' ? 'KTP' : 'Swafoto';

        const valid = await validateImageDimensions(file, minWidth, minHeight);
        if (!valid) {
            modal.showAlert({
                title: 'Resolusi Terlalu Rendah',
                message: `Dimensi foto ${label} minimal ${minWidth}×${minHeight} px. Pastikan foto diambil dengan resolusi tinggi dan tidak terpotong.`,
                type: 'error'
            });
            return;
        }

        if (fileType === 'ktp') {
            setKtpImage(file);
            setKtpPreview(URL.createObjectURL(file));
        } else {
            setSelfieImage(file);
            setSelfiePreview(URL.createObjectURL(file));
        }
    };

    const handleRequestOtp = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        const apiFormData = new FormData();
        Object.keys(formData).forEach(key => apiFormData.append(key, formData[key]));
        apiFormData.append('ktp_image', ktpImage);
        apiFormData.append('selfie_image', selfieImage);

        try {
            const response = await fetch(`${AppConfig.api.baseUrl}/auth/register/request-otp`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                credentials: 'same-origin',
                body: apiFormData,
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Terjadi kesalahan.');
            }
            setStep(2);
            modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' });
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    // Cooldown timer for resend button
    useEffect(() => {
        if (cooldown <= 0) return;
        const timer = setTimeout(() => setCooldown(cooldown - 1), 1000);
        return () => clearTimeout(timer);
    }, [cooldown]);

    const handleResendEmail = async () => {
        setResendLoading(true);
        try {
            const response = await fetch(`${AppConfig.api.baseUrl}/auth/register/resend-verification`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ email: formData.email }),
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Gagal mengirim ulang email.');
            }
            setCooldown(60);
            modal.showAlert({ title: 'Terkirim!', message: result.message, type: 'success' });
        } catch (err) {
            modal.showAlert({ title: 'Gagal', message: err.message, type: 'error' });
        } finally {
            setResendLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col justify-center items-center p-4">
            <div className="w-full max-w-lg">
                <div className="text-center mb-8">
                    <img src={AppConfig.brand.logo} alt="A2U Bank Digital Logo" className="h-10 mx-auto mb-4" />
                    <h1 className="text-3xl font-bold text-gray-800">Buka Akun Baru</h1>
                    <p className="text-gray-500">Lengkapi data diri Anda untuk memulai.</p>
                </div>

                <div className="bg-white p-8 rounded-xl shadow-md">
                    {step === 1 && (
                        <form onSubmit={handleRequestOtp}>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Input name="full_name" label="Nama Lengkap" value={formData.full_name} onChange={handleChange} required />
                                <Input name="email" type="email" label="Email" value={formData.email} onChange={handleChange} required />
                                <Input name="password" type="password" label="Password" value={formData.password} onChange={handleChange} required />
                                <Input name="phone_number" label="Nomor Telepon" value={formData.phone_number} onChange={handleChange} required />
                                <Input name="nik" label="Nomor Induk Kependudukan (NIK)" value={formData.nik} onChange={handleChange} required />
                                <Input name="mother_maiden_name" label="Nama Ibu Kandung" value={formData.mother_maiden_name} onChange={handleChange} required />
                                <Input name="pob" label="Tempat Lahir" value={formData.pob} onChange={handleChange} required />
                                <Input name="dob" type="date" label="Tanggal Lahir" value={formData.dob} onChange={handleChange} required />
                                <div>
                                    <label htmlFor="gender" className="block mb-2 text-sm font-medium text-gray-700">Jenis Kelamin</label>
                                    <select name="gender" id="gender" value={formData.gender} onChange={handleChange} className={`w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 ${AppConfig.theme.ringFocus}`}>
                                        <option value="L">Laki-laki</option>
                                        <option value="P">Perempuan</option>
                                    </select>
                                </div>
                                <div className="md:col-span-2">
                                    <Input name="address_ktp" label="Alamat Sesuai KTP" value={formData.address_ktp} onChange={handleChange} required />
                                </div>

                                <ImageUpload name="ktp_image" label="Foto KTP" required onChange={(e) => handleFileChange(e, 'ktp')} previewSrc={ktpPreview}
                                    guide={[
                                        'Foto KTP harus jelas dan terbaca',
                                        'Seluruh KTP terlihat dalam satu frame',
                                        'Minimal 600×400 px, format JPG/PNG, maks 2MB',
                                        'Tidak buram, tidak terpotong, pencahayaan cukup',
                                    ]}
                                />
                                <ImageUpload name="selfie_image" label="Foto Selfie dengan KTP" required onChange={(e) => handleFileChange(e, 'selfie')} previewSrc={selfiePreview}
                                    guide={[
                                        'Wajah dan KTP terlihat jelas dalam satu frame',
                                        'Wajah menghadap kamera, tidak blur',
                                        'Minimal 400×400 px, format JPG/PNG, maks 2MB',
                                        'Pencahayaan cukup, tidak gelap',
                                    ]}
                                />

                                <div className="md:col-span-2">
                                    <label htmlFor="unit_id" className="block mb-2 text-sm font-medium text-gray-700">Pilih Unit/Cabang Terdekat</label>
                                    <select name="unit_id" id="unit_id" value={formData.unit_id} onChange={handleChange} className={`w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 ${AppConfig.theme.ringFocus}`} required>
                                        {nearestLocations.length === 0 && <option value="" disabled>Memuat lokasi terdekat...</option>}
                                        {nearestLocations.map(loc => (
                                            <option key={loc.id} value={loc.id}>
                                                {loc.unit_name} ({loc.type}){loc.distance > 0 ? ` - ${parseFloat(loc.distance).toFixed(1)} km` : ''}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            {error && <p className="text-bpn-red text-sm mt-4 text-center">{error}</p>}
                            <div className="mt-6">
                                <Button type="submit" fullWidth disabled={loading}>
                                    {loading ? 'Memproses...' : 'Lanjutkan & Kirim Data'}
                                </Button>
                            </div>
                        </form>
                    )}

                    {step === 2 && (
                        <div className="text-center py-4">
                            {/* Email Icon */}
                            <div className="w-20 h-20 mx-auto mb-6 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg className="w-10 h-10 text-bpn-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>

                            <h2 className="text-xl font-bold text-gray-800 mb-2">Cek Email Anda</h2>
                            <p className="text-gray-600 mb-2">
                                Kami telah mengirimkan link verifikasi ke:
                            </p>
                            <p className="text-gray-800 font-semibold mb-4">{formData.email}</p>
                            <p className="text-gray-500 text-sm mb-6">
                                Silakan buka email Anda dan klik tombol <strong>"Verifikasi Email Saya"</strong> untuk mengaktifkan akun.
                            </p>

                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <p className="text-yellow-700 text-sm">
                                    ⚠️ Link verifikasi berlaku selama <strong>10 menit</strong>. Jika tidak menemukan email, cek folder <strong>Spam</strong> atau <strong>Promotions</strong>.
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={() => {
                                    const emailDomain = formData.email.split('@')[1];
                                    const webmailUrls = {
                                        'gmail.com': 'https://mail.google.com',
                                        'yahoo.com': 'https://mail.yahoo.com',
                                        'outlook.com': 'https://outlook.live.com',
                                        'hotmail.com': 'https://outlook.live.com',
                                    };
                                    window.open(webmailUrls[emailDomain] || `https://${emailDomain}`, '_blank');
                                }}
                                className="w-full bg-bpn-blue text-white font-semibold py-3 rounded-xl hover:bg-bpn-blue/90 transition-colors"
                            >
                                Buka Email
                            </button>

                            <button
                                type="button"
                                onClick={handleResendEmail}
                                disabled={resendLoading || cooldown > 0}
                                className={`w-full mt-4 py-3 rounded-xl font-semibold text-sm transition-colors border ${cooldown > 0
                                        ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                        : 'bg-white text-bpn-blue border-bpn-blue hover:bg-blue-50'
                                    }`}
                            >
                                {resendLoading
                                    ? 'Mengirim...'
                                    : cooldown > 0
                                        ? `Kirim Ulang dalam ${cooldown}s`
                                        : 'Kirim Ulang Email'
                                }
                            </button>

                            <button type="button" onClick={() => setStep(1)} className="text-sm text-center w-full mt-4 text-gray-500 hover:text-black">
                                Kembali
                            </button>
                        </div>
                    )}
                </div>
                <div className="text-center mt-6">
                    <p className="text-gray-600 text-sm">Sudah punya akun? <Link href="/login" className={`font-semibold ${AppConfig.theme.textPrimaryHover}`}>Login di sini</Link></p>
                </div>
            </div>
        </div>
    );
};

export default RegisterPage;
