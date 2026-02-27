import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const ChangePinPage = () => {
    const navigate = useNavigate();
    const modal = useModal();
    const { loading, error, callApi } = useApi();
    const [formData, setFormData] = useState({
        old_pin: '',
        new_pin: '',
        confirm_pin: ''
    });

    const handleChange = (e) => {
        const { name, value } = e.target;
        // Hanya izinkan angka dan batasi hingga 6 digit
        if (/^\d*$/.test(value) && value.length <= 6) {
            setFormData(prev => ({ ...prev, [name]: value }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const result = await callApi('user_security_update_pin.php', 'POST', formData);
        if (result && result.status === 'success') {
            await modal.showAlert({ title: 'Berhasil', message: 'PIN Anda telah berhasil diperbarui.', type: 'success' });
            navigate('/profile');
        }
    };

    return (
        <div>
            <Link to="/profile" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Ubah PIN Transaksi</h1>
            </Link>

            <div className="bg-white p-6 rounded-lg shadow-md">
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <Input 
                            name="old_pin" 
                            type="password" 
                            label="PIN Lama (Kosongkan jika baru pertama kali)" 
                            value={formData.old_pin} 
                            onChange={handleChange}
                            placeholder="6 digit PIN lama"
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
                            {loading ? 'Menyimpan...' : 'Simpan PIN'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default ChangePinPage;
