import React, { useState, useEffect } from 'react';
import CustomerLayout from '../Layouts/CustomerLayout';
import { Building2, ArrowRight, AlertCircle } from 'lucide-react';

export default function ExternalTransferPage() {
    const [banks, setBanks] = useState([]);
    const [loading, setLoading] = useState(false);
    const [step, setStep] = useState(1); // 1: Form, 2: Confirmation, 3: Success
    const [formData, setFormData] = useState({
        bank_code: '',
        account_number: '',
        account_name: '',
        amount: '',
        description: ''
    });
    const [inquiryData, setInquiryData] = useState(null);
    const [error, setError] = useState('');

    useEffect(() => {
        fetchBanks();
    }, []);

    const fetchBanks = async () => {
        try {
            const response = await fetch('/ajax/user/external-banks');
            const data = await response.json();
            if (data.status === 'success') {
                setBanks(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    };

    const handleInquiry = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await fetch('/ajax/user/external-transfer/inquiry', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.status === 'success') {
                setInquiryData(data.data);
                setStep(2);
            } else {
                setError(data.message || 'Terjadi kesalahan saat inquiry');
            }
        } catch (error) {
            console.error('Error:', error);
            setError('Terjadi kesalahan koneksi');
        } finally {
            setLoading(false);
        }
    };

    const handleExecute = async () => {
        setLoading(true);
        setError('');

        try {
            const response = await fetch('/ajax/user/external-transfer/execute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.status === 'success') {
                setStep(3);
            } else {
                setError(data.message || 'Transfer gagal');
                setStep(1);
            }
        } catch (error) {
            console.error('Error:', error);
            setError('Terjadi kesalahan koneksi');
            setStep(1);
        } finally {
            setLoading(false);
        }
    };

    const handleReset = () => {
        setStep(1);
        setFormData({
            bank_code: '',
            account_number: '',
            account_name: '',
            amount: '',
            description: ''
        });
        setInquiryData(null);
        setError('');
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };

    const selectedBank = banks.find(b => b.bank_code === formData.bank_code);

    return (
        <CustomerLayout>
            <div className="p-6 max-w-2xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">Transfer ke Bank Lain</h1>
                    <p className="text-gray-600 mt-1">Transfer uang ke rekening bank lain</p>
                </div>

                {/* Progress Steps */}
                <div className="flex items-center justify-center mb-8">
                    <div className="flex items-center">
                        <div className={`w-10 h-10 rounded-full flex items-center justify-center ${step >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'
                            }`}>
                            1
                        </div>
                        <div className={`w-20 h-1 ${step >= 2 ? 'bg-blue-600' : 'bg-gray-200'}`}></div>
                        <div className={`w-10 h-10 rounded-full flex items-center justify-center ${step >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'
                            }`}>
                            2
                        </div>
                        <div className={`w-20 h-1 ${step >= 3 ? 'bg-blue-600' : 'bg-gray-200'}`}></div>
                        <div className={`w-10 h-10 rounded-full flex items-center justify-center ${step >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'
                            }`}>
                            3
                        </div>
                    </div>
                </div>

                {error && (
                    <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 flex items-start gap-3">
                        <AlertCircle className="text-red-600 flex-shrink-0 mt-0.5" size={20} />
                        <p className="text-red-800">{error}</p>
                    </div>
                )}

                {/* Step 1: Form */}
                {step === 1 && (
                    <div className="bg-white rounded-lg shadow p-6">
                        <h2 className="text-lg font-semibold text-gray-800 mb-4">Informasi Transfer</h2>
                        <form onSubmit={handleInquiry}>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Bank Tujuan
                                    </label>
                                    <select
                                        value={formData.bank_code}
                                        onChange={(e) => setFormData({ ...formData, bank_code: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        required
                                    >
                                        <option value="">Pilih Bank</option>
                                        {banks.map((bank) => (
                                            <option key={bank.bank_code} value={bank.bank_code}>
                                                {bank.bank_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Nomor Rekening Tujuan
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.account_number}
                                        onChange={(e) => setFormData({ ...formData, account_number: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Masukkan nomor rekening"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Nama Penerima
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.account_name}
                                        onChange={(e) => setFormData({ ...formData, account_name: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Masukkan nama penerima"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Jumlah Transfer (Rp)
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.amount}
                                        onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Masukkan jumlah"
                                        min="10000"
                                        required
                                    />
                                    <p className="text-xs text-gray-500 mt-1">Minimum transfer Rp 10.000</p>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Keterangan (Opsional)
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.description}
                                        onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        placeholder="Catatan transfer"
                                        maxLength="255"
                                    />
                                </div>

                                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p className="text-sm text-yellow-800">
                                        <strong>Biaya Admin:</strong> Rp 6.500 per transaksi
                                    </p>
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full mt-6 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium"
                            >
                                {loading ? 'Memproses...' : 'Lanjutkan'}
                            </button>
                        </form>
                    </div>
                )}

                {/* Step 2: Confirmation */}
                {step === 2 && inquiryData && (
                    <div className="bg-white rounded-lg shadow p-6">
                        <h2 className="text-lg font-semibold text-gray-800 mb-4">Konfirmasi Transfer</h2>

                        <div className="space-y-4 mb-6">
                            <div className="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                                <Building2 className="text-blue-600" size={40} />
                                <div>
                                    <p className="text-sm text-gray-600">Bank Tujuan</p>
                                    <p className="font-semibold text-gray-900">{selectedBank?.bank_name}</p>
                                </div>
                            </div>

                            <div className="border-t border-gray-200 pt-4 space-y-3">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Nomor Rekening</span>
                                    <span className="font-medium">{formData.account_number}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Nama Penerima</span>
                                    <span className="font-medium">{formData.account_name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Jumlah Transfer</span>
                                    <span className="font-medium text-blue-600">{formatCurrency(formData.amount)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Biaya Admin</span>
                                    <span className="font-medium">Rp 6.500</span>
                                </div>
                                {formData.description && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Keterangan</span>
                                        <span className="font-medium">{formData.description}</span>
                                    </div>
                                )}
                                <div className="border-t border-gray-200 pt-3 flex justify-between">
                                    <span className="font-semibold text-gray-900">Total</span>
                                    <span className="font-bold text-lg text-blue-600">
                                        {formatCurrency(parseFloat(formData.amount) + 6500)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div className="flex gap-3">
                            <button
                                onClick={() => setStep(1)}
                                className="flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium"
                            >
                                Kembali
                            </button>
                            <button
                                onClick={handleExecute}
                                disabled={loading}
                                className="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium"
                            >
                                {loading ? 'Memproses...' : 'Transfer Sekarang'}
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 3: Success */}
                {step === 3 && (
                    <div className="bg-white rounded-lg shadow p-6 text-center">
                        <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg className="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 className="text-2xl font-bold text-gray-800 mb-2">Transfer Berhasil!</h2>
                        <p className="text-gray-600 mb-6">
                            Transfer Anda ke {selectedBank?.bank_name} sebesar {formatCurrency(formData.amount)} telah berhasil diproses.
                        </p>

                        <div className="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Bank Tujuan</span>
                                    <span className="font-medium">{selectedBank?.bank_name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Rekening</span>
                                    <span className="font-medium">{formData.account_number}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Penerima</span>
                                    <span className="font-medium">{formData.account_name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Jumlah</span>
                                    <span className="font-medium text-blue-600">{formatCurrency(formData.amount)}</span>
                                </div>
                            </div>
                        </div>

                        <button
                            onClick={handleReset}
                            className="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 font-medium"
                        >
                            Transfer Lagi
                        </button>
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
}
