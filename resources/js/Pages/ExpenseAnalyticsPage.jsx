import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { TrendingUp, TrendingDown, Calendar, ArrowRight, Download } from 'lucide-react';
import axios from 'axios';
import { Bar, Line, Doughnut } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend, Filler } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Title, Tooltip, Legend, Filler);

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
};

const ExpenseAnalyticsPage = () => {
    const [summary, setSummary] = useState(null);
    const [trend, setTrend] = useState([]);
    const [insights, setInsights] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentMonth, setCurrentMonth] = useState(new Date().getMonth() + 1);
    const [currentYear, setCurrentYear] = useState(new Date().getFullYear());

    useEffect(() => {
        fetchData();
    }, [currentMonth, currentYear]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [summaryRes, trendRes, insightsRes] = await Promise.all([
                axios.get('/ajax/user/expense/summary', { params: { year: currentYear, month: currentMonth } }),
                axios.get('/ajax/user/expense/trend', { params: { months: 6 } }),
                axios.get('/ajax/user/expense/insights'),
            ]);
            setSummary(summaryRes.data.data);
            setTrend(trendRes.data.data);
            setInsights(insightsRes.data.data);
        } catch (error) {
            console.error('Error fetching analytics:', error);
        } finally {
            setLoading(false);
        }
    };

    const getMonthName = (month) => {
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return months[month - 1];
    };

    // Chart configurations
    const doughnutChartData = {
        labels: summary?.categories?.filter(c => c.spent > 0).map(c => c.category_name) || [],
        datasets: [{
            data: summary?.categories?.filter(c => c.spent > 0).map(c => c.spent) || [],
            backgroundColor: summary?.categories?.filter(c => c.spent > 0).map(c => c.category_color) || [],
            borderWidth: 3,
            borderColor: '#fff',
        }],
    };

    const barChartData = {
        labels: summary?.categories?.filter(c => c.spent > 0).map(c => c.category_name) || [],
        datasets: [
            {
                label: 'Pengeluaran',
                data: summary?.categories?.filter(c => c.spent > 0).map(c => c.spent) || [],
                backgroundColor: summary?.categories?.filter(c => c.spent > 0).map(c => c.category_color + 'CC') || [],
                borderRadius: 6,
            },
            {
                label: 'Budget',
                data: summary?.categories?.filter(c => c.spent > 0).map(c => c.budget) || [],
                backgroundColor: 'rgba(200, 200, 200, 0.5)',
                borderRadius: 6,
            },
        ],
    };

    const lineChartData = {
        labels: trend.map(t => t.month_name),
        datasets: [{
            label: 'Pengeluaran',
            data: trend.map(t => t.total_spent),
            borderColor: '#DC2626',
            backgroundColor: 'rgba(220, 38, 38, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#DC2626',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
        }],
    };

    const chartOptions = {
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
            legend: { display: true, position: 'top', labels: { boxWidth: 12 } }
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[400px]">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-bpn-blue mx-auto mb-4"></div>
                    <p className="text-gray-500">Memuat analisis...</p>
                </div>
            </div>
        );
    }

    // Calculate stats
    const totalSpent = summary?.total_spent || 0;
    const totalBudget = summary?.total_budget || 0;
    const remaining = summary?.remaining || 0;
    const budgetUsedPercent = totalBudget > 0 ? ((totalSpent / totalBudget) * 100).toFixed(1) : 0;

    // Top spending category
    const topCategory = summary?.categories?.filter(c => c.spent > 0).sort((a, b) => b.spent - a.spent)[0];

    // Categories over budget
    const overBudgetCategories = summary?.categories?.filter(c => c.is_over_budget) || [];

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="bg-gradient-to-r from-bpn-blue to-blue-800 text-white rounded-xl shadow-lg p-6">
                <div className="flex justify-between items-center mb-4">
                    <div>
                        <h1 className="text-2xl font-bold">Analisis Pengeluaran</h1>
                        <p className="text-white/80 text-sm">Pahami pola pengeluaran Anda</p>
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
                        <span className="px-4 py-2 bg-white/10 rounded-lg font-medium">
                            {getMonthName(currentMonth)} {currentYear}
                        </span>
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

                {/* Stats Grid */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                    <div className="bg-white/10 rounded-lg p-3">
                        <p className="text-white/70 text-xs mb-1">Total Pengeluaran</p>
                        <p className="text-xl font-bold">{formatCurrency(totalSpent)}</p>
                    </div>
                    <div className="bg-white/10 rounded-lg p-3">
                        <p className="text-white/70 text-xs mb-1">Total Budget</p>
                        <p className="text-xl font-bold">{formatCurrency(totalBudget)}</p>
                    </div>
                    <div className="bg-white/10 rounded-lg p-3">
                        <p className="text-white/70 text-xs mb-1">Sisa Budget</p>
                        <p className="text-xl font-bold">{formatCurrency(remaining)}</p>
                    </div>
                    <div className="bg-white/10 rounded-lg p-3">
                        <p className="text-white/70 text-xs mb-1">Budget Terpakai</p>
                        <p className="text-xl font-bold">{budgetUsedPercent}%</p>
                    </div>
                </div>
            </div>

            {/* Top Category & Over Budget Alerts */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Top Spending */}
                {topCategory && (
                    <div className="bg-white rounded-xl shadow-md p-4">
                        <h3 className="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                            <TrendingUp className="text-red-500" size={18} />
                            Pengeluaran Terbesar
                        </h3>
                        <div className="flex items-center gap-4">
                            <div className="w-16 h-16 rounded-xl flex items-center justify-center text-3xl" style={{ backgroundColor: topCategory.category_color + '20' }}>
                                {topCategory.category_icon}
                            </div>
                            <div>
                                <p className="font-medium text-gray-800">{topCategory.category_name}</p>
                                <p className="text-2xl font-bold text-gray-800">{formatCurrency(topCategory.spent)}</p>
                                <p className="text-sm text-gray-500">{topCategory.usage_percentage}% dari budget</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Over Budget Alert */}
                <div className={`rounded-xl shadow-md p-4 ${overBudgetCategories.length > 0 ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'}`}>
                    <h3 className={`font-semibold mb-3 flex items-center gap-2 ${overBudgetCategories.length > 0 ? 'text-red-800' : 'text-green-800'}`}>
                        {overBudgetCategories.length > 0 ? (
                            <><TrendingDown className="text-red-500" size={18} /> Melebihi Budget</>
                        ) : (
                            <><span className="text-green-500">✓</span> Semua Dalam Budget</>
                        )}
                    </h3>
                    {overBudgetCategories.length > 0 ? (
                        <div className="space-y-2">
                            {overBudgetCategories.map(cat => (
                                <div key={cat.category_id} className="flex justify-between items-center bg-white rounded-lg p-2">
                                    <span className="text-sm text-gray-700">{cat.category_icon} {cat.category_name}</span>
                                    <span className="text-sm font-semibold text-red-600">+{formatCurrency(cat.spent - cat.budget)}</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-green-700 text-sm">Selamat! Semua pengeluaran Anda masih dalam batas budget. 🎉</p>
                    )}
                </div>
            </div>

            {/* Charts */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Doughnut Chart */}
                {summary?.categories?.filter(c => c.spent > 0).length > 0 && (
                    <div className="bg-white rounded-xl shadow-md p-4">
                        <h3 className="font-semibold text-gray-800 mb-4">Distribusi Pengeluaran</h3>
                        <div className="h-64 flex items-center justify-center">
                            <Doughnut
                                data={doughnutChartData}
                                options={{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } }
                                    },
                                    cutout: '60%',
                                }}
                            />
                        </div>
                    </div>
                )}

                {/* Bar Chart - Spending vs Budget */}
                {summary?.categories?.filter(c => c.spent > 0).length > 0 && (
                    <div className="bg-white rounded-xl shadow-md p-4">
                        <h3 className="font-semibold text-gray-800 mb-4">Pengeluaran vs Budget</h3>
                        <div className="h-64">
                            <Bar data={barChartData} options={chartOptions} />
                        </div>
                    </div>
                )}
            </div>

            {/* Trend Line Chart */}
            {trend.length > 0 && (
                <div className="bg-white rounded-xl shadow-md p-4">
                    <h3 className="font-semibold text-gray-800 mb-4">Tren Pengeluaran 6 Bulan</h3>
                    <div className="h-64">
                        <Line data={lineChartData} options={chartOptions} />
                    </div>
                </div>
            )}

            {/* Category Details Table */}
            <div className="bg-white rounded-xl shadow-md p-4">
                <h3 className="font-semibold text-gray-800 mb-4">Detail per Kategori</h3>
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-gray-200">
                                <th className="text-left py-3 px-2 text-sm font-semibold text-gray-600">Kategori</th>
                                <th className="text-right py-3 px-2 text-sm font-semibold text-gray-600">Pengeluaran</th>
                                <th className="text-right py-3 px-2 text-sm font-semibold text-gray-600">Budget</th>
                                <th className="text-right py-3 px-2 text-sm font-semibold text-gray-600">Sisa</th>
                                <th className="text-right py-3 px-2 text-sm font-semibold text-gray-600">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            {summary?.categories?.filter(c => c.spent > 0).sort((a, b) => b.spent - a.spent).map((cat) => (
                                <tr key={cat.category_id} className="border-b border-gray-100 hover:bg-gray-50">
                                    <td className="py-3 px-2">
                                        <div className="flex items-center gap-2">
                                            <span className="text-lg">{cat.category_icon}</span>
                                            <span className="text-sm font-medium text-gray-800">{cat.category_name}</span>
                                        </div>
                                    </td>
                                    <td className="py-3 px-2 text-right text-sm font-medium text-gray-800">{formatCurrency(cat.spent)}</td>
                                    <td className="py-3 px-2 text-right text-sm text-gray-600">{cat.budget > 0 ? formatCurrency(cat.budget) : '-'}</td>
                                    <td className="py-3 px-2 text-right text-sm">
                                        <span className={cat.remaining > 0 ? 'text-green-600' : 'text-red-600'}>
                                            {cat.budget > 0 ? formatCurrency(cat.remaining) : '-'}
                                        </span>
                                    </td>
                                    <td className="py-3 px-2 text-right">
                                        {cat.budget > 0 ? (
                                            <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${cat.is_over_budget ? 'bg-red-100 text-red-700' :
                                                    cat.usage_percentage >= 80 ? 'bg-yellow-100 text-yellow-700' :
                                                        'bg-green-100 text-green-700'
                                                }`}>
                                                {cat.usage_percentage}%
                                            </span>
                                        ) : '-'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Insights */}
            {insights.length > 0 && (
                <div className="bg-white rounded-xl shadow-md p-4">
                    <h3 className="font-semibold text-gray-800 mb-4">💡 Insights & Rekomendasi</h3>
                    <div className="space-y-3">
                        {insights.map((insight, index) => (
                            <div key={index} className={`p-3 rounded-lg flex items-start gap-3 ${insight.type === 'warning' ? 'bg-red-50 border border-red-200' :
                                    insight.type === 'alert' ? 'bg-yellow-50 border border-yellow-200' :
                                        insight.type === 'success' ? 'bg-green-50 border border-green-200' :
                                            'bg-blue-50 border border-blue-200'
                                }`}>
                                <div className="flex-1">
                                    <p className="font-medium text-gray-800 text-sm">{insight.title}</p>
                                    <p className="text-gray-600 text-xs mt-1">{insight.message}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* Back to Budgeting */}
            <div className="text-center">
                <Link href="/budgeting" className="inline-flex items-center gap-2 text-bpn-blue font-semibold hover:underline">
                    ← Kembali ke Dashboard Budgeting
                </Link>
            </div>
        </div>
    );
};

export default ExpenseAnalyticsPage;
