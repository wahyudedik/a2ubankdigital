import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useNavigate from '@/hooks/useNavigate';
import Input from '@/components/ui/Input';
import Button from '@/components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const CustomerEditPage = () => {
    const { customer, units, allUnits } = usePage().props;
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const customerId = customer?.id;
    const customerProfile = customer?.customer_profile || customer?.customerProfile || {};
    const rawDob = customerProfile?.dob || customer?.dob || '';
    const dobFormatted = rawDob ? rawDob.substring(0, 10) : '';
    const [formData, setFormData] = useState({
        full_name: customer?.full_name ?? '', email: customer?.email ?? '',
        phone_number: customer?.phone_number ?? '', status: customer?.status ?? 'ACTIVE',
        nik: customerProfile?.nik ?? customer?.nik ?? '',
        mother_maiden_name: customerProfile?.mother_maiden_name ?? customer?.mother_maiden_name ?? '',
        pob: customerProfile?.pob ?? customer?.pob ?? '',
        dob: dobFormatted,
        gender: customerProfile?.gender ?? customer?.gender ?? 'L',
        address_ktp: customerProfile?.address_ktp ?? customer?.address_ktp ?? '',
        unit_id: customerProfile?.unit_id ?? customer?.unit_id ?? ''
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);
        router.put(`/admin/customers/${customerId}`, formData, {
            onSuccess: () => navigate(`/admin/customers/${customerId}`),
            onError: (errors) => setError(Object.values(errors).flat()[0] || 'Terjadi kesalahan.'),
            onFinish: () => setLoading(false),
        });
    };

    const unitList = allUnits || units || [];

    if (!customer) return <div className="text-center p-8">Memuat data nasabah...</div>;

    return (
        <div>
            <Link href={`/admin/customers/${customerId}`} className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Edit Nasabah</h1>
            </Link>

            <div className="bg-white p-8 rounded-lg shadow-md">
                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input name="full_name" label="Nama Lengkap" value={formData.full_name} onChange={handleChange} required />
                        <Input name="email" type="email" label="Email" value={formData.email} onChange={handleChange} required />
                        <Input name="phone_number" label="Nomor Telepon" value={formData.phone_number} onChange={handleChange} />
                        <Input name="nik" label="NIK" value={formData.nik} onChange={handleChange} required />
                        <Input name="mother_maiden_name" label="Nama Ibu Kandung" value={formData.mother_maiden_name} onChange={handleChange} required />
                        <Input name="pob" label="Tempat Lahir" value={formData.pob} onChange={handleChange} />
                        <Input name="dob" type="date" label="Tanggal Lahir" value={formData.dob} onChange={handleChange} />
                        <div>
                            <label htmlFor="gender" className="block mb-2 text-sm font-medium text-gray-700">Jenis Kelamin</label>
                            <select name="gender" id="gender" value={formData.gender} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div className="md:col-span-2">
                            <Input name="address_ktp" label="Alamat KTP" value={formData.address_ktp} onChange={handleChange} />
                        </div>
                        <div className="md:col-span-2">
                            <label htmlFor="unit_id" className="block mb-2 text-sm font-medium text-gray-700">Ubah Penempatan Unit/Cabang</label>
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
                            {loading ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default CustomerEditPage;
