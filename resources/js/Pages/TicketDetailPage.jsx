import React, { useState, useEffect } from 'react';
import CustomerLayout from '../Layouts/CustomerLayout';
import { router } from '@inertiajs/react';
import { ArrowLeft, Send, Clock, CheckCircle, XCircle } from 'lucide-react';

export default function TicketDetailPage({ ticketId }) {
    const [ticket, setTicket] = useState(null);
    const [loading, setLoading] = useState(true);
    const [replyMessage, setReplyMessage] = useState('');
    const [sending, setSending] = useState(false);

    useEffect(() => {
        fetchTicketDetail();
    }, [ticketId]);

    const fetchTicketDetail = async () => {
        try {
            const response = await fetch(`/ajax/user/tickets/${ticketId}`);
            const data = await response.json();
            if (data.status === 'success') {
                setTicket(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleReply = async (e) => {
        e.preventDefault();
        if (!replyMessage.trim()) return;

        setSending(true);
        try {
            const response = await fetch(`/ajax/user/tickets/${ticketId}/reply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ message: replyMessage })
            });

            const data = await response.json();

            if (data.status === 'success') {
                setReplyMessage('');
                fetchTicketDetail();
            } else {
                alert(data.message || 'Gagal mengirim balasan');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        } finally {
            setSending(false);
        }
    };

    const handleCloseTicket = async () => {
        if (!confirm('Apakah Anda yakin ingin menutup tiket ini?')) return;

        try {
            const response = await fetch(`/ajax/user/tickets/${ticketId}/close`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                fetchTicketDetail();
            } else {
                alert(data.message || 'Gagal menutup tiket');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Terjadi kesalahan');
        }
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
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

    if (loading) {
        return (
            <CustomerLayout>
                <div className="p-6">
                    <div className="text-center py-12">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="text-gray-600 mt-4">Memuat detail tiket...</p>
                    </div>
                </div>
            </CustomerLayout>
        );
    }

    if (!ticket) {
        return (
            <CustomerLayout>
                <div className="p-6">
                    <div className="text-center py-12">
                        <p className="text-gray-600">Tiket tidak ditemukan</p>
                        <button
                            onClick={() => router.visit('/tickets')}
                            className="mt-4 text-blue-600 hover:underline"
                        >
                            Kembali ke Daftar Tiket
                        </button>
                    </div>
                </div>
            </CustomerLayout>
        );
    }

    const statusBadge = getStatusBadge(ticket.status);
    const priorityBadge = getPriorityBadge(ticket.priority);
    const StatusIcon = statusBadge.icon;
    const canReply = ticket.status !== 'CLOSED' && ticket.status !== 'RESOLVED';

    return (
        <CustomerLayout>
            <div className="p-6">
                <button
                    onClick={() => router.visit('/tickets')}
                    className="flex items-center gap-2 text-gray-600 hover:text-gray-800 mb-6"
                >
                    <ArrowLeft size={20} />
                    Kembali ke Daftar Tiket
                </button>

                <div className="bg-white rounded-lg shadow p-6 mb-6">
                    <div className="flex justify-between items-start mb-4">
                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <h1 className="text-2xl font-bold text-gray-800">
                                    #{ticket.ticket_number}
                                </h1>
                                <span className={`px-3 py-1 rounded-full text-xs font-medium ${statusBadge.color} flex items-center gap-1`}>
                                    <StatusIcon size={14} />
                                    {statusBadge.label}
                                </span>
                                <span className={`px-3 py-1 rounded-full text-xs font-medium ${priorityBadge.color}`}>
                                    {priorityBadge.label}
                                </span>
                            </div>
                            <h2 className="text-xl font-semibold text-gray-900 mb-2">
                                {ticket.subject}
                            </h2>
                            <div className="flex items-center gap-4 text-sm text-gray-600">
                                <span>Dibuat: {formatDate(ticket.created_at)}</span>
                                {ticket.assigned_to_name && (
                                    <>
                                        <span>•</span>
                                        <span>Ditangani oleh: {ticket.assigned_to_name}</span>
                                    </>
                                )}
                            </div>
                        </div>
                        {canReply && (
                            <button
                                onClick={handleCloseTicket}
                                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm"
                            >
                                Tutup Tiket
                            </button>
                        )}
                    </div>
                </div>

                {/* Messages */}
                <div className="space-y-4 mb-6">
                    {ticket.replies && ticket.replies.map((reply, index) => (
                        <div
                            key={reply.id || index}
                            className={`bg-white rounded-lg shadow p-6 ${reply.is_staff ? 'border-l-4 border-blue-500' : ''
                                }`}
                        >
                            <div className="flex justify-between items-start mb-3">
                                <div>
                                    <p className="font-semibold text-gray-900">
                                        {reply.sender_name}
                                        {reply.is_staff && (
                                            <span className="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                Staff
                                            </span>
                                        )}
                                    </p>
                                    <p className="text-sm text-gray-500">{formatDate(reply.created_at)}</p>
                                </div>
                            </div>
                            <p className="text-gray-700 whitespace-pre-wrap">{reply.message}</p>
                        </div>
                    ))}
                </div>

                {/* Reply Form */}
                {canReply && (
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Balas Tiket</h3>
                        <form onSubmit={handleReply}>
                            <textarea
                                value={replyMessage}
                                onChange={(e) => setReplyMessage(e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4"
                                rows="4"
                                placeholder="Tulis balasan Anda..."
                                required
                            />
                            <button
                                type="submit"
                                disabled={sending || !replyMessage.trim()}
                                className="bg-blue-600 text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700 disabled:opacity-50"
                            >
                                <Send size={18} />
                                {sending ? 'Mengirim...' : 'Kirim Balasan'}
                            </button>
                        </form>
                    </div>
                )}

                {!canReply && (
                    <div className="bg-gray-50 rounded-lg p-6 text-center">
                        <p className="text-gray-600">
                            Tiket ini sudah {ticket.status === 'CLOSED' ? 'ditutup' : 'diselesaikan'}.
                            Anda tidak dapat mengirim balasan lagi.
                        </p>
                    </div>
                )}
            </div>
        </CustomerLayout>
    );
}
