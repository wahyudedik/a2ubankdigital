import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import useApi from '../hooks/useApi.js';
import Input from '../components/ui/Input.jsx';
import Button from '../components/ui/Button.jsx';
import { ArrowLeft } from 'lucide-react';

const CustomerAddPage = () => {
    const navigate = useNavigate();
    const { loading, error, callApi } = useApi();
    const [units, setUnits] = useState([]);
    const [formData, setFormData] = useState({
        full_name: '',
        email: '',
        phone_number: '',
        nik: '',
        mother_maiden_name: '',
        pob: '',
        dob: '',
        gender: 'L',
        address_ktp: '',
        unit_id: ''
    });

    useEffect(() => {
        const fetchUnits = async () => {
            const result = await callApi('admin_get_units.php');
            if (result && result.status === 'success') {
                setUnits(result.data);
            }
        };
        fetchUnits();
    }, [callApi]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const result = await callApi('admin_add_customer.php', 'POST', formData);
        if (result && result.status === 'success') {
            navigate('/admin/customers');
        }
    };

    return (
        <div>
            <Link to="/admin/customers" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Tambah Nasabah Baru</h1>
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
                            <select name="gender" id="gender" value={formData.gender} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-taskora-green-300">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div className="md:col-span-2">
                           <Input name="address_ktp" label="Alamat KTP" value={formData.address_ktp} onChange={handleChange} />
                        </div>
                        <div className="md:col-span-2">
                             <label htmlFor="unit_id" className="block mb-2 text-sm font-medium text-gray-700">Penempatan Unit/Cabang</label>
                            <select name="unit_id" id="unit_id" value={formData.unit_id} onChange={handleChange} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg" required>
                                <option value="" disabled>Pilih penempatan...</option>
                                {units.map(branch => (
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
