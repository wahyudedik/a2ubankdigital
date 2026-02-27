import React, { useState, useEffect, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useApi from '../hooks/useApi.js';
import { useModal } from '../contexts/ModalContext.jsx';
import Input from '../components/ui/Input.jsx';
import Button from '../components/ui/Button.jsx';
import { AppConfig } from '../config/index.js';
import { UploadCloud } from 'lucide-react';

const ImageUpload = ({ label, name, required, onChange, previewSrc }) => (
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
        <p className="text-xs text-gray-500 mt-1">PNG atau JPG, maks 2MB.</p>
    </div>
);


const RegisterPage = () => {
    const navigate = useNavigate();
    const modal = useModal();
    const { loading, error, callApi, setLoading, setError } = useApi();
    const [step, setStep] = useState(1);
    const [nearestLocations, setNearestLocations] = useState([]);

    const [formData, setFormData] = useState({
        full_name: '', email: '', password: '', phone_number: '',
        nik: '', mother_maiden_name: '', pob: '', dob: '',
        gender: 'L', address_ktp: '', unit_id: '', otp_code: ''
    });

    const [ktpImage, setKtpImage] = useState(null);
    const [selfieImage, setSelfieImage] = useState(null);
    const [ktpPreview, setKtpPreview] = useState(null);
    const [selfiePreview, setSelfiePreview] = useState(null);

    const fetchNearestLocations = useCallback(async (lat, lon) => {
        const result = await callApi(`utility_get_nearest_units.php?lat=${lat}&lon=${lon}`);
        if (result && result.status === 'success') {
            setNearestLocations(result.data);
            if (result.data.length > 0) {
                setFormData(prev => ({ ...prev, unit_id: result.data[0].id }));
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

    const handleFileChange = (e, fileType) => {
        const file = e.target.files[0];
        if (file && file.size < 2097152) { // 2MB
            if (fileType === 'ktp') {
                setKtpImage(file);
                setKtpPreview(URL.createObjectURL(file));
            } else {
                setSelfieImage(file);
                setSelfiePreview(URL.createObjectURL(file));
            }
        } else if (file) {
            modal.showAlert({ title: 'Ukuran File Terlalu Besar', message: 'Ukuran file maksimal adalah 2MB.', type: 'warning' });
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
            const response = await fetch(`${AppConfig.api.baseUrl}/auth_register_request_otp.php`, {
                method: 'POST',
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

    const handleVerifyOtp = async (e) => {
        e.preventDefault();
        const result = await callApi('auth_register_verify_otp.php', 'POST', { email: formData.email, otp_code: formData.otp_code });
        if (result && result.status === 'success') {
            await modal.showAlert({ title: 'Pendaftaran Berhasil', message: result.message, type: 'success' });
            navigate('/login');
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

                                <ImageUpload name="ktp_image" label="Foto KTP" required onChange={(e) => handleFileChange(e, 'ktp')} previewSrc={ktpPreview} />
                                <ImageUpload name="selfie_image" label="Foto Selfie dengan KTP" required onChange={(e) => handleFileChange(e, 'selfie')} previewSrc={selfiePreview} />

                                <div className="md:col-span-2">
                                    <label htmlFor="unit_id" className="block mb-2 text-sm font-medium text-gray-700">Pilih Unit/Cabang Terdekat</label>
                                    <select name="unit_id" id="unit_id" value={formData.unit_id} onChange={handleChange} className={`w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 ${AppConfig.theme.ringFocus}`} required>
                                        {nearestLocations.length === 0 && <option value="" disabled>Memuat lokasi terdekat...</option>}
                                        {nearestLocations.map(loc => (
                                            <option key={loc.id} value={loc.id}>
                                                {loc.unit_name} ({loc.type}) - {parseFloat(loc.distance).toFixed(1)} km
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>
                            {error && <p className="text-bpn-red text-sm mt-4 text-center">{error}</p>}
                            <div className="mt-6">
                                <Button type="submit" fullWidth disabled={loading}>
                                    {loading ? 'Memproses...' : 'Lanjutkan & Kirim OTP'}
                                </Button>
                            </div>
                        </form>
                    )}

                    {step === 2 && (
                        <form onSubmit={handleVerifyOtp}>
                            <p className="text-center text-gray-600 mb-4">Kami telah mengirimkan kode 6 digit ke <strong>{formData.email}</strong>. Silakan masukkan di bawah ini.</p>
                            <Input name="otp_code" label="Kode OTP" value={formData.otp_code} onChange={handleChange} required />
                            {error && <p className="text-bpn-red text-sm mt-4 text-center">{error}</p>}
                            <div className="mt-6">
                                <Button type="submit" fullWidth disabled={loading}>
                                    {loading ? 'Memverifikasi...' : 'Verifikasi & Buat Akun'}
                                </Button>
                            </div>
                            <button type="button" onClick={() => setStep(1)} className="text-sm text-center w-full mt-4 text-gray-500 hover:text-black">Kembali</button>
                        </form>
                    )}
                </div>
                <div className="text-center mt-6">
                    <p className="text-gray-600 text-sm">Sudah punya akun? <Link to="/login" className={`font-semibold ${AppConfig.theme.textPrimaryHover}`}>Login di sini</Link></p>
                </div>
            </div>
        </div>
    );
};

export default RegisterPage;