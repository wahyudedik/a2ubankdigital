import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function AccountClosurePage({ auth }) {
    const [closureRequest, setClosureRequest] = useState(null);
    const [loading, setLoading] = useState(true);
    const [showRequestForm, setShowRequestForm] = useState(false);
    const [reason, setReason] = useState('');
    const [confirmation, setConfirmation] = useState(false);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        fetchStatus();
    }, []);

    const fetchStatus = async () => {
        try {
            const response = await fetch('/user/account-closure/status', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setClosureRequest(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch status:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!confirmation) {
            alert('Mohon centang konfirmasi');
            return;
        }

        setProcessing(true);
        try {
            const response = await fetch('/user/account-closure/request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({ reason, confirmation: confirmation ? '1' : '0' })
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                setShowRequestForm(false);
                fetchStatus();
            } else {
                alert(data.message || 'Gagal mengajukan penutupan akun');
            }
        } catch (error) {
            console.error('Request failed:', error);
            alert('Terjadi kesalahan');
        } finally {
            setProcessing(false);
        }
    };

    const handleCancel = async () => {
        if (!confirm('Yakin ingin membatalkan permintaan penutupan akun?')) {
            return;
        }

        try {
            const response = await fetch(`/user/account-closure/${closureRequest.id}/cancel`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                }
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                fetchStatus();
            } else {
                alert(data.message || 'Gagal membatalkan permintaan');
            }
        } catch (error) {
            console.error('Cancel failed:', error);
            alert('Terjadi kesalahan');
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            'PENDING': { color: 'bg-yellow-100 text-yellow-800', text: 'Menunggu' },
            'APPROVED': { color: 'bg-green-100 text-green-800', text: 'Disetujui' },
            'REJECTED': { color: 'bg-red-100 text-red-800', text: 'Ditolak' },
            'CANCELLED': { color: 'bg-gray-100 text-gray-800', text: 'Dibatalkan' }
        };
        const badge = badges[status] || badges['PENDING'];
        return <span className={`px-3 py-1 rounded-full text-sm font-medium ${badge.color}`}>{badge.text}</span>;
    };

    if (loading) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Penutupan Akun" />
                <div className="py-12 text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Penutupan Akun" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Penutupan Akun</h1>
                        <p className="text-sm text-gray-600 mt-1">Ajukan permintaan penutupan akun bank Anda</p>
                    </div>

                    {/* Warning Box */}
                    <div className="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <span className="text-2xl">⚠️</span>
                            </div>
                            <div className="ml-3">
                                <h3 className="text-sm font-medium text-red-800">Perhatian!</h3>
                                <div className="mt-2 text-sm text-red-700">
                                    <ul className="list-disc list-inside space-y-1">
                                        <li>Penutupan akun bersifat permanen dan tidak dapat dibatalkan setelah disetujui</li>
                                        <li>Semua saldo akan dikembalikan ke rekening yang Anda daftarkan</li>
                                        <li>Pastikan tidak ada pinjaman atau deposito aktif</li>
                                        <li>Proses penutupan memakan waktu 1-3 hari kerja</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Current Request Status */}
                    {closureRequest && closureRequest.status === 'PENDING' && (
                        <div className="bg-white rounded-lg shadow mb-6 p-6">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <h2 className="text-lg font-semibold mb-2">Status Permintaan</h2>
                                    {getStatusBadge(closureRequest.status)}
                                </div>
                                <button
                                    onClick={handleCancel}
                                    className="px-4 py-2 text-sm border border-red-300 text-red-700 rounded-lg hover:bg-red-50"
                                >
                                    Batalkan Permintaan
                                </button>
                            </div>
                            <div className="bg-gray-50 rounded-lg p-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-xs text-gray-600">Tanggal Pengajuan</div>
                                        <div className="font-medium">{new Date(closureRequest.requested_at).toLocaleDateString('id-ID')}</div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-gray-600">Alasan</div>
                                        <div className="font-medium">{closureRequest.reason}</div>
                                    </div>
                                </div>
                            </div>
                            <p className="text-sm text-gray-600 mt-4">
                                Tim kami sedang memproses permintaan Anda. Kami akan menghubungi Anda dalam 1-3 hari kerja.
                            </p>
                        </div>
                    )}

                    {/* Request Form */}
                    {!closureRequest || closureRequest.status !== 'PENDING' ? (
                        !showRequestForm ? (
                            <div className="bg-white rounded-lg shadow p-8 text-center">
                                <div className="text-6xl mb-4">🔒</div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">Tutup Akun Bank Anda</h3>
                                <p className="text-gray-600 mb-6">
                                    Jika Anda yakin ingin menutup akun, silakan ajukan permintaan penutupan
                                </p>
                                <button
                                    onClick={() => setShowRequestForm(true)}
                                    className="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium"
                                >
                                    Ajukan Penutupan Akun
                                </button>
                            </div>
                        ) : (
                            <div className="bg-white rounded-lg shadow p-6">
                                <h2 className="text-lg font-semibold mb-4">Form Penutupan Akun</h2>
                                <form onSubmit={handleSubmit}>
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Alasan Penutupan Akun
                                        </label>
                                        <textarea
                                            value={reason}
                                            onChange={(e) => setReason(e.target.value)}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            rows="4"
                                            placeholder="Mohon jelaskan alasan Anda ingin menutup akun..."
                                            required
                                        />
                                    </div>

                                    <div className="mb-6">
                                        <label className="flex items-start">
                                            <input
                                                type="checkbox"
                                                checked={confirmation}
                                                onChange={(e) => setConfirmation(e.target.checked)}
                                                className="mt-1 w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                                required
                                            />
                                            <span className="ml-2 text-sm text-gray-700">
                                                Saya memahami bahwa penutupan akun bersifat permanen dan tidak dapat dibatalkan setelah disetujui.
                                                Saya juga memastikan bahwa tidak ada pinjaman atau deposito aktif yang masih berjalan.
                                            </span>
                                        </label>
                                    </div>

                                    <div className="flex gap-3">
                                        <button
                                            type="button"
                                            onClick={() => setShowRequestForm(false)}
                                            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                            disabled={processing}
                                        >
                                            Batal
                                        </button>
                                        <button
                                            type="submit"
                                            className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400"
                                            disabled={processing}
                                        >
                                            {processing ? 'Memproses...' : 'Ajukan Penutupan'}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        )
                    ) : null}

                    {/* Info Box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <h4 className="font-medium text-blue-900 mb-2">ℹ️ Informasi Penting</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Pastikan semua transaksi telah selesai</li>
                            <li>• Lunasi semua pinjaman yang masih aktif</li>
                            <li>• Cairkan semua deposito sebelum mengajukan penutupan</li>
                            <li>• Saldo rekening akan dikembalikan sesuai prosedur</li>
                            <li>• Hubungi customer service jika ada pertanyaan</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
