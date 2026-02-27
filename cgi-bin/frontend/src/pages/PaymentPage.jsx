import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { Html5QrcodeScanner } from 'html5-qrcode';
import useApi from '../hooks/useApi';
import { useModal } from '../contexts/ModalContext';
import Button from '../components/ui/Button';
import Input from '../components/ui/Input';
import { QrCode, Camera } from 'lucide-react';

const PaymentPage = () => {
    const [activeTab, setActiveTab] = useState('scan'); // 'scan' or 'my_qr'
    return (
        <div className="p-4">
            <h1 className="text-2xl font-bold text-gray-800 mb-4">Bayar / Terima QR</h1>
            <div className="bg-white rounded-lg shadow-md">
                <div className="flex border-b">
                    <TabButton id="scan" activeTab={activeTab} setActiveTab={setActiveTab} icon={<Camera/>}>Bayar (Scan)</TabButton>
                    <TabButton id="my_qr" activeTab={activeTab} setActiveTab={setActiveTab} icon={<QrCode/>}>Terima Uang</TabButton>
                </div>
                <div className="p-4">
                    {activeTab === 'scan' ? <ScanQr /> : <MyQr />}
                </div>
            </div>
        </div>
    );
};

const TabButton = ({ id, activeTab, setActiveTab, icon, children }) => (
    <button 
        onClick={() => setActiveTab(id)}
        className={`flex-1 p-3 font-semibold text-sm flex items-center justify-center gap-2 ${activeTab === id ? 'border-b-2 border-taskora-green-700 text-taskora-green-700' : 'text-gray-500'}`}
    >
        {icon} {children}
    </button>
);

const ScanQr = () => {
    const navigate = useNavigate();
    const modal = useModal();
    const scannerRef = useRef(false); // Menggunakan ref untuk menandai status

    useEffect(() => {
        const scanner = new Html5QrcodeScanner(
            "qr-reader", 
            { fps: 10, qrbox: { width: 250, height: 250 } },
            false // verbose
        );

        const onScanSuccess = (decodedText) => {
            // Hanya proses jika belum pernah diproses sebelumnya
            if (!scannerRef.current) {
                scannerRef.current = true; // Tandai sebagai sudah diproses
                scanner.clear(); // Hentikan scanner
                try {
                    const qrData = JSON.parse(decodedText);
                    if (qrData.iss !== 'bank.taskora.id' || !qrData.acc) {
                        throw new Error('QR Code tidak valid atau bukan QR Bank Taskora.');
                    }
                    navigate('/transfer', { state: { qrData } });
                } catch (e) {
                    modal.showAlert({ title: 'Error', message: e.message || 'Gagal memindai QR code.', type: 'warning' });
                }
            }
        };
        
        const onScanFailure = (error) => {
            // Tidak melakukan apa-apa saat gagal
        };
        
        scanner.render(onScanSuccess, onScanFailure);

        // Fungsi cleanup saat komponen dibongkar
        return () => {
            scanner.clear().catch(error => {
                // Seringkali error saat membersihkan tidak relevan jika user sudah pindah halaman
                // console.error("Gagal membersihkan scanner", error)
            });
        };
    }, [navigate, modal]);

    return (
        <div>
            <p className="text-center text-gray-600 mb-4">Arahkan kamera ke QR Code untuk melakukan pembayaran.</p>
            <div id="qr-reader" className="w-full max-w-xs mx-auto"></div>
        </div>
    );
};

const MyQr = () => {
    const { loading, error, callApi } = useApi();
    const [amount, setAmount] = useState('');
    const [qrCode, setQrCode] = useState('');

    const generateQr = async () => {
        const result = await callApi('user_payment_qr_generate.php', 'POST', { amount });
        if (result && result.status === 'success') {
            setQrCode(result.data.qr_base64);
        }
    };

    return (
        <div className="text-center">
            {qrCode ? (
                <>
                    <img src={qrCode} alt="QR Code Pembayaran" className="mx-auto" />
                    <p className="font-bold text-lg mt-2">Pindai untuk Membayar</p>
                    <Button onClick={() => setQrCode('')} className="mt-4 bg-gray-200 text-gray-800 hover:bg-gray-300">Buat QR Baru</Button>
                </>
            ) : (
                <>
                    <p className="text-gray-600 mb-4">Masukkan jumlah (opsional) untuk ditampilkan di QR Code.</p>
                    <Input 
                        type="number"
                        placeholder="Rp 0 (Opsional)"
                        value={amount}
                        onChange={(e) => setAmount(e.target.value)}
                    />
                    {error && <p className="text-red-500 text-sm mt-2">{error}</p>}
                    <Button onClick={generateQr} disabled={loading} fullWidth className="mt-4">
                        {loading ? 'Membuat...' : 'Tampilkan QR Code'}
                    </Button>
                </>
            )}
        </div>
    );
};

export default PaymentPage;

