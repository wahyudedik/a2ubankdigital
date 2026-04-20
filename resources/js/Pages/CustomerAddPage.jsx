import React, { useState, useRef } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft, Upload, X, Image } from 'lucide-react';

const FileUploadField = ({ label, name, accept, onChange, preview, required }) => {
    const inputRef = useRef(null);

    return (
        <div>
            <label className="block mb-2 text-sm font-medium text-gray-700">
                {label} {required && <span className="text-red-500">*</span>}
            </label>
            <div
                className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:border-green-400 transition-colors"
                onClick={() => inputRef.current?.click()}
            >
                {preview ? (
                    <div className="relative">
                        <img src={preview} alt={label} className="max-h-40 mx-auto rounded object-contain" />
                        <p className="text-xs text-gray-500 mt-2">Klik untuk ganti foto</p>
                    </div>
                ) : (
                    <div className="flex flex-col items-center gap-2 py-4">
                        <Image size={32} className="text-gray-400" />
                        <p className="text-sm text-gray-500">Klik untuk upload foto</p>
                        <p className="text-xs text-gray-400">JPG, JPEG, PNG — maks. 2MB</p>
                    </div>
                )}
            </div>
            <input
                ref={inputRef}
                type="file"
                name={name}
                accept={accept}
                onChange={onChange}
                className="hidden"
                required={required}
            />
        </div>
    );
};

const CustomerAddPage = () => {
    const { units, allUnits } = usePage().props;
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [formData, setFormData] = useState({
        full_name: '', email: '', phone_number: '', nik: '', mother_maiden_name: '',
        pob: '', dob: '', gender: 'MALE', address_ktp: '', unit_id: ''
    });
    const [ktpFile, setKtpFile] = useState(null);
    const [selfieFile, setSelfieFile] = useState(null);
    const [ktpPreview, setKtpPreview] = useState(null);
    const [selfiePreview, setSelfiePreview] = useState(null);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleFileChange = (e, type) => {
        const file = e.target.files[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            setError(`Ukuran ${type === 'ktp' ? 'foto KTP' : 'foto selfie'} maksimal 2MB.`);
            return;
        }

        const previewUrl = URL.createObjectURL(file);
        if (type === 'ktp') {
            setKtpFile(file);
            setKtpPreview(previewUrl);
        } else {
            setSelfieFile(file);
            setSelfiePreview(previewUrl);
        }
        setError(null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setError(null);

        if (!ktpFile) { setError('Foto KTP wajib diupload.'); return; }
        if (!selfieFile) { setError('Foto selfie dengan KTP wajib diupload.'); return; }

        setLoading(true);

        // Use FormData to support file uploads
        const data = new FormData();
        Object.entries(formData).forEach(([key, val]) => data.append(key, val));
        data.append('ktp_image', ktpFile);
        data.append('selfie_image', selfieFile);

        router.post('/admin/customers', data, {
            forceFormData: true,
            onSuccess: () => navigate('/admin/customers'),
            onError: (errors) => setError(Object.values(errors).flat()[0] || 'Terjadi kesalahan.'),
            onFinish: () => setLoading(false),
        });
    };

    const unitList = allUnits || units || [];

    return (
        <div>
            <Link href="/admin/customers" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Tambah Nasabah Baru</h1>
            </Link>

            <div className="bg-white p-8 rounded-lg shadow-md">
                <form onSubmit={handleSubmit} encType="multipart/form-data">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input name="full_name" label="Nama Lengkap" value={formData.full_name} onChange={handleChange} required />
                        <Input name="email" type="email" label="Email" value={formData.email} onChange={handleChange} required />
                        <Input name="phone_number" label="Nomor Telepon" value={formData.phone_number} onChange={handleChange} />
                        <Input name="nik" label="NIK" value={formData.nik} onChange={handleChange} required maxLength="16" minLength="16" placeholder="16 digit NIK" />
                        <Input name="mother_maiden_name" label="Nama Ibu Kandung" value={formData.mother_maiden_name} onChange={handleChange} required />
                        <Input name="pob" label="Tempat Lahir" value={formData.pob} onChange={handleChange} />
                        <Input name="dob" type="date" label="Tanggal Lahir" value={formData.dob} onChange={handleChange} />
                        <div>
                            <label htmlFor="gender" className="block mb-2 text-sm font-medium text-gray-700">Jenis Kelamin</label>
                            <select name="gender" id="gender" value={formData.gender} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-taskora-green-300">
                                <option value="MALE">Laki-laki</option>
                                <option value="FEMALE">Perempuan</option>
                            </select>
                        </div>
                        <div className="md:col-span-2">
                            <Input name="address_ktp" label="Alamat KTP" value={formData.address_ktp} onChange={handleChange} />
                        </div>
                        <div className="md:col-span-2">
                            <label htmlFor="unit_id" className="block mb-2 text-sm font-medium text-gray-700">Penempatan Unit/Cabang</label>
                            <select name="unit_id" id="unit_id" value={formData.unit_id} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg" required>
                                <option value="" disabled>Pilih penempatan...</option>
                                {unitList.map(branch => (
                                    <optgroup key={branch.id} label={branch.unit_name}>
                                        <option value={branch.id}>{branch.unit_name} (Level Cabang)</option>
                                        {branch.units?.map(unit => (
                                            <option key={unit.id} value={unit.id}>{unit.unit_name}</option>
                                        ))}
                                    </optgroup>
                                ))}
                            </select>
                        </div>

                        {/* KYC Document Upload */}
                        <div className="md:col-span-2">
                            <h3 className="text-base font-semibold text-gray-700 mb-4 border-t pt-4">Dokumen KYC</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <FileUploadField
                                    label="Foto KTP"
                                    name="ktp_image"
                                    accept="image/jpg,image/jpeg,image/png"
                                    onChange={(e) => handleFileChange(e, 'ktp')}
                                    preview={ktpPreview}
                                    required
                                />
                                <FileUploadField
                                    label="Foto Selfie dengan KTP"
                                    name="selfie_image"
                                    accept="image/jpg,image/jpeg,image/png"
                                    onChange={(e) => handleFileChange(e, 'selfie')}
                                    preview={selfiePreview}
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}

                    <div className="mt-8 flex justify-end">
                        <Button type="submit" disabled={loading}>
                            {loading ? 'Menyimpan...' : 'Simpan Nasabah'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default CustomerAddPage;
