import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function EWalletPage({ auth }) {
    const [selectedWallet, setSelectedWallet] = useState(null);
    const [phoneNumber, setPhoneNumber] = useState('');
    const [amount, setAmount] = useState('');
    const [step, setStep] = useState(1); // 1: select, 2: input, 3: confirm
    const [processing, setProcessing] = useState(false);

    const ewallets = [
        { id: 'GOPAY', name: 'GoPay', icon: '🟢', fee: 1500, min: 10000, max: 2000000 },
        { id: 'OVO', name: 'OVO', icon: '🟣', fee: 1500, min: 10000, max: 2000000 },
        { id: 'DANA', name: 'DANA', icon: '🔵', fee: 1500, min: 10000, max: 2000000 },
        { id: 'SHOPEEPAY', name: 'ShopeePay', icon: '🟠', fee: 1500, min: 10000, max: 2000000 },
        { id: 'LINKAJA', name: 'LinkAja', icon: '🔴', fee: 1500, min: 10000, max: 2000000 }
    ];

    const handleSelectWallet = (wallet) => {
        setSelectedWallet(wallet);
        setStep(2);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const amt = parseFloat(amount);

        if (amt < selectedWallet.min || amt > selectedWallet.max) {
            alert(`Jumlah harus antara ${formatCurrency(selectedWallet.min)} - ${formatCurrency(selectedWallet.max)}`);
            return;
        }

        setStep(3);
    };

    const handleConfirm = async () => {
        setProcessing(true);
        try {
            // In production, this would call API
            await new Promise(resolve => setTimeout(resolve, 2000));
            alert('Top-up berhasil! Fitur ini masih dalam pengembangan.');
            resetForm();
        } catch (error) {
            console.error('Top-up failed:', error);
            alert('Top-up gagal');
        } finally {
            setProcessing(false);
        }
    };

    const resetForm = () => {
        setSelectedWallet(null);
        setPhoneNumber('');
        setAmount('');
        setStep(1);
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(value);
    };

    const getTotalAmount = () => {
        return parseFloat(amount) + selectedWallet.fee;
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Top-Up E-Wallet" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Top-Up E-Wallet</h1>
                        <p className="text-sm text-gray-600 mt-1">Isi saldo e-wallet Anda dengan mudah</p>
                    </div>

                    {/* Progress Steps */}
                    <div className="bg-white rounded-lg shadow p-6 mb-6">
                        <div className="flex items-center justify-between">
                            <div className={`flex items-center ${step >= 1 ? 'text-blue-600' : 'text-gray-400'}`}>
                                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${step >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-300'}`}>
                                    1
                                </div>
                                <span className="ml-2 font-medium">Pilih E-Wallet</span>
                            </div>
                            <div className={`flex-1 h-1 mx-4 ${step >= 2 ? 'bg-blue-600' : 'bg-gray-300'}`}></div>
                            <div className={`flex items-center ${step >= 2 ? 'text-blue-600' : 'text-gray-400'}`}>
                                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${step >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-300'}`}>
                                    2
                                </div>
                                <span className="ml-2 font-medium">Input Data</span>
                            </div>
                            <div className={`flex-1 h-1 mx-4 ${step >= 3 ? 'bg-blue-600' : 'bg-gray-300'}`}></div>
                            <div className={`flex items-center ${step >= 3 ? 'text-blue-600' : 'text-gray-400'}`}>
                                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${step >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-300'}`}>
                                    3
                                </div>
                                <span className="ml-2 font-medium">Konfirmasi</span>
                            </div>
                        </div>
                    </div>

                    {/* Step 1: Select E-Wallet */}
                    {step === 1 && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <h2 className="text-lg font-semibold mb-4">Pilih E-Wallet</h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {ewallets.map(wallet => (
                                    <button
                                        key={wallet.id}
                                        onClick={() => handleSelectWallet(wallet)}
                                        className="border-2 border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:bg-blue-50 transition text-left"
                                    >
                                        <div className="flex items-center gap-3">
                                            <span className="text-4xl">{wallet.icon}</span>
                                            <div className="flex-1">
                                                <div className="font-semibold text-gray-900">{wallet.name}</div>
                                                <div className="text-sm text-gray-600">
                                                    Biaya: {formatCurrency(wallet.fee)}
                                                </div>
                                            </div>
                                            <span className="text-blue-600">→</span>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Step 2: Input Data */}
                    {step === 2 && selectedWallet && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <button
                                    onClick={() => setStep(1)}
                                    className="text-gray-600 hover:text-gray-900"
                                >
                                    ← Kembali
                                </button>
                                <span className="text-2xl">{selectedWallet.icon}</span>
                                <h2 className="text-lg font-semibold">{selectedWallet.name}</h2>
                            </div>

                            <form onSubmit={handleSubmit}>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Nomor HP / ID E-Wallet
                                    </label>
                                    <input
                                        type="text"
                                        value={phoneNumber}
                                        onChange={(e) => setPhoneNumber(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="08123456789"
                                        required
                                    />
                                </div>

                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Jumlah Top-Up
                                    </label>
                                    <input
                                        type="number"
                                        value={amount}
                                        onChange={(e) => setAmount(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder={`Min: ${formatCurrency(selectedWallet.min)}`}
                                        min={selectedWallet.min}
                                        max={selectedWallet.max}
                                        required
                                    />
                                    <p className="text-xs text-gray-500 mt-1">
                                        Minimal: {formatCurrency(selectedWallet.min)} | Maksimal: {formatCurrency(selectedWallet.max)}
                                    </p>
                                </div>

                                {/* Quick Amount Buttons */}
                                <div className="mb-6">
                                    <div className="text-sm font-medium text-gray-700 mb-2">Nominal Cepat</div>
                                    <div className="grid grid-cols-4 gap-2">
                                        {[50000, 100000, 200000, 500000].map(amt => (
                                            <button
                                                key={amt}
                                                type="button"
                                                onClick={() => setAmount(amt.toString())}
                                                className="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
                                            >
                                                {formatCurrency(amt)}
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    className="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                                >
                                    Lanjutkan
                                </button>
                            </form>
                        </div>
                    )}

                    {/* Step 3: Confirmation */}
                    {step === 3 && selectedWallet && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <h2 className="text-lg font-semibold mb-4">Konfirmasi Top-Up</h2>

                            <div className="bg-gray-50 rounded-lg p-4 mb-6">
                                <div className="flex items-center gap-3 mb-4">
                                    <span className="text-3xl">{selectedWallet.icon}</span>
                                    <div>
                                        <div className="font-semibold text-gray-900">{selectedWallet.name}</div>
                                        <div className="text-sm text-gray-600">{phoneNumber}</div>
                                    </div>
                                </div>

                                <div className="border-t pt-4 space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Jumlah Top-Up</span>
                                        <span className="font-medium">{formatCurrency(parseFloat(amount))}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Biaya Admin</span>
                                        <span className="font-medium">{formatCurrency(selectedWallet.fee)}</span>
                                    </div>
                                    <div className="flex justify-between text-lg font-bold border-t pt-2">
                                        <span>Total</span>
                                        <span className="text-blue-600">{formatCurrency(getTotalAmount())}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-6">
                                <p className="text-sm text-yellow-800">
                                    ⚠️ Pastikan nomor tujuan sudah benar. Transaksi tidak dapat dibatalkan setelah diproses.
                                </p>
                            </div>

                            <div className="flex gap-3">
                                <button
                                    onClick={() => setStep(2)}
                                    className="flex-1 px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50"
                                    disabled={processing}
                                >
                                    Ubah
                                </button>
                                <button
                                    onClick={handleConfirm}
                                    className="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                                    disabled={processing}
                                >
                                    {processing ? 'Memproses...' : 'Konfirmasi'}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Info Box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <h4 className="font-medium text-blue-900 mb-2">ℹ️ Informasi</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Top-up akan diproses dalam 1-5 menit</li>
                            <li>• Pastikan nomor tujuan aktif dan benar</li>
                            <li>• Biaya admin sudah termasuk dalam total pembayaran</li>
                            <li>• Hubungi customer service jika ada kendala</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
