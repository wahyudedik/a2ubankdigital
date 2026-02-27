import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import { ArrowLeft, Upload, Banknote, QrCode as QrCodeIcon, ClipboardCopy } from 'lucide-react';

const TopUpPage = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const navigate = useNavigate();

    // State untuk data dari backend
    const [paymentMethods, setPaymentMethods] = useState({ qris_image_url: null, bank_accounts: [] });

    // State untuk alur UI
    const [selectedMethod, setSelectedMethod] = useState('');
    const [selectedBankIndex, setSelectedBankIndex] = useState('');

    // State untuk form
    const [amount, setAmount] = useState('');
    const [proof, setProof] = useState(null);
    const [preview, setPreview] = useState(null);

    useEffect(() => {
        const fetchMethods = async () => {
            const result = await callApi('utility_get_payment_methods.php');
            if (result && result.status === 'success') {
                setPaymentMethods(result.data);
            }
        };
        fetchMethods();
    }, [callApi]);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file && file.size < 2097152) { // Batas 2MB
            setProof(file);
            setPreview(URL.createObjectURL(file));
        } else if (file) {
            modal.showAlert({ title: 'Ukuran File Terlalu Besar', message: 'Ukuran file maksimal adalah 2MB.', type: 'warning' });
        }
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text).then(() => {
            modal.showAlert({ title: 'Berhasil', message: 'Nomor rekening berhasil disalin.', type: 'success' });
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!selectedMethod) {
            modal.showAlert({ title: 'Error', message: 'Silakan pilih metode pembayaran.', type: 'warning' });
            return;
        }

        if (selectedMethod === 'bank' && selectedBankIndex === '') {
            modal.showAlert({ title: 'Error', message: 'Silakan pilih bank tujuan.', type: 'warning' });
            return;
        }

        if (!proof) {
            modal.showAlert({ title: 'Error', message: 'Silakan upload bukti pembayaran.', type: 'warning' });
            return;
        }

        const apiFormData = new FormData();
        apiFormData.append('amount', amount);
        apiFormData.append('payment_method', selectedMethod === 'bank' ? `Transfer ${paymentMethods.bank_accounts[selectedBankIndex].bank_name}` : 'QRIS');
        apiFormData.append('proof', proof);

        const token = localStorage.getItem('authToken');
        const baseUrl = 'http://a2ubankdigital.my.id.test/app';

        try {
            const response = await fetch(`${baseUrl}/user_create_topup_request.php`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: apiFormData
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                await modal.showAlert({ title: "Berhasil", message: result.message, type: "success" });
                navigate('/dashboard');
            } else {
                modal.showAlert({ title: "Gagal", message: result.message || 'Terjadi kesalahan.', type: "warning" });
            }
        } catch (err) {
            console.error('Error:', err);
            modal.showAlert({ title: "Error", message: 'Gagal menghubungi server. Silakan coba lagi.', type: "warning" });
        }
    };

    const selectedBankAccount = paymentMethods.bank_accounts[selectedBankIndex];

    return (
        <div>
            <Link to="/dashboard" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Isi Saldo</h1>
            </Link>
            <form onSubmit={handleSubmit} className="bg-white p-6 rounded-lg shadow-md space-y-6">
                <Input label="1. Masukkan Jumlah Isi Saldo (Rp)" type="number" value={amount} onChange={(e) => setAmount(e.target.value)} required />

                <div>
                    <label className="block mb-2 text-sm font-medium text-gray-700">2. Pilih Metode Pembayaran</label>
                    <select value={selectedMethod} onChange={(e) => setSelectedMethod(e.target.value)} className="w-full px-4 py-2 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg">
                        <option value="" disabled>Pilih metode...</option>
                        {paymentMethods.qris_image_url && <option value="qris">QRIS</option>}
                        {paymentMethods.bank_accounts.length > 0 && <option value="bank">Transfer Bank</option>}
                    </select>
                </div>

                {/* Tampilan Kondisional berdasarkan Metode */}
                {selectedMethod === 'qris' && paymentMethods.qris_image_url && (
                    <div className="text-center p-4 border rounded-lg bg-gray-50">
                        <h3 className="font-semibold mb-2">Silakan Pindai QRIS di Bawah</h3>
                        {/* --- KODE YANG DIPERBARUI DIMULAI DI SINI --- */}
                        <div className="w-56 h-56 mx-auto border rounded-lg bg-white p-2 flex items-center justify-center">
                            <img
                                src={paymentMethods.qris_image_url}
                                alt="QRIS Code"
                                className="w-full h-full object-contain"
                            />
                        </div>
                        {/* --- KODE YANG DIPERBARUI BERAKHIR DI SINI --- */}
                    </div>
                )}
                {selectedMethod === 'bank' && (
                    <div className="p-4 border rounded-lg bg-gray-50 space-y-3">
                        <h3 className="font-semibold">Silakan Transfer ke Rekening Berikut</h3>
                        <select value={selectedBankIndex} onChange={(e) => setSelectedBankIndex(e.target.value)} className="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg">
                            <option value="" disabled>Pilih bank tujuan...</option>
                            {paymentMethods.bank_accounts.map((acc, index) => (
                                <option key={index} value={index}>{acc.bank_name}</option>
                            ))}
                        </select>
                        {selectedBankAccount && (
                            <div className="p-3 bg-white rounded-md border text-sm">
                                <p><strong>{selectedBankAccount.bank_name}</strong></p>
                                <div className="flex justify-between items-center">
                                    <p className="font-mono text-lg">{selectedBankAccount.account_number}</p>
                                    <button type="button" onClick={() => copyToClipboard(selectedBankAccount.account_number)} className="p-1 text-gray-500 hover:text-black"><ClipboardCopy size={16} /></button>
                                </div>
                                <p>a/n {selectedBankAccount.account_name}</p>
                            </div>
                        )}
                    </div>
                )}

                {/* Upload Bukti */}
                <div>
                    <label className="block mb-2 text-sm font-medium text-gray-700">3. Unggah Bukti Pembayaran</label>
                    <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div className="space-y-1 text-center">
                            {preview ? <img src={preview} alt="Preview" className="w-32 h-32 mx-auto object-cover" /> : <Upload className="mx-auto h-12 w-12 text-gray-400" />}
                            <div className="flex text-sm text-gray-600">
                                <label htmlFor="file-upload" className="relative cursor-pointer bg-white rounded-md font-medium text-taskora-green-600 hover:text-taskora-green-500">
                                    <span>Pilih file</span>
                                    <input id="file-upload" name="proof" type="file" className="sr-only" onChange={handleFileChange} required accept="image/*" />
                                </label>
                                <p className="pl-1">atau seret dan lepas</p>
                            </div>
                            <p className="text-xs text-gray-500">PNG, JPG, GIF hingga 2MB</p>
                        </div>
                    </div>
                </div>

                {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                <Button type="submit" fullWidth disabled={loading} className="mt-4">{loading ? 'Mengirim...' : 'Kirim Konfirmasi'}</Button>
            </form>
        </div>
    );
};

export default TopUpPage;
