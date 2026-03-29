import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function QrPaymentPage({ auth }) {
    const [activeTab, setActiveTab] = useState('generate'); // 'generate' or 'scan'
    const [amount, setAmount] = useState('');
    const [qrCode, setQrCode] = useState(null);
    const [generating, setGenerating] = useState(false);
    const [scanData, setScanData] = useState('');
    const [scannedInfo, setScannedInfo] = useState(null);
    const [paymentAmount, setPaymentAmount] = useState('');
    const [processing, setProcessing] = useState(false);

    const generateQR = async (e) => {
        e.preventDefault();
        setGenerating(true);

        try {
            const response = await fetch('/user/payment/qr-generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({ amount: amount || 0 })
            });
            const data = await response.json();

            if (data.status === 'success') {
                setQrCode(data.data);
            } else {
                alert(data.message || 'Gagal membuat QR Code');
            }
        } catch (error) {
            console.error('Failed to generate QR:', error);
            alert('Terjadi kesalahan saat membuat QR Code');
        } finally {
            setGenerating(false);
        }
    };

    const scanQR = async (e) => {
        e.preventDefault();
        if (!scanData.trim()) {
            alert('Mohon masukkan data QR');
            return;
        }

        try {
            const response = await fetch('/user/payment/qr-scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({ qr_data: scanData })
            });
            const data = await response.json();

            if (data.status === 'success') {
                setScannedInfo(data.data);
                if (data.data.amount > 0) {
                    setPaymentAmount(data.data.amount.toString());
                }
            } else {
                alert(data.message || 'QR Code tidak valid');
            }
        } catch (error) {
            console.error('Failed to scan QR:', error);
            alert('Terjadi kesalahan saat memindai QR Code');
        }
    };

    const executePayment = async (e) => {
        e.preventDefault();
        if (!paymentAmount || parseFloat(paymentAmount) < 1000) {
            alert('Minimal pembayaran Rp 1.000');
            return;
        }

        setProcessing(true);
        try {
            const response = await fetch('/user/payment/qr-pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({
                    qr_data: scanData,
                    amount: parseFloat(paymentAmount)
                })
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert('Pembayaran berhasil!');
                setScanData('');
                setScannedInfo(null);
                setPaymentAmount('');
            } else {
                alert(data.message || 'Pembayaran gagal');
            }
        } catch (error) {
            console.error('Payment failed:', error);
            alert('Terjadi kesalahan saat melakukan pembayaran');
        } finally {
            setProcessing(false);
        }
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(value);
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Pembayaran QR" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Pembayaran QR</h1>
                        <p className="text-sm text-gray-600 mt-1">Terima atau bayar menggunakan QR Code</p>
                    </div>

                    {/* Tabs */}
                    <div className="bg-white rounded-lg shadow mb-6">
                        <div className="flex border-b">
                            <button
                                onClick={() => setActiveTab('generate')}
                                className={`flex-1 px-6 py-3 font-medium transition ${activeTab === 'generate'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-600 hover:text-gray-900'
                                    }`}
                            >
                                🔲 Terima Pembayaran
                            </button>
                            <button
                                onClick={() => setActiveTab('scan')}
                                className={`flex-1 px-6 py-3 font-medium transition ${activeTab === 'scan'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-600 hover:text-gray-900'
                                    }`}
                            >
                                📷 Bayar dengan QR
                            </button>
                        </div>
                    </div>

                    {/* Generate QR Tab */}
                    {activeTab === 'generate' && (
                        <div className="bg-white rounded-lg shadow p-6">
                            {!qrCode ? (
                                <form onSubmit={generateQR}>
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Jumlah (Opsional)
                                        </label>
                                        <input
                                            type="number"
                                            value={amount}
                                            onChange={(e) => setAmount(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Kosongkan untuk jumlah dinamis"
                                            min="0"
                                        />
                                        <p className="text-xs text-gray-500 mt-1">
                                            Jika dikosongkan, pembayar dapat memasukkan jumlah sendiri
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        disabled={generating}
                                        className="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium disabled:bg-gray-400"
                                    >
                                        {generating ? 'Membuat QR Code...' : '🔲 Buat QR Code'}
                                    </button>
                                </form>
                            ) : (
                                <div className="text-center">
                                    <h3 className="text-lg font-semibold mb-4">QR Code Anda</h3>

                                    {/* QR Code Display */}
                                    <div className="bg-gray-100 p-8 rounded-lg mb-4 inline-block">
                                        <div className="w-64 h-64 bg-white flex items-center justify-center">
                                            <div className="text-center">
                                                <div className="text-6xl mb-2">🔲</div>
                                                <p className="text-xs text-gray-500">QR Code</p>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Info */}
                                    <div className="bg-gray-50 rounded-lg p-4 mb-4 text-left">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <div className="text-xs text-gray-600">Nama</div>
                                                <div className="font-medium">{qrCode.recipient_name}</div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-gray-600">No. Rekening</div>
                                                <div className="font-medium">{qrCode.account_number}</div>
                                            </div>
                                            {qrCode.amount > 0 && (
                                                <div className="col-span-2">
                                                    <div className="text-xs text-gray-600">Jumlah</div>
                                                    <div className="text-lg font-bold text-blue-600">
                                                        {formatCurrency(qrCode.amount)}
                                                    </div>
                                                </div>
                                            )}
                                            <div className="col-span-2">
                                                <div className="text-xs text-gray-600">Berlaku Hingga</div>
                                                <div className="font-medium">{new Date(qrCode.expires_at).toLocaleString('id-ID')}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        onClick={() => setQrCode(null)}
                                        className="w-full py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                    >
                                        Buat QR Baru
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Scan QR Tab */}
                    {activeTab === 'scan' && (
                        <div className="bg-white rounded-lg shadow p-6">
                            {!scannedInfo ? (
                                <form onSubmit={scanQR}>
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Data QR Code
                                        </label>
                                        <textarea
                                            value={scanData}
                                            onChange={(e) => setScanData(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            rows="4"
                                            placeholder="Paste data QR Code di sini"
                                            required
                                        />
                                        <p className="text-xs text-gray-500 mt-1">
                                            📷 Gunakan aplikasi scanner QR untuk mendapatkan data QR
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        className="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                                    >
                                        📷 Scan QR Code
                                    </button>
                                </form>
                            ) : (
                                <form onSubmit={executePayment}>
                                    <h3 className="text-lg font-semibold mb-4">Detail Pembayaran</h3>

                                    {/* Recipient Info */}
                                    <div className="bg-gray-50 rounded-lg p-4 mb-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <div className="text-xs text-gray-600">Penerima</div>
                                                <div className="font-medium">{scannedInfo.recipient_name}</div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-gray-600">No. Rekening</div>
                                                <div className="font-medium">{scannedInfo.account_number}</div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Amount Input */}
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Jumlah Pembayaran
                                        </label>
                                        <input
                                            type="number"
                                            value={paymentAmount}
                                            onChange={(e) => setPaymentAmount(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Masukkan jumlah"
                                            min="1000"
                                            required
                                            disabled={scannedInfo.is_fixed_amount}
                                        />
                                        {scannedInfo.is_fixed_amount && (
                                            <p className="text-xs text-orange-600 mt-1">
                                                ⚠️ Jumlah sudah ditentukan oleh penerima
                                            </p>
                                        )}
                                    </div>

                                    {/* Actions */}
                                    <div className="flex gap-3">
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setScannedInfo(null);
                                                setScanData('');
                                                setPaymentAmount('');
                                            }}
                                            className="flex-1 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                            disabled={processing}
                                        >
                                            Batal
                                        </button>
                                        <button
                                            type="submit"
                                            className="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                                            disabled={processing}
                                        >
                                            {processing ? 'Memproses...' : '💳 Bayar'}
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    )}

                    {/* Info Box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <h4 className="font-medium text-blue-900 mb-2">ℹ️ Informasi</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• QR Code berlaku selama 30 menit</li>
                            <li>• Pembayaran QR tidak dikenakan biaya admin</li>
                            <li>• Pastikan data QR Code valid sebelum melakukan pembayaran</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
