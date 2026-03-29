import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function DebtCollectionPage({ auth }) {
    const [overdueLoans, setOverdueLoans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedLoan, setSelectedLoan] = useState(null);
    const [showContactModal, setShowContactModal] = useState(false);
    const [contactNote, setContactNote] = useState('');
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        fetchOverdueLoans();
    }, []);

    const fetchOverdueLoans = async () => {
        try {
            // In production, this would fetch from API
            // For now, using mock data
            setOverdueLoans([]);
        } catch (error) {
            console.error('Failed to fetch overdue loans:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleContact = async (e) => {
        e.preventDefault();
        setProcessing(true);

        try {
            // In production, this would call API to log contact
            await new Promise(resolve => setTimeout(resolve, 1000));
            alert('Catatan kontak berhasil disimpan');
            setShowContactModal(false);
            setContactNote('');
        } catch (error) {
            console.error('Failed to save contact:', error);
            alert('Gagal menyimpan catatan');
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

    const getDaysOverdue = (dueDate) => {
        const due = new Date(dueDate);
        const today = new Date();
        const diff = Math.floor((today - due) / (1000 * 60 * 60 * 24));
        return diff;
    };

    const getSeverityColor = (days) => {
        if (days >= 90) return 'bg-red-600';
        if (days >= 60) return 'bg-orange-600';
        if (days >= 30) return 'bg-yellow-600';
        return 'bg-blue-600';
    };

    if (loading) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Penagihan" />
                <div className="py-12 text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Penagihan" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Penagihan Pinjaman</h1>
                        <p className="text-sm text-gray-600 mt-1">Kelola pinjaman yang menunggak</p>
                    </div>

                    {/* Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-sm text-gray-600">Total Tunggakan</div>
                            <div className="text-2xl font-bold text-red-600">0</div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-sm text-gray-600">1-30 Hari</div>
                            <div className="text-2xl font-bold text-yellow-600">0</div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-sm text-gray-600">31-60 Hari</div>
                            <div className="text-2xl font-bold text-orange-600">0</div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4">
                            <div className="text-sm text-gray-600">&gt; 60 Hari</div>
                            <div className="text-2xl font-bold text-red-600">0</div>
                        </div>
                    </div>

                    {/* Overdue Loans List */}
                    <div className="bg-white rounded-lg shadow">
                        <div className="p-6 border-b">
                            <h2 className="text-lg font-semibold">Daftar Pinjaman Menunggak</h2>
                        </div>

                        {overdueLoans.length === 0 ? (
                            <div className="p-12 text-center">
                                <div className="text-6xl mb-4">✅</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Tidak Ada Tunggakan</h3>
                                <p className="text-gray-600">Semua pinjaman dalam kondisi baik</p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {overdueLoans.map(loan => {
                                    const daysOverdue = getDaysOverdue(loan.due_date);
                                    return (
                                        <div key={loan.id} className="p-4 hover:bg-gray-50">
                                            <div className="flex justify-between items-start">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-3 mb-2">
                                                        <h3 className="font-semibold text-gray-900">{loan.customer_name}</h3>
                                                        <span className={`px-2 py-1 rounded text-xs text-white ${getSeverityColor(daysOverdue)}`}>
                                                            {daysOverdue} hari
                                                        </span>
                                                    </div>
                                                    <div className="grid grid-cols-3 gap-4 text-sm">
                                                        <div>
                                                            <div className="text-gray-600">Nomor Pinjaman</div>
                                                            <div className="font-medium">{loan.loan_number}</div>
                                                        </div>
                                                        <div>
                                                            <div className="text-gray-600">Tunggakan</div>
                                                            <div className="font-medium text-red-600">{formatCurrency(loan.overdue_amount)}</div>
                                                        </div>
                                                        <div>
                                                            <div className="text-gray-600">Jatuh Tempo</div>
                                                            <div className="font-medium">{new Date(loan.due_date).toLocaleDateString('id-ID')}</div>
                                                        </div>
                                                    </div>
                                                    <div className="mt-2 text-sm text-gray-600">
                                                        Telepon: {loan.phone_number}
                                                    </div>
                                                </div>
                                                <button
                                                    onClick={() => {
                                                        setSelectedLoan(loan);
                                                        setShowContactModal(true);
                                                    }}
                                                    className="ml-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                                                >
                                                    Hubungi
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    {/* Info Box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <h4 className="font-medium text-blue-900 mb-2">ℹ️ Panduan Penagihan</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Hubungi nasabah dengan sopan dan profesional</li>
                            <li>• Catat setiap komunikasi yang dilakukan</li>
                            <li>• Tawarkan solusi pembayaran yang realistis</li>
                            <li>• Eskalasi ke supervisor jika diperlukan</li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Contact Modal */}
            {showContactModal && selectedLoan && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div className="flex justify-between items-center p-6 border-b">
                            <h2 className="text-xl font-bold">Catatan Kontak</h2>
                            <button
                                onClick={() => setShowContactModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleContact} className="p-6">
                            <div className="bg-gray-50 rounded-lg p-4 mb-4">
                                <div className="font-semibold text-gray-900 mb-1">{selectedLoan.customer_name}</div>
                                <div className="text-sm text-gray-600">
                                    Tunggakan: {formatCurrency(selectedLoan.overdue_amount)}
                                </div>
                            </div>

                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Hasil Kontak
                                </label>
                                <textarea
                                    value={contactNote}
                                    onChange={(e) => setContactNote(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    rows="4"
                                    placeholder="Catat hasil komunikasi dengan nasabah..."
                                    required
                                />
                            </div>

                            <div className="flex gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowContactModal(false)}
                                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                    disabled={processing}
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                                    disabled={processing}
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
