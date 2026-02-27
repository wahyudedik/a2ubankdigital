import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { ArrowLeft } from 'lucide-react';

// Komponen untuk menampilkan item detail
const DetailItem = ({ label, value }) => (
    <div className="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
        <dt className="text-sm font-medium text-gray-500">{label}</dt>
        <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{value || '-'}</dd>
    </div>
);

const ProfileInfoPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [profile, setProfile] = useState(null);
    const [formData, setFormData] = useState({
        address_domicile: '',
        occupation: ''
    });

    const fetchProfile = useCallback(async () => {
        const result = await callApi('user_profile_get.php');
        if (result && result.status === 'success') {
            setProfile(result.data);
            setFormData({
                address_domicile: result.data.address_domicile || '',
                occupation: result.data.occupation || ''
            });
        }
    }, [callApi]);

    useEffect(() => {
        fetchProfile();
    }, [fetchProfile]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const result = await callApi('user_profile_update.php', 'POST', formData);
        if (result && result.status === 'success') {
            modal.showAlert({ title: 'Berhasil', message: 'Informasi profil Anda telah diperbarui.', type: 'success' });
            fetchProfile(); // Muat ulang data untuk menampilkan perubahan
        }
    };

    if (loading && !profile) {
        return <div className="p-4 text-center">Memuat profil...</div>;
    }

    return (
        <div>
            <Link to="/profile" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Informasi Pribadi</h1>
            </Link>

            {/* Bagian Tampilan Data */}
            <div className="bg-white rounded-lg shadow-md mb-6">
                <div className="p-4 border-b">
                    <h3 className="text-lg font-medium">Data Terdaftar</h3>
                </div>
                <div className="p-4">
                    {profile ? (
                        <dl className="divide-y divide-gray-200">
                            <DetailItem label="Nama Lengkap" value={profile.full_name} />
                            <DetailItem label="Email" value={profile.email} />
                            <DetailItem label="Nomor Telepon" value={profile.phone_number} />
                            <DetailItem label="NIK" value={profile.nik} />
                            <DetailItem label="Nama Ibu Kandung" value={profile.mother_maiden_name} />
                            <DetailItem label="Tanggal Lahir" value={profile.dob ? new Date(profile.dob).toLocaleDateString('id-ID') : '-'} />
                        </dl>
                    ) : <p>Gagal memuat data.</p>}
                </div>
            </div>

            {/* Bagian Form Edit */}
            <div className="bg-white rounded-lg shadow-md">
                 <div className="p-4 border-b">
                    <h3 className="text-lg font-medium">Data yang Dapat Diubah</h3>
                </div>
                <form onSubmit={handleSubmit} className="p-4">
                    <div className="space-y-4">
                        <Input
                            name="address_domicile"
                            label="Alamat Domisili"
                            value={formData.address_domicile}
                            onChange={handleChange}
                            placeholder="Masukkan alamat tinggal Anda saat ini"
                        />
                        <Input
                            name="occupation"
                            label="Pekerjaan"
                            value={formData.occupation}
                            onChange={handleChange}
                            placeholder="Contoh: Karyawan Swasta"
                        />
                    </div>
                    {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                    <div className="mt-6 flex justify-end">
                        <Button type="submit" disabled={loading}>
                            {loading ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ProfileInfoPage;
