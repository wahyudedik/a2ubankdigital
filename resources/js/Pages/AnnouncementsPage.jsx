import React, { useState, useEffect } from 'react';
import CustomerLayout from '../Layouts/CustomerLayout';
import { Bell, AlertCircle, Info, Megaphone, Shield, Wrench } from 'lucide-react';

export default function AnnouncementsPage() {
    const [announcements, setAnnouncements] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('ALL');

    useEffect(() => {
        fetchAnnouncements();
    }, []);

    const fetchAnnouncements = async () => {
        try {
            const response = await fetch('/ajax/user/announcements');
            const data = await response.json();
            if (data.status === 'success') {
                setAnnouncements(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const getTypeIcon = (type) => {
        const icons = {
            'INFO': Info,
            'PROMO': Megaphone,
            'MAINTENANCE': Wrench,
            'UPDATE': Bell,
            'SECURITY': Shield
        };
        return icons[type] || Info;
    };

    const getTypeBadge = (type) => {
        const badges = {
            'INFO': { color: 'bg-blue-100 text-blue-800', label: 'Info' },
            'PROMO': { color: 'bg-green-100 text-green-800', label: 'Promo' },
            'MAINTENANCE': { color: 'bg-orange-100 text-orange-800', label: 'Maintenance' },
            'UPDATE': { color: 'bg-purple-100 text-purple-800', label: 'Update' },
            'SECURITY': { color: 'bg-red-100 text-red-800', label: 'Keamanan' }
        };
        return badges[type] || badges['INFO'];
    };

    const getPriorityBadge = (priority) => {
        const badges = {
            'LOW': { color: 'bg-gray-100 text-gray-800', label: 'Rendah' },
            'NORMAL': { color: 'bg-blue-100 text-blue-800', label: 'Normal' },
            'HIGH': { color: 'bg-red-100 text-red-800', label: 'Penting' }
        };
        return badges[priority] || badges['NORMAL'];
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    };

    const filteredAnnouncements = filter === 'ALL'
        ? announcements
        : announcements.filter(a => a.type === filter);

    return (
        <CustomerLayout>
            <div className="p-6 max-w-4xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">Pengumuman</h1>
                    <p className="text-gray-600 mt-1">Informasi terbaru dari A2U Bank Digital</p>
                </div>

                {/* Filter */}
                <div className="bg-white rounded-lg shadow p-4 mb-6">
                    <div className="flex flex-wrap gap-2">
                        <button
                            onClick={() => setFilter('ALL')}
                            className={`px-4 py-2 rounded-lg ${filter === 'ALL'
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            Semua
                        </button>
                        <button
                            onClick={() => setFilter('PROMO')}
                            className={`px-4 py-2 rounded-lg ${filter === 'PROMO'
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            Promo
                        </button>
                        <button
                            onClick={() => setFilter('UPDATE')}
                            className={`px-4 py-2 rounded-lg ${filter === 'UPDATE'
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            Update
                        </button>
                        <button
                            onClick={() => setFilter('MAINTENANCE')}
                            className={`px-4 py-2 rounded-lg ${filter === 'MAINTENANCE'
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            Maintenance
                        </button>
                        <button
                            onClick={() => setFilter('SECURITY')}
                            className={`px-4 py-2 rounded-lg ${filter === 'SECURITY'
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                }`}
                        >
                            Keamanan
                        </button>
                    </div>
                </div>

                {loading ? (
                    <div className="text-center py-12">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="text-gray-600 mt-4">Memuat pengumuman...</p>
                    </div>
                ) : filteredAnnouncements.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-12 text-center">
                        <Bell size={64} className="mx-auto text-gray-400 mb-4" />
                        <h3 className="text-xl font-semibold text-gray-800 mb-2">Tidak Ada Pengumuman</h3>
                        <p className="text-gray-600">Belum ada pengumuman untuk kategori ini</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {filteredAnnouncements.map((announcement) => {
                            const typeBadge = getTypeBadge(announcement.type);
                            const priorityBadge = getPriorityBadge(announcement.priority);
                            const TypeIcon = getTypeIcon(announcement.type);

                            return (
                                <div key={announcement.id} className="bg-white rounded-lg shadow p-6">
                                    <div className="flex items-start gap-4">
                                        <div className={`p-3 rounded-lg ${typeBadge.color.replace('text-', 'bg-').replace('800', '200')}`}>
                                            <TypeIcon size={24} className={typeBadge.color.split(' ')[1]} />
                                        </div>
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-2">
                                                <span className={`px-3 py-1 rounded-full text-xs font-medium ${typeBadge.color}`}>
                                                    {typeBadge.label}
                                                </span>
                                                {announcement.priority === 'HIGH' && (
                                                    <span className={`px-3 py-1 rounded-full text-xs font-medium ${priorityBadge.color}`}>
                                                        {priorityBadge.label}
                                                    </span>
                                                )}
                                            </div>
                                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                                {announcement.title}
                                            </h3>
                                            <p className="text-gray-700 mb-3 whitespace-pre-wrap">
                                                {announcement.content}
                                            </p>
                                            <div className="flex items-center gap-4 text-sm text-gray-500">
                                                <span>{formatDate(announcement.created_at)}</span>
                                                {announcement.end_date && (
                                                    <>
                                                        <span>•</span>
                                                        <span>Berlaku hingga: {formatDate(announcement.end_date)}</span>
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
            </div>
        </CustomerLayout>
    );
}
