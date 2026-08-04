import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { PieChart, TrendingUp, Plus, Settings, AlertTriangle, CheckCircle, DollarSign, Wallet, Target, ArrowRight } from 'lucide-react';
import axios from 'axios';
import { Pie, Bar } from 'react-chartjs-2';
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement } from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement);

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
};

const BudgetingPage = () => {
    const [summary, setSummary] = useState(null);
    const [insights, setInsights] = useState([]);
    const [trend, setTrend] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentMonth, setCurrentMonth] = useState(new Date().getMonth() + 1);
    const [currentYear, setCurrentYear] = useState(new Date().getFullYear());

    useEffect(() => {
        fetchData();
    }, [currentMonth, currentYear]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [summaryRes, insightsRes, trendRes] = await Promise.all([
                axios.get('/ajax/user/expense/summary', { params: { year: currentYear, month: currentMonth } }),
                axios.get('/ajax/user/expense/insights'),
                axios.get('/ajax/user/expense/trend', { params: { months: 6 } }),
            ]);
            setSummary(summaryRes.data.data);
            setInsights(insightsRes.data.data);
            setTrend(trendRes.data.data);
        } catch (error) {
            console.error('Error fetching budget data:', error);
        } finally {
            setLoading(false);
        }
    };

    const pieChartData = {
        labels: summary?.categories?.filter(c => c.spent > 0).map(c => c.category_name) || [],
        datasets: [{
            data: summary?.categories?.filter(c => c.spent > 0).map(c => c.spent) || [],
            backgroundColor: summary?.categories?.filter(c => c.spent > 0).map(c => c.category_color) || [],
            borderWidth: 2,
            borderColor: '#fff',
        }],
    };

    const barChartData = {
        labels: trend.map(t => t.month_name),
        datasets: [
            {
                label: 'Pengeluaran',
                data: trend.map(t => t.total_spent),
                backgroundColor: 'rgba(220, 38, 38, 0.7)',
                borderRadius: 4,
            },
            {
                label: 'Budget',
                data: trend.map(t => t.total_budget),
                backgroundColor: 'rgba(30, 58, 138, 0.7)',
                borderRadius: 4,
            },
        ],
    };

    const barChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function (value) {
                        if (value >= 1000000) return (value / 1000000).toFixed(1) + ' Jt';
                        if (value >= 1000) return (value / 1000) + ' Rb';
                        return value;
                    }
                }
            },
            x: { grid: { display: false } }
        },
        plugins: {
            legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 10 } } }
        }
    };

    const getMonthName = (month) => {
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return months[month - 1];
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[400px]">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-bpn-blue mx-auto mb-4"></div>
                    <p className="text-gray-500">Memuat data budgeting...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="bg-bpn-blue text-white rounded-xl shadow-lg p-6 relative overflow-hidden">
                <div className="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full"></div>
                <div className="absolute -bottom-12 -left-12 w-24 h-24 bg-white/10 rounded-full"></div>

                <div className="flex justify-between items-start mb-4">
                    <div>
                        <h1 className="text-xl font-bold mb-1">Personal Budget</h1>
                        <p className="text-white/80 text-sm">{getMonthName(currentMonth)} {currentYear}</p>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() => {
                                if (currentMonth === 1) {
                                    setCurrentMonth(12);
                                    setCurrentYear(currentYear - 1);
                                } else {
                                    setCurrentMonth(currentMonth - 1);
                                }
                            }}
                            className="p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors"
                        >
                            ←
                        </button>
                        <button
                            onClick={() => {
                                if (currentMonth === 12) {
                                    setCurrentMonth(1);
                                    setCurrentYear(currentYear + 1);
                                } else {
                                    setCurrentMonth(currentMonth + 1);
                                }
                            }}
                            className="p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors"
                        >
                            →
                        </button>
                    </div>
                </div>

                <div className="grid grid-cols-3 gap-4 mt-4">
                    <div className="bg-white/10 rounded-lg p-3">
                        <p className="text-white/70 text-xs mb-1">Total Pengeluaran</p>
                        <p className="text-lg font-bold">{formatCurrency(summary?.total_spent)}</p>
                    </div>
                    <div className="bg-white/10 rounded-lg p-3">
                        <p className="text-white/70 text-xs mb-1">Total Budget</p>
                        <p className="text-lg font-bold">{formatCurrency(summary?.total_budget)}</p>
                    </div>
                    <div className="bg-white/10 rounded-lg p-3">
                        <p className="text-white/70 text-xs mb-1">Sisa Budget</p>
                        <p className="text-lg font-bold">{formatCurrency(summary?.remaining)}</p>
                    </div>
                </div>
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-3 gap-3">
                <Link href="/expense/records" className="bg-white rounded-xl shadow-md p-4 flex flex-col items-center hover:shadow-lg transition-shadow">
                    <div className="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                        <DollarSign className="text-blue-600" size={24} />
                    </div>
                    <span className="text-xs font-medium text-gray-700 text-center">Catat Pengeluaran</span>
                </Link>
                <Link href="/expense/budgets" className="bg-white rounded-xl shadow-md p-4 flex flex-col items-center hover:shadow-lg transition-shadow">
                    <div className="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-2">
                        <Target className="text-green-600" size={24} />
                    </div>
                    <span className="text-xs font-medium text-gray-700 text-center">Atur Budget</span>
                </Link>
                <Link href="/expense/analytics" className="bg-white rounded-xl shadow-md p-4 flex flex-col items-center hover:shadow-lg transition-shadow">
                    <div className="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mb-2">
                        <TrendingUp className="text-purple-600" size={24} />
                    </div>
                    <span className="text-xs font-medium text-gray-700 text-center">Analisis</span>
                </Link>
            </div>

            {/* Budget Progress */}
            <div className="bg-white rounded-xl shadow-md p-4">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="font-semibold text-gray-800">Budget per Kategori</h3>
                    <Link href="/expense/budgets" className="text-sm font-semibold text-bpn-blue flex items-center gap-1">
                        Atur <ArrowRight size={14} />
                    </Link>
                </div>
                <div className="space-y-4">
                    {summary?.categories?.filter(c => c.budget > 0).slice(0, 5).map((category) => (
                        <div key={category.category_id}>
                            <div className="flex justify-between items-center mb-1">
                                <div className="flex items-center gap-2">
                                    <span className="text-lg">{category.category_icon}</span>
                                    <span className="text-sm font-medium text-gray-700">{category.category_name}</span>
                                </div>
                                <span className="text-xs text-gray-500">
                                    {formatCurrency(category.spent)} / {formatCurrency(category.budget)}
                                </span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    className={`h-2 rounded-full ${category.is_over_budget ? 'bg-red-500' : category.usage_percentage >= 80 ? 'bg-yellow-500' : 'bg-green-500'}`}
                                    style={{ width: `${Math.min(category.usage_percentage, 100)}%` }}
                                ></div>
                            </div>
                            <div className="flex justify-between mt-1">
                                <span className="text-xs text-gray-500">{category.usage_percentage}% terpakai</span>
                                <span className="text-xs text-gray-500">Sisa {formatCurrency(category.remaining)}</span>
                            </div>
                        </div>
                    ))}
                    {summary?.categories?.filter(c => c.budget > 0).length === 0 && (
                        <div className="text-center py-6">
                            <Wallet className="mx-auto text-gray-300 mb-2" size={48} />
                            <p className="text-gray-500 text-sm">Belum ada budget yang diatur</p>
                            <Link href="/expense/budgets" className="inline-block mt-2 text-bpn-blue text-sm font-semibold">
                                Atur Budget Sekarang
                            </Link>
                        </div>
                    )}
                </div>
            </div>

            {/* Charts */}
            <div className="grid grid-cols-1 gap-6">
                {/* Pie Chart */}
                {summary?.categories?.filter(c => c.spent > 0).length > 0 && (
                    <div className="bg-white rounded-xl shadow-md p-4">
                        <h3 className="font-semibold text-gray-800 mb-4">Distribusi Pengeluaran</h3>
                        <div className="h-64">
                            <Pie data={pieChartData} options={{ responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 12 } } } }} />
                        </div>
                    </div>
                )}

                {/* Bar Chart - Trend */}
                {trend.length > 0 && (
                    <div className="bg-white rounded-xl shadow-md p-4">
                        <h3 className="font-semibold text-gray-800 mb-4">Tren 6 Bulan Terakhir</h3>
                        <div className="h-64">
                            <Bar data={barChartData} options={barChartOptions} />
                        </div>
                    </div>
                )}
            </div>

            {/* Insights */}
            {insights.length > 0 && (
                <div className="bg-white rounded-xl shadow-md p-4">
                    <h3 className="font-semibold text-gray-800 mb-4">Insights & Rekomendasi</h3>
                    <div className="space-y-3">
                        {insights.map((insight, index) => (
                            <div key={index} className={`p-3 rounded-lg flex items-start gap-3 ${
                                insight.type === 'warning' ? 'bg-red-50 border border-red-200' :
                                insight.type === 'alert' ? 'bg-yellow-50 border border-yellow-200' :
                                insight.type === 'success' ? 'bg-green-50 border border-green-200' :
                                'bg-blue-50 border border-blue-200'
                            }`}>
                                {insight.type === 'warning' && <AlertTriangle className="text-red-500 mt-0.5" size={18} />}
                                {insight.type === 'alert' && <AlertTriangle className="text-yellow-500 mt-0.5" size={18} />}
                                {insight.type === 'success' && <CheckCircle className="text-green-500 mt-0.5" size={18} />}
                                {insight.type === 'info' && <Wallet className="text-blue-500 mt-0.5" size={18} />}
                                <div>
                                    <p className="font-medium text-gray-800 text-sm">{insight.title}</p>
                                    <p className="text-gray-600 text-xs mt-1">{insight.message}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Add to Dashboard Link */}
            <div className="bg-gradient-to-r from-bpn-blue to-blue-800 rounded-xl shadow-md p-4 text-white">
                <div className="flex items-center justify-between">
                    <div>
                        <h3 className="font-semibold mb-1">Kelola Keuangan Pribadi</h3>
                        <p className="text-white/80 text-sm">Pantau pengeluaran dan capai target keuangan Anda</p>
                    </div>
                    <Link href="/expense/analytics" className="bg-white/20 px-4 py-2 rounded-lg hover:bg-white/30 transition-colors">
                        Lihat Analisis
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default BudgetingPage;
