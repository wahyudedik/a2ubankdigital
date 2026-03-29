import React, { useState, useEffect } from 'react';
import CustomerLayout from '../Layouts/CustomerLayout';
import { router } from '@inertiajs/react';
import { Calendar, Plus, Edit2, Trash2, Pause, Play } from 'lucide-react';

export default function ScheduledTransfersPage() {
    const [transfers, setTransfers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingTransfer, setEditingTransfer] = useState(null);
    const [formData, setFormData] = useState({
        to_account_number: '',
        amount: '',
        frequency: 'MONTHLY',
        start_date: '',
        end_date: '',
        description: ''
    });

    useEffect(() => {
        fetchTransfers();
    }, []);

    const fetchTransfers = async () => {
        try {
            const response = await fetch('/ajax/user/scheduled-transfers');
            const data = await response.json();
            if (data.status === 'success') {
                setTransfers(data.data);
            }
        } catch (error) {
            console.error('Error fetching transfers:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            const url = editingTransfer
                ? `/ajax/user/scheduled-transfers/${editingTransfer.id}`
                : '/ajax/user/scheduled-transfers';

            const method = editingTransfer ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                setShowModal(false);
                setEditingTransfer(null);
                setFormData({
                    to_account_number: '',
                    amount: '',
                    frequency: 'MONTHLY',
                    start_date: '',
                    end_date: '',
                    description: ''
                });
                fetchTransfers();
            } else {
                alert(data.message || 'Terjadi kesalahan');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan data');
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = (transfer) => {
        setEditingTransfer(transfer);
        setFormData({
            to_account_number: transfer.to_account_number,
            amount: transfer.amount,
            frequency: transfer.frequency,
            start_date: transfer.next_execution_date,
            end_date: transfer.end_date || '',
            description: transfer.description
        });
        setShowModal(true);
    };

    const handleDelete = async (id) => {
        if (!confirm('Apakah Anda yakin ingin menghapus transfer terjadwal ini?')) return;

        try {
            const response = await fetch(`/ajax/user/scheduled-transfers/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                fetchTransfers();
            } else {
                alert(data.message || 'Gagal menghapus');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        }
    };

    const handleToggleStatus = async (transfer) => {
        const newStatus = transfer.status === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';

        try {
            const response = await fetch(`/ajax/user/scheduled-transfers/${transfer.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: newStatus })
            });

            const data = await response.json();

            if (data.status === 'success') {
                fetchTransfers();
            } else {
                alert(data.message || 'Gagal mengubah status');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const getFrequencyLabel = (frequency) => {
        const labels = {
            'DAILY': 'Harian',
            'WEEKLY': 'Mingguan',
            'MONTHLY': 'Bulanan'
        };
        return labels[frequency] || frequency;
    };

    return (
        <CustomerLayout>
            <div className="p-6">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Transfer Terjadwal</h1>
                        <p className="text-gray-600 mt-1">Kelola transfer otomatis Anda</p>
                    </div>
                    <button
                        onClick={() => {
                            setEditingTransfer(null);
                            setFormData({
                                to_account_number: '',
                                amount: '',
                                frequency: 'MONTHLY',
                                start_date: '',
                                end_date: '',
                                description: ''
                            });
                            setShowModal(true);
                        }}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700"
                    >
                        <Plus size={20} />
                        Buat Transfer Terjadwal
                    </button>
                </div>

                {loading ? (
                    <div className="text-center py-12">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="text-gray-600 mt-4">Memuat data...</p>
                    </div>
                ) : transfers.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-12 text-center">
                        <Calendar size={64} className="mx-auto text-gray-400 mb-4" />
                        <h3 className="text-xl font-semibold text-gray-800 mb-2">Belum Ada Transfer Terjadwal</h3>
                        <p className="text-gray-600 mb-6">Buat transfer terjadwal untuk otomatis mengirim uang secara berkala</p>
                        <button
                            onClick={() => setShowModal(true)}
                            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
                        >
                            Buat Sekarang
                        </button>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        {transfers.map((transfer) => (
                            <div key={transfer.id} className="bg-white rounded-lg shadow p-6">
                                <div className="flex justify-between items-start">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-3">
                                            <h3 className="text-lg font-semibold text-gray-800">
                                                {transfer.recipient_name}
                                            </h3>
                                            <span className={`px-3 py-1 rounded-full text-xs font-medium ${transfer.status === 'ACTIVE'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                {transfer.status === 'ACTIVE' ? 'Aktif' : 'Dijeda'}
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <p className="text-gray-600">Rekening Tujuan</p>
                                                <p className="font-medium">{transfer.to_account_number}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Jumlah</p>
                                                <p className="font-medium text-blue-600">{formatCurrency(transfer.amount)}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Frekuensi</p>
                                                <p className="font-medium">{getFrequencyLabel(transfer.frequency)}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Eksekusi Berikutnya</p>
                                                <p className="font-medium">{formatDate(transfer.next_execution_date)}</p>
                                            </div>
                                            {transfer.end_date && (
                                                <div>
                                                    <p className="text-gray-600">Berakhir</p>
                                                    <p className="font-medium">{formatDate(transfer.end_date)}</p>
                                                </div>
                                            )}
                                            {transfer.description && (
                                                <div className="col-span-2">
                                                    <p className="text-gray-600">Keterangan</p>
                                                    <p className="font-medium">{transfer.description}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex gap-2 ml-4">
                                        <button
                                            onClick={() => handleToggleStatus(transfer)}
                                            className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                                            title={transfer.status === 'ACTIVE' ? 'Jeda' : 'Aktifkan'}
                                        >
                                            {transfer.status === 'ACTIVE' ? <Pause size={20} /> : <Play size={20} />}
                                        </button>
                                        <button
                                            onClick={() => handleEdit(transfer)}
                                            className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg"
                                            title="Edit"
                                        >
                                            <Edit2 size={20} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(transfer.id)}
                                            className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                            title="Hapus"
                                        >
                                            <Trash2 size={20} />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Modal */}
                {showModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-lg max-w-md w-full p-6">
                            <h2 className="text-xl font-bold mb-4">
                                {editingTransfer ? 'Edit Transfer Terjadwal' : 'Buat Transfer Terjadwal'}
                            </h2>
                            <form onSubmit={handleSubmit}>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Nomor Rekening Tujuan
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.to_account_number}
                                            onChange={(e) => setFormData({ ...formData, to_account_number: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            required
                                            disabled={editingTransfer}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Jumlah (Rp)
                                        </label>
                                        <input
                                            type="number"
                                            value={formData.amount}
                                            onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            min="10000"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Frekuensi
                                        </label>
                                        <select
                                            value={formData.frequency}
                                            onChange={(e) => setFormData({ ...formData, frequency: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            required
                                        >
                                            <option value="DAILY">Harian</option>
                                            <option value="WEEKLY">Mingguan</option>
                                            <option value="MONTHLY">Bulanan</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Tanggal Mulai
                                        </label>
                                        <input
                                            type="date"
                                            value={formData.start_date}
                                            onChange={(e) => setFormData({ ...formData, start_date: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            required
                                            min={new Date().toISOString().split('T')[0]}
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Tanggal Berakhir (Opsional)
                                        </label>
                                        <input
                                            type="date"
                                            value={formData.end_date}
                                            onChange={(e) => setFormData({ ...formData, end_date: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                        />
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
                                            maxLength="255"
                                        />
                                    </div>
                                </div>
                                <div className="flex gap-3 mt-6">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowModal(false);
                                            setEditingTransfer(null);
                                        }}
                                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {loading ? 'Menyimpan...' : 'Simpan'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
}
