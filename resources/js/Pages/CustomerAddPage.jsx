import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const CustomerAddPage = () => {
    const { units, allUnits } = usePage().props;
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [formData, setFormData] = useState({
        full_name: '', email: '', phone_number: '', nik: '', mother_maiden_name: '',
        pob: '', dob: '', gender: 'MALE', address_ktp: '', unit_id: ''
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        router.post('/admin/customers', formData, {
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
                <form onSubmit={handleSubmit}>
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
