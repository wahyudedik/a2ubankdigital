import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { Trash2 } from 'lucide-react';

// Komponen untuk form ganti password (tidak berubah)
const ChangePasswordForm = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [formData, setFormData] = useState({
        current_password: '',
        new_password: '',
        confirm_password: ''
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (formData.new_password !== formData.confirm_password) {
            modal.showAlert({ title: 'Error', message: 'Password baru dan konfirmasi tidak cocok.', type: 'warning' });
            return;
        }

        const result = await callApi('user_security_update_password.php', 'POST', {
            current_password: formData.current_password,
            new_password: formData.new_password
        });

        if (result && result.status === 'success') {
            await modal.showAlert({ title: 'Berhasil', message: 'Password Anda telah berhasil diperbarui.', type: 'success' });
            setFormData({ current_password: '', new_password: '', confirm_password: '' });
        } else {
            modal.showAlert({ title: 'Gagal', message: error || result?.message, type: 'warning' });
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <div className="space-y-4">
                <Input name="current_password" type="password" label="Password Saat Ini" value={formData.current_password} onChange={handleChange} required />
                <Input name="new_password" type="password" label="Password Baru" value={formData.new_password} onChange={handleChange} required />
                <Input name="confirm_password" type="password" label="Konfirmasi Password Baru" value={formData.confirm_password} onChange={handleChange} required />
            </div>
            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
            <div className="mt-6 flex justify-end">
                <Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Ubah Password'}</Button>
            </div>
        </form>
    );
};

// --- PERBAIKAN UTAMA DI SINI ---
const SystemSettingsForm = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [settings, setSettings] = useState({
        monthly_admin_fee: '',
        transfer_fee_external: '',
        payment_qris_image_url: '',
        payment_bank_accounts: [],
        APP_DOWNLOAD_LINK_IOS: '',      // <-- Tambah state baru
        APP_DOWNLOAD_LINK_ANDROID: ''   // <-- Tambah state baru
    });
    const [newAccount, setNewAccount] = useState({ bank_name: '', account_number: '', account_name: '' });

    const fetchSettings = useCallback(async () => {
        const result = await callApi('admin_get_settings.php');
        if (result && result.status === 'success') {
            setSettings({
                monthly_admin_fee: result.data.monthly_admin_fee || '',
                transfer_fee_external: result.data.transfer_fee_external || '',
                payment_qris_image_url: result.data.payment_qris_image_url || '',
                payment_bank_accounts: JSON.parse(result.data.payment_bank_accounts || '[]'),
                APP_DOWNLOAD_LINK_IOS: result.data.APP_DOWNLOAD_LINK_IOS || '',         // <-- Ambil data baru
                APP_DOWNLOAD_LINK_ANDROID: result.data.APP_DOWNLOAD_LINK_ANDROID || ''  // <-- Ambil data baru
            });
        }
    }, [callApi]);

    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSettings(prev => ({ ...prev, [name]: value }));
    };
    
    const handleNewAccountChange = (e) => {
        const { name, value } = e.target;
        setNewAccount(prev => ({ ...prev, [name]: value }));
    };

    const addAccount = () => {
        if (newAccount.bank_name && newAccount.account_number && newAccount.account_name) {
            setSettings(prev => ({
                ...prev,
                payment_bank_accounts: [...prev.payment_bank_accounts, newAccount]
            }));
            setNewAccount({ bank_name: '', account_number: '', account_name: '' });
        } else {
            modal.showAlert({ title:'Info', message:'Harap isi semua detail rekening baru.'});
        }
    };
    
    const removeAccount = (index) => {
        setSettings(prev => ({
            ...prev,
            payment_bank_accounts: prev.payment_bank_accounts.filter((_, i) => i !== index)
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const payload = {
            ...settings, // Kirim semua state
            payment_bank_accounts: JSON.stringify(settings.payment_bank_accounts)
        };
        const result = await callApi('admin_config_update.php', 'POST', payload);
        if (result && result.status === 'success') {
            modal.showAlert({ title: 'Berhasil', message: 'Pengaturan sistem telah berhasil disimpan.', type: 'success' });
        }
    };

    if (loading && !settings.monthly_admin_fee) return <p>Memuat pengaturan...</p>;

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {/* --- BAGIAN BARU: PENGATURAN LINK DOWNLOAD --- */}
            <div>
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Pengaturan Tautan Aplikasi</h3>
                <div className="space-y-4">
                     <Input name="APP_DOWNLOAD_LINK_ANDROID" label="Link Google Play Store" type="url" value={settings.APP_DOWNLOAD_LINK_ANDROID} onChange={handleChange} placeholder="https://play.google.com/store/apps/..."/>
                    <Input name="APP_DOWNLOAD_LINK_IOS" label="Link Apple App Store" type="url" value={settings.APP_DOWNLOAD_LINK_IOS} onChange={handleChange} placeholder="https://apps.apple.com/..." />
                </div>
            </div>
            {/* --- AKHIR BAGIAN BARU --- */}

            <div>
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Pengaturan Biaya</h3>
                <div className="space-y-4">
                     <Input name="monthly_admin_fee" label="Biaya Administrasi Bulanan (Rp)" type="number" value={settings.monthly_admin_fee} onChange={handleChange} required />
                    <Input name="transfer_fee_external" label="Biaya Transfer Antar Bank (Rp)" type="number" value={settings.transfer_fee_external} onChange={handleChange} required />
                </div>
            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-700 mb-2">Metode Pembayaran Isi Saldo</h3>
                <Input name="payment_qris_image_url" label="URL Gambar QRIS" value={settings.payment_qris_image_url} onChange={handleChange} placeholder="https://.../qris.png" />
                
                <div className="mt-4">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Rekening Bank untuk Transfer</label>
                    <div className="space-y-2">
                        {settings.payment_bank_accounts.map((acc, index) => (
                            <div key={index} className="flex items-center gap-2 bg-gray-50 p-2 rounded">
                                <p className="flex-grow text-sm">{acc.bank_name} - {acc.account_number} (a/n {acc.account_name})</p>
                                <button type="button" onClick={() => removeAccount(index)} className="text-red-500 hover:text-red-700 p-1"><Trash2 size={16}/></button>
                            </div>
                        ))}
                    </div>
                     <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-4 border-t pt-4">
                        <Input name="bank_name" value={newAccount.bank_name} onChange={handleNewAccountChange} placeholder="Nama Bank" />
                        <Input name="account_number" value={newAccount.account_number} onChange={handleNewAccountChange} placeholder="No. Rekening" />
                        <Input name="account_name" value={newAccount.account_name} onChange={handleNewAccountChange} placeholder="Atas Nama" />
                     </div>
                     <Button type="button" onClick={addAccount} className="mt-2 text-sm py-1 px-3 bg-gray-600 hover:bg-gray-700">Tambah Rekening</Button>
                </div>
            </div>

            {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
            <div className="mt-8 border-t pt-6 flex justify-end">
                <Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan Semua Pengaturan'}</Button>
            </div>
        </form>
    );
};


const SettingsPage = () => {
    const user = JSON.parse(localStorage.getItem('authUser') || '{}');
    const isSuperAdmin = user.roleId === 1;

    return (
        <div>
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Pengaturan</h1>
            
            <div className="bg-white p-8 rounded-lg shadow-md max-w-2xl mb-8">
                <h2 className="text-xl font-semibold text-gray-700 mb-4">Ubah Password Akun Anda</h2>
                <ChangePasswordForm />
            </div>

            {isSuperAdmin && (
                <div className="bg-white p-8 rounded-lg shadow-md max-w-2xl">
                     <h2 className="text-xl font-semibold text-gray-700 mb-4">Pengaturan Sistem & Pembayaran</h2>
                    <SystemSettingsForm />
                </div>
            )}
        </div>
    );
};

export default SettingsPage;
