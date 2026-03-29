import React, { useState, useEffect } from 'react';
import CustomerLayout from '../Layouts/CustomerLayout';
import { Repeat, Plus, Edit2, Trash2, Pause, Play } from 'lucide-react';

export default function StandingInstructionsPage() {
    const [instructions, setInstructions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingInstruction, setEditingInstruction] = useState(null);
    const [formData, setFormData] = useState({
        to_account_number: '',
        amount: '',
        instruction_type: 'MONTHLY',
        execution_day: '1',
        start_date: '',
        end_date: '',
        description: ''
    });

    useEffect(() => {
        fetchInstructions();
    }, []);

    const fetchInstructions = async () => {
        try {
            const response = await fetch('/ajax/user/standing-instructions');
            const data = await response.json();
            if (data.status === 'success') {
                setInstructions(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            const url = editingInstruction
                ? `/ajax/user/standing-instructions/${editingInstruction.id}`
                : '/ajax/user/standing-instructions';

            const method = editingInstruction ? 'PUT' : 'POST';

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
                setEditingInstruction(null);
                setFormData({
                    to_account_number: '',
                    amount: '',
                    instruction_type: 'MONTHLY',
                    execution_day: '1',
                    start_date: '',
                    end_date: '',
                    description: ''
                });
                fetchInstructions();
            } else {
                alert(data.message || 'Terjadi kesalahan');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        } finally {
            setLoading(false);
        }
    };

    const handleEdit = (instruction) => {
        setEditingInstruction(instruction);
        setFormData({
            to_account_number: instruction.to_account_number,
            amount: instruction.amount,
            instruction_type: instruction.instruction_type,
            execution_day: instruction.execution_day,
            start_date: instruction.start_date,
            end_date: instruction.end_date || '',
            description: instruction.description
        });
        setShowModal(true);
    };

    const handleDelete = async (id) => {
        if (!confirm('Apakah Anda yakin ingin menghapus standing instruction ini?')) return;

        try {
            const response = await fetch(`/ajax/user/standing-instructions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                fetchInstructions();
            } else {
                alert(data.message || 'Gagal menghapus');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        }
    };

    const handleToggleStatus = async (instruction) => {
        const newStatus = instruction.status === 'ACTIVE' ? 'PAUSED' : 'ACTIVE';

        try {
            const response = await fetch(`/ajax/user/standing-instructions/${instruction.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ status: newStatus })
            });

            const data = await response.json();

            if (data.status === 'success') {
                fetchInstructions();
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

    const getTypeLabel = (type) => {
        return type === 'MONTHLY' ? 'Bulanan' : 'Tanggal Tertentu';
    };

    return (
        <CustomerLayout>
            <div className="p-6">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Standing Instructions</h1>
                        <p className="text-gray-600 mt-1">Auto-debit rutin untuk pembayaran berkala</p>
                    </div>
                    <button
                        onClick={() => {
                            setEditingInstruction(null);
                            setFormData({
                                to_account_number: '',
                                amount: '',
                                instruction_type: 'MONTHLY',
                                execution_day: '1',
                                start_date: '',
                                end_date: '',
                                description: ''
                            });
                            setShowModal(true);
                        }}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700"
                    >
                        <Plus size={20} />
                        Buat Standing Instruction
                    </button>
                </div>

                {loading ? (
                    <div className="text-center py-12">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="text-gray-600 mt-4">Memuat data...</p>
                    </div>
                ) : instructions.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-12 text-center">
                        <Repeat size={64} className="mx-auto text-gray-400 mb-4" />
                        <h3 className="text-xl font-semibold text-gray-800 mb-2">Belum Ada Standing Instruction</h3>
                        <p className="text-gray-600 mb-6">Buat standing instruction untuk auto-debit pembayaran rutin</p>
                        <button
                            onClick={() => setShowModal(true)}
                            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
                        >
                            Buat Sekarang
                        </button>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        {instructions.map((instruction) => (
                            <div key={instruction.id} className="bg-white rounded-lg shadow p-6">
                                <div className="flex justify-between items-start">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-3">
                                            <h3 className="text-lg font-semibold text-gray-800">
                                                {instruction.recipient_name}
                                            </h3>
                                            <span className={`px-3 py-1 rounded-full text-xs font-medium ${instruction.status === 'ACTIVE'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                {instruction.status === 'ACTIVE' ? 'Aktif' : 'Dijeda'}
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <p className="text-gray-600">Rekening Tujuan</p>
                                                <p className="font-medium">{instruction.to_account_number}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Jumlah</p>
                                                <p className="font-medium text-blue-600">{formatCurrency(instruction.amount)}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Tipe</p>
                                                <p className="font-medium">{getTypeLabel(instruction.instruction_type)}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Tanggal Eksekusi</p>
                                                <p className="font-medium">Setiap tanggal {instruction.execution_day}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Mulai</p>
                                                <p className="font-medium">{formatDate(instruction.start_date)}</p>
                                            </div>
                                            {instruction.end_date && (
                                                <div>
                                                    <p className="text-gray-600">Berakhir</p>
                                                    <p className="font-medium">{formatDate(instruction.end_date)}</p>
                                                </div>
                                            )}
                                            {instruction.description && (
                                                <div className="col-span-2">
                                                    <p className="text-gray-600">Keterangan</p>
                                                    <p className="font-medium">{instruction.description}</p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex gap-2 ml-4">
                                        <button
                                            onClick={() => handleToggleStatus(instruction)}
                                            className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                                            title={instruction.status === 'ACTIVE' ? 'Jeda' : 'Aktifkan'}
                                        >
                                            {instruction.status === 'ACTIVE' ? <Pause size={20} /> : <Play size={20} />}
                                        </button>
                                        <button
                                            onClick={() => handleEdit(instruction)}
                                            className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg"
                                            title="Edit"
                                        >
                                            <Edit2 size={20} />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(instruction.id)}
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
                                {editingInstruction ? 'Edit Standing Instruction' : 'Buat Standing Instruction'}
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
                                            disabled={editingInstruction}
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
                                            Tipe
                                        </label>
                                        <select
                                            value={formData.instruction_type}
                                            onChange={(e) => setFormData({ ...formData, instruction_type: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            required
                                            disabled={editingInstruction}
                                        >
                                            <option value="MONTHLY">Bulanan</option>
                                            <option value="SPECIFIC_DATE">Tanggal Tertentu</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Tanggal Eksekusi (1-31)
                                        </label>
                                        <input
                                            type="number"
                                            value={formData.execution_day}
                                            onChange={(e) => setFormData({ ...formData, execution_day: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            min="1"
                                            max="31"
                                            required
                                        />
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
                                            disabled={editingInstruction}
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
                                            setEditingInstruction(null);
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
