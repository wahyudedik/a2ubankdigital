import React, { useState, useEffect } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';
import { ArrowLeft } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const TransferPage = () => {
    const navigate = useNavigate();
    const modal = useModal();
    const location = useLocation(); // Hook untuk mengakses data dari navigasi
    const { loading, error, callApi } = useApi();
    
    const [step, setStep] = useState(1);
    const [accountNumber, setAccountNumber] = useState('');
    const [amount, setAmount] = useState('');
    const [description, setDescription] = useState('');
    const [pin, setPin] = useState('');
    const [recipient, setRecipient] = useState(null);

    // EFEK INI AKAN BERJALAN SAAT HALAMAN DIBUKA
    useEffect(() => {
        const qrData = location.state?.qrData;
        if (qrData) {
            // Jika ada data dari QR, langsung isi semua state
            setAccountNumber(qrData.acc);
            setRecipient(qrData.name);
            setAmount(qrData.amt || '');
            setDescription(`Pembayaran QR ke ${qrData.name}`);
            // Dan langsung lompat ke langkah 2
            setStep(2);
        }
    }, [location.state]);


    const handleInquiry = async (e) => {
        e.preventDefault();
        const result = await callApi('transfer_internal_inquiry.php', 'POST', { destination_account_number: accountNumber });
        if (result && result.status === 'success') {
            setRecipient(result.data.recipient_name);
            setDescription(`Transfer ke ${result.data.recipient_name}`);
            setStep(2);
        }
    };

    const handleExecute = async (e) => {
        e.preventDefault();
        const confirmed = await modal.showConfirmation({
            title: "Konfirmasi Transfer",
            message: `Anda akan mentransfer ${formatCurrency(amount)} ke ${recipient}. Lanjutkan?`,
        });
        if (confirmed) {
            const result = await callApi('transfer_internal_execute.php', 'POST', { 
                destination_account_number: accountNumber,
                amount: amount,
                description: description,
                pin: pin
            });
            if (result && result.status === 'success') {
                await modal.showAlert({ title: "Berhasil", message: result.message, type: 'success' });
                navigate('/');
            }
        }
    };
    
    return (
        <div className="p-4">
            <Link to="/" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6">
                <ArrowLeft size={20} />
                <h1 className="text-2xl font-bold text-gray-800">Transfer Dana</h1>
            </Link>

            <div className="bg-white p-6 rounded-lg shadow-md">
                {step === 1 && (
                    <form onSubmit={handleInquiry}>
                        <h3 className="font-semibold mb-4">Langkah 1: Masukkan Rekening Tujuan</h3>
                        <Input id="accountNumber" name="accountNumber" label="Nomor Rekening" value={accountNumber} onChange={(e) => setAccountNumber(e.target.value)} required />
                        {error && <p className="text-red-500 text-sm mt-4">{error}</p>}
                        <div className="mt-6">
                            <Button type="submit" fullWidth disabled={loading}>{loading ? 'Mengecek...' : 'Lanjutkan'}</Button>
                        </div>
                    </form>
                )}
                {step === 2 && (
                    <form onSubmit={handleExecute}>
                        <h3 className="font-semibold mb-4">Langkah 2: Konfirmasi & Jumlah</h3>
                        <div className="mb-4 p-3 bg-gray-50 rounded-md">
                            <p className="text-sm text-gray-500">Penerima</p>
                            <p className="font-bold text-lg">{recipient}</p>
                            <p className="text-sm text-gray-700">{accountNumber}</p>
                        </div>
                        <Input id="amount" name="amount" type="number" label="Jumlah Transfer (Rp)" value={amount} onChange={(e) => setAmount(e.target.value)} required />
                        <div className="mt-4">
                            <Input id="description" name="description" label="Deskripsi (Opsional)" value={description} onChange={(e) => setDescription(e.target.value)} />
                        </div>
                        <div className="mt-4">
                            <Input id="pin" name="pin" type="password" label="Masukkan PIN Transaksi" value={pin} onChange={(e) => setPin(e.target.value)} required />
                        </div>
                        {error && <p className="text-red-500 text-sm mt-4 text-center">{error}</p>}
                        <div className="mt-6 flex gap-2">
                             <Button type="button" onClick={() => { setStep(1); navigate('/transfer', { replace: true }); }} className="bg-gray-200 text-gray-800 hover:bg-gray-300 w-1/3">Kembali</Button>
                            <Button type="submit" fullWidth disabled={loading}>{loading ? 'Memproses...' : 'Transfer Sekarang'}</Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
};

export default TransferPage;

