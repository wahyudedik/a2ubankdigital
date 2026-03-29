import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function MarketingDashboardPage({ auth }) {
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [period, setPeriod] = useState('month'); // month, quarter, year

    useEffect(() => {
        fetchStats();
    }, [period]);

    const fetchStats = async () => {
        try {
            // In production, this would fetch from API
            // For now, using mock data
            setStats({
                new_customers: 45,
                active_customers: 1250,
                conversion_rate: 12.5,
                total_deposits: 15000000000,
                total_loans: 8500000000,
                customer_growth: 8.3,
                campaigns: [
                    { id: 1, name: 'Promo Deposito 7%', status: 'active', leads: 120, conversions: 15 },
                    { id: 2, name: 'Cashback Transfer', status: 'active', leads: 85, conversions: 42 },
                    { id: 3, name: 'Referral Program', status: 'active', leads: 65, conversions: 8 }
                ],
                top_products: [
                    { name: 'Tabungan Reguler', customers: 850, growth: 5.2 },
                    { name: 'Deposito 12 Bulan', customers: 320, growth: 12.8 },
                    { name: 'Pinjaman Mikro', customers: 180, growth: -2.1 }
                ]
            });
        } catch (error) {
            console.error('Failed to fetch stats:', error);
        } finally {
            setLoading(false);
        }
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    };

    const formatCompact = (value) => {
        if (value >= 1000000000) return (value / 1000000000).toFixed(1) + ' M';
        if (value >= 1000000) return (value / 1000000).toFixed(1) + ' Jt';
        return value.toLocaleString('id-ID');
    };

    if (loading) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Marketing Dashboard" />
                <div className="py-12 text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Marketing Dashboard" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Marketing Dashboard</h1>
                            <p className="text-sm text-gray-600 mt-1">Pantau performa marketing dan akuisisi nasabah</p>
                        </div>
                        <select
                            value={period}
                            onChange={(e) => setPeriod(e.target.value)}
                            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="month">Bulan Ini</option>
                            <option value="quarter">Kuartal Ini</option>
                            <option value="year">Tahun Ini</option>
                        </select>
                    </div>

                    {/* Key Metrics */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                            <div className="text-sm opacity-90 mb-1">Nasabah Baru</div>
                            <div className="text-3xl font-bold">{stats.new_customers}</div>
                            <div className="text-xs opacity-75 mt-2">
                                ↑ {stats.customer_growth}% dari periode sebelumnya
                            </div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-sm text-gray-600 mb-1">Nasabah Aktif</div>
                            <div className="text-3xl font-bold text-gray-900">{stats.active_customers.toLocaleString('id-ID')}</div>
                            <div className="text-xs text-gray-500 mt-2">Total nasabah aktif</div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-sm text-gray-600 mb-1">Conversion Rate</div>
                            <div className="text-3xl font-bold text-green-600">{stats.conversion_rate}%</div>
                            <div className="text-xs text-gray-500 mt-2">Lead to customer</div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-sm text-gray-600 mb-1">Total Deposits</div>
                            <div className="text-2xl font-bold text-gray-900">{formatCompact(stats.total_deposits)}</div>
                            <div className="text-xs text-gray-500 mt-2">Dana pihak ketiga</div>
                        </div>
                    </div>

                    {/* Campaigns Performance */}
                    <div className="bg-white rounded-lg shadow mb-6">
                        <div className="p-6 border-b">
                            <h2 className="text-lg font-semibold">Performa Kampanye</h2>
                        </div>
                        <div className="p-6">
                            <div className="space-y-4">
                                {stats.campaigns.map(campaign => (
                                    <div key={campaign.id} className="border rounded-lg p-4">
                                        <div className="flex justify-between items-start mb-3">
                                            <div>
                                                <h3 className="font-semibold text-gray-900">{campaign.name}</h3>
                                                <span className="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs rounded mt-1">
                                                    {campaign.status === 'active' ? 'Aktif' : 'Selesai'}
                                                </span>
                                            </div>
                                            <div className="text-right">
                                                <div className="text-2xl font-bold text-blue-600">
                                                    {((campaign.conversions / campaign.leads) * 100).toFixed(1)}%
                                                </div>
                                                <div className="text-xs text-gray-500">Conversion</div>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <div className="text-sm text-gray-600">Leads</div>
                                                <div className="text-xl font-semibold">{campaign.leads}</div>
                                            </div>
                                            <div>
                                                <div className="text-sm text-gray-600">Conversions</div>
                                                <div className="text-xl font-semibold text-green-600">{campaign.conversions}</div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Top Products */}
                    <div className="bg-white rounded-lg shadow">
                        <div className="p-6 border-b">
                            <h2 className="text-lg font-semibold">Produk Terpopuler</h2>
                        </div>
                        <div className="p-6">
                            <div className="space-y-4">
                                {stats.top_products.map((product, index) => (
                                    <div key={index} className="flex items-center justify-between p-4 border rounded-lg">
                                        <div className="flex items-center gap-4">
                                            <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center font-bold text-blue-600">
                                                {index + 1}
                                            </div>
                                            <div>
                                                <div className="font-semibold text-gray-900">{product.name}</div>
                                                <div className="text-sm text-gray-600">{product.customers} nasabah</div>
                                            </div>
                                        </div>
                                        <div className={`text-right ${product.growth >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                            <div className="font-semibold">
                                                {product.growth >= 0 ? '↑' : '↓'} {Math.abs(product.growth)}%
                                            </div>
                                            <div className="text-xs">Growth</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <button className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition text-left">
                            <div className="text-3xl mb-2">📊</div>
                            <div className="font-semibold text-gray-900 mb-1">Laporan Lengkap</div>
                            <div className="text-sm text-gray-600">Lihat laporan marketing detail</div>
                        </button>
                        <button className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition text-left">
                            <div className="text-3xl mb-2">🎯</div>
                            <div className="font-semibold text-gray-900 mb-1">Buat Kampanye</div>
                            <div className="text-sm text-gray-600">Buat kampanye marketing baru</div>
                        </button>
                        <button className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition text-left">
                            <div className="text-3xl mb-2">👥</div>
                            <div className="font-semibold text-gray-900 mb-1">Kelola Leads</div>
                            <div className="text-sm text-gray-600">Manage dan follow-up leads</div>
                        </button>
                    </div>

                    {/* Info Box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <h4 className="font-medium text-blue-900 mb-2">💡 Tips Marketing</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Focus pada produk dengan conversion rate tinggi</li>
                            <li>• Optimalkan kampanye yang sudah berjalan baik</li>
                            <li>• Follow-up leads secara konsisten</li>
                            <li>• Analisis feedback nasabah untuk improvement</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
