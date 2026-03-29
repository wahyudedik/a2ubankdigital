import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function SecureMessagesPage({ auth }) {
    const [messages, setMessages] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showCompose, setShowCompose] = useState(false);
    const [selectedThread, setSelectedThread] = useState(null);
    const [threadMessages, setThreadMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [filter, setFilter] = useState('all');
    const [unreadCount, setUnreadCount] = useState(0);

    useEffect(() => {
        fetchMessages();
    }, [filter]);

    const fetchMessages = async () => {
        try {
            setLoading(true);
            const response = await fetch(`/user/messages?status=${filter}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setMessages(data.data.messages);
                setUnreadCount(data.data.summary.unread_count);
            }
        } catch (error) {
            console.error('Failed to fetch messages:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchThread = async (threadId) => {
        try {
            const response = await fetch(`/user/messages/thread?thread_id=${threadId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setThreadMessages(data.data.messages);
                setSelectedThread(threadId);
            }
        } catch (error) {
            console.error('Failed to fetch thread:', error);
        }
    };

    const sendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim()) return;

        try {
            const response = await fetch('/user/messages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({ message: newMessage })
            });
            const data = await response.json();
            if (data.status === 'success') {
                setNewMessage('');
                setShowCompose(false);
                fetchMessages();
                alert('Pesan berhasil dikirim!');
            } else {
                alert(data.message || 'Gagal mengirim pesan');
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            alert('Gagal mengirim pesan');
        }
    };

    const markAsRead = async (messageId) => {
        try {
            await fetch(`/user/messages/${messageId}/read`, {
                method: 'PUT',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                }
            });
            fetchMessages();
        } catch (error) {
            console.error('Failed to mark as read:', error);
        }
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));

        if (days === 0) {
            return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        } else if (days === 1) {
            return 'Kemarin';
        } else if (days < 7) {
            return `${days} hari lalu`;
        } else {
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Pesan Aman" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Pesan Aman</h1>
                            <p className="text-sm text-gray-600 mt-1">Komunikasi aman dengan tim kami</p>
                        </div>
                        <button
                            onClick={() => setShowCompose(true)}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            + Pesan Baru
                        </button>
                    </div>

                    {/* Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div className="bg-white p-4 rounded-lg shadow">
                            <div className="text-sm text-gray-600">Total Pesan</div>
                            <div className="text-2xl font-bold text-gray-900">{messages.length}</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow">
                            <div className="text-sm text-gray-600">Belum Dibaca</div>
                            <div className="text-2xl font-bold text-orange-600">{unreadCount}</div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow">
                            <div className="text-sm text-gray-600">Terkirim</div>
                            <div className="text-2xl font-bold text-green-600">
                                {messages.filter(m => !m.is_received).length}
                            </div>
                        </div>
                    </div>

                    {/* Filter */}
                    <div className="bg-white rounded-lg shadow mb-6">
                        <div className="flex border-b">
                            {['all', 'unread', 'received', 'sent'].map(f => (
                                <button
                                    key={f}
                                    onClick={() => setFilter(f)}
                                    className={`px-6 py-3 font-medium transition ${filter === f
                                            ? 'text-blue-600 border-b-2 border-blue-600'
                                            : 'text-gray-600 hover:text-gray-900'
                                        }`}
                                >
                                    {f === 'all' && 'Semua'}
                                    {f === 'unread' && 'Belum Dibaca'}
                                    {f === 'received' && 'Diterima'}
                                    {f === 'sent' && 'Terkirim'}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Messages List */}
                    <div className="bg-white rounded-lg shadow">
                        {loading ? (
                            <div className="p-8 text-center text-gray-500">Memuat pesan...</div>
                        ) : messages.length === 0 ? (
                            <div className="p-8 text-center text-gray-500">
                                <p>Tidak ada pesan</p>
                                <button
                                    onClick={() => setShowCompose(true)}
                                    className="mt-4 text-blue-600 hover:text-blue-700"
                                >
                                    Kirim pesan pertama Anda
                                </button>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {messages.map(message => (
                                    <div
                                        key={message.id}
                                        className={`p-4 hover:bg-gray-50 cursor-pointer transition ${message.is_received && !message.is_read ? 'bg-blue-50' : ''
                                            }`}
                                        onClick={() => {
                                            fetchThread(message.thread_id);
                                            if (message.is_received && !message.is_read) {
                                                markAsRead(message.id);
                                            }
                                        }}
                                    >
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium text-gray-900">
                                                        {message.sender.name}
                                                    </span>
                                                    <span className={`text-xs px-2 py-0.5 rounded ${message.sender.type === 'admin'
                                                            ? 'bg-purple-100 text-purple-700'
                                                            : 'bg-gray-100 text-gray-700'
                                                        }`}>
                                                        {message.sender.type === 'admin' ? 'Admin' : 'Anda'}
                                                    </span>
                                                    {message.is_received && !message.is_read && (
                                                        <span className="w-2 h-2 bg-blue-600 rounded-full"></span>
                                                    )}
                                                </div>
                                                <p className="text-sm text-gray-600 mt-1 line-clamp-2">
                                                    {message.message}
                                                </p>
                                            </div>
                                            <div className="text-xs text-gray-500 ml-4">
                                                {formatDate(message.sent_at)}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Compose Modal */}
            {showCompose && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                        <div className="flex justify-between items-center p-6 border-b">
                            <h2 className="text-xl font-bold">Pesan Baru</h2>
                            <button
                                onClick={() => setShowCompose(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>
                        <form onSubmit={sendMessage} className="p-6">
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Kepada: Tim Customer Service
                                </label>
                            </div>
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Pesan
                                </label>
                                <textarea
                                    value={newMessage}
                                    onChange={(e) => setNewMessage(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    rows="6"
                                    placeholder="Tulis pesan Anda di sini..."
                                    required
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    Maksimal 2000 karakter
                                </p>
                            </div>
                            <div className="flex justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowCompose(false)}
                                    className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                                >
                                    Kirim Pesan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Thread Modal */}
            {selectedThread && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[80vh] flex flex-col">
                        <div className="flex justify-between items-center p-6 border-b">
                            <h2 className="text-xl font-bold">Percakapan</h2>
                            <button
                                onClick={() => setSelectedThread(null)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>
                        <div className="flex-1 overflow-y-auto p-6 space-y-4">
                            {threadMessages.map(msg => (
                                <div
                                    key={msg.id}
                                    className={`flex ${msg.is_mine ? 'justify-end' : 'justify-start'}`}
                                >
                                    <div className={`max-w-[70%] ${msg.is_mine
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-900'
                                        } rounded-lg p-4`}>
                                        <div className="text-xs opacity-75 mb-1">
                                            {msg.sender.name} • {formatDate(msg.sent_at)}
                                        </div>
                                        <p className="text-sm">{msg.message}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
