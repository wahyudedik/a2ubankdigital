import React, { useState, useEffect } from 'react';
import CustomerLayout from '../Layouts/CustomerLayout';
import { router } from '@inertiajs/react';
import { MessageSquare, Plus, Clock, CheckCircle, XCircle } from 'lucide-react';

export default function TicketsPage() {
    const [tickets, setTickets] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [formData, setFormData] = useState({
        subject: '',
        category: 'GENERAL',
        priority: 'MEDIUM',
        message: ''
    });

    useEffect(() => {
        fetchTickets();
    }, []);

    const fetchTickets = async () => {
        try {
            const response = await fetch('/ajax/user/tickets');
            const data = await response.json();
            if (data.status === 'success') {
                setTickets(data.data);
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
            const response = await fetch('/ajax/user/tickets', {
                method: 'POST',
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
                setFormData({
                    subject: '',
                    category: 'GENERAL',
                    priority: 'MEDIUM',
                    message: ''
                });
                fetchTickets();
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

    const handleTicketClick = (ticketId) => {
        router.visit(`/tickets/${ticketId}`);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getStatusBadge = (status) => {
        const badges = {
            'OPEN': { color: 'bg-blue-100 text-blue-800', icon: Clock, label: 'Terbuka' },
            'IN_PROGRESS': { color: 'bg-yellow-100 text-yellow-800', icon: Clock, label: 'Diproses' },
            'RESOLVED': { color: 'bg-green-100 text-green-800', icon: CheckCircle, label: 'Selesai' },
            'CLOSED': { color: 'bg-gray-100 text-gray-800', icon: XCircle, label: 'Ditutup' }
        };
        return badges[status] || badges['OPEN'];
    };

    const getPriorityBadge = (priority) => {
        const badges = {
            'LOW': { color: 'bg-gray-100 text-gray-800', label: 'Rendah' },
            'MEDIUM': { color: 'bg-blue-100 text-blue-800', label: 'Sedang' },
            'HIGH': { color: 'bg-orange-100 text-orange-800', label: 'Tinggi' },
            'URGENT': { color: 'bg-red-100 text-red-800', label: 'Mendesak' }
        };
        return badges[priority] || badges['MEDIUM'];
    };

    const getCategoryLabel = (category) => {
        const labels = {
            'GENERAL': 'Umum',
            'ACCOUNT': 'Akun',
            'TRANSACTION': 'Transaksi',
            'LOAN': 'Pinjaman',
            'CARD': 'Kartu',
            'TECHNICAL': 'Teknis',
            'COMPLAINT': 'Keluhan',
            'OTHER': 'Lainnya'
        };
        return labels[category] || category;
    };

    return (
        <CustomerLayout>
            <div className="p-6">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-800">Tiket Dukungan</h1>
                        <p className="text-gray-600 mt-1">Kelola pertanyaan dan keluhan Anda</p>
                    </div>
                    <button
                        onClick={() => setShowModal(true)}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700"
                    >
                        <Plus size={20} />
                        Buat Tiket Baru
                    </button>
                </div>

                {loading ? (
                    <div className="text-center py-12">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="text-gray-600 mt-4">Memuat data...</p>
                    </div>
                ) : tickets.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-12 text-center">
                        <MessageSquare size={64} className="mx-auto text-gray-400 mb-4" />
                        <h3 className="text-xl font-semibold text-gray-800 mb-2">Belum Ada Tiket</h3>
                        <p className="text-gray-600 mb-6">Buat tiket dukungan untuk mendapatkan bantuan dari tim kami</p>
                        <button
                            onClick={() => setShowModal(true)}
                            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
                        >
                            Buat Tiket Sekarang
                        </button>
                    </div>
                ) : (
                    <div className="grid gap-4">
                        {tickets.map((ticket) => {
                            const statusBadge = getStatusBadge(ticket.status);
                            const priorityBadge = getPriorityBadge(ticket.priority);
                            const StatusIcon = statusBadge.icon;

                            return (
                                <div
                                    key={ticket.id}
                                    onClick={() => handleTicketClick(ticket.id)}
                                    className="bg-white rounded-lg shadow p-6 cursor-pointer hover:shadow-md transition-shadow"
                                >
                                    <div className="flex justify-between items-start mb-3">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-2">
                                                <h3 className="text-lg font-semibold text-gray-800">
                                                    #{ticket.ticket_number}
                                                </h3>
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusBadge.color} flex items-center gap-1`}>
                                                    <StatusIcon size={14} />
                                                    {statusBadge.label}
                                                </span>
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${priorityBadge.color}`}>
                                                    {priorityBadge.label}
                                                </span>
                                            </div>
                                            <h4 className="text-base font-medium text-gray-900 mb-1">
                                                {ticket.subject}
                                            </h4>
                                            <p className="text-sm text-gray-600 line-clamp-2 mb-2">
                                                {ticket.message}
                                            </p>
                                            <div className="flex items-center gap-4 text-xs text-gray-500">
                                                <span>Kategori: {getCategoryLabel(ticket.category)}</span>
                                                <span>•</span>
                                                <span>{formatDate(ticket.created_at)}</span>
                                                {ticket.assigned_to_name && (
                                                    <>
                                                        <span>•</span>
                                                        <span>Ditangani: {ticket.assigned_to_name}</span>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Modal */}
                {showModal && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                        <div className="bg-white rounded-lg max-w-md w-full p-6">
                            <h2 className="text-xl font-bold mb-4">Buat Tiket Dukungan</h2>
                            <form onSubmit={handleSubmit}>
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Subjek
                                        </label>
                                        <input
                                            type="text"
                                            value={formData.subject}
                                            onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            required
                                            maxLength="255"
                                            placeholder="Ringkasan masalah Anda"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Kategori
                                        </label>
                                        <select
                                            value={formData.category}
                                            onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            required
                                        >
                                            <option value="GENERAL">Umum</option>
                                            <option value="ACCOUNT">Akun</option>
                                            <option value="TRANSACTION">Transaksi</option>
                                            <option value="LOAN">Pinjaman</option>
                                            <option value="CARD">Kartu</option>
                                            <option value="TECHNICAL">Teknis</option>
                                            <option value="COMPLAINT">Keluhan</option>
                                            <option value="OTHER">Lainnya</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Prioritas
                                        </label>
                                        <select
                                            value={formData.priority}
                                            onChange={(e) => setFormData({ ...formData, priority: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            required
                                        >
                                            <option value="LOW">Rendah</option>
                                            <option value="MEDIUM">Sedang</option>
                                            <option value="HIGH">Tinggi</option>
                                            <option value="URGENT">Mendesak</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">
                                            Pesan
                                        </label>
                                        <textarea
                                            value={formData.message}
                                            onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg px-3 py-2"
                                            rows="5"
                                            required
                                            placeholder="Jelaskan masalah Anda secara detail..."
                                        />
                                    </div>
                                </div>
                                <div className="flex gap-3 mt-6">
                                    <button
                                        type="button"
                                        onClick={() => setShowModal(false)}
                                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        {loading ? 'Mengirim...' : 'Kirim Tiket'}
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
