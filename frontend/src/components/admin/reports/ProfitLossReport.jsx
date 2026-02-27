import React, { useState, useEffect, useCallback } from 'react';
import useApi from '/src/hooks/useApi.js';
import { Bar } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';
import { AppConfig } from '../../../config';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const StatCard = ({ title, value, colorClass, loading, detail }) => (
    <div className="bg-gray-50 p-4 rounded-lg">
        <p className="text-sm text-gray-500">{title}</p>
        {loading ? (
             <div className="h-8 w-3/4 bg-gray-200 rounded animate-pulse mt-1"></div>
        ) : (
            <p className={`text-2xl font-bold ${colorClass}`}>{formatCurrency(value)}</p>
        )}
        {detail && !loading && <p className="text-xs text-gray-400 mt-1">{detail}</p>}
    </div>
);

const ProfitLossReport = ({ dateFilter }) => {
    const { loading, error, callApi } = useApi();
    const [report, setReport] = useState(null);

    const fetchReport = useCallback(async (currentDates) => {
        const result = await callApi(`admin_get_profit_loss_report.php?start_date=${currentDates.start_date}&end_date=${currentDates.end_date}`);
        if (result && result.status === 'success') {
            setReport(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        if (dateFilter) {
            fetchReport(dateFilter);
        }
    }, [fetchReport, dateFilter]);

    const chartData = {
        labels: ['Ringkasan Finansial'],
        datasets: [
            {
                label: 'Pendapatan Bunga Pinjaman',
                data: [report?.revenue_from_interest || 0],
                backgroundColor: AppConfig.theme.colors.BPN_BLUE, 
                stack: 'Pendapatan',
            },
            {
                label: 'Pendapatan Biaya Transaksi',
                data: [report?.revenue_from_fees || 0],
                backgroundColor: AppConfig.theme.colors.BPN_YELLOW, 
                stack: 'Pendapatan',
            },
            {
                label: 'Beban Bunga Nasabah',
                data: [report?.total_expense || 0],
                backgroundColor: AppConfig.theme.colors.BPN_RED,
                stack: 'Beban',
            }
        ]
    };
    
    const chartOptions = {
        responsive: true,
        plugins: { 
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) label += ': ';
                        if (context.parsed.y !== null) label += formatCurrency(context.parsed.y);
                        return label;
                    }
                }
            }
        },
        scales: { 
            y: { 
                ticks: { callback: (value) => formatCurrency(value) } 
            } 
        }
    };

    return (
        <div className="bg-white rounded-xl shadow-md border border-gray-200">
            <div className="p-4 border-b">
                <h2 className="text-lg font-bold text-gray-800">Laporan Laba & Rugi</h2>
                <p className="text-sm text-gray-500">Periode: {dateFilter.start_date} s/d {dateFilter.end_date}</p>
            </div>
            <div className="p-4">
                {error && <p className="text-red-500 text-sm my-4">{error}</p>}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <StatCard 
                        title="Pendapatan (Bunga Pinjaman)" 
                        value={report?.revenue_from_interest ?? 0} 
                        colorClass="text-bpn-blue" 
                        loading={loading}
                    />
                    <StatCard 
                        title="Pendapatan (Biaya Transaksi)" 
                        value={report?.revenue_from_fees ?? 0} 
                        colorClass="text-bpn-yellow" 
                        loading={loading}
                    />
                    <StatCard 
                        title="Total Beban" 
                        value={report?.total_expense ?? 0} 
                        colorClass="text-bpn-red" 
                        loading={loading}
                        detail="Bunga yang dibayarkan ke nasabah"
                    />
                    <StatCard 
                        title="Laba / Rugi Bersih" 
                        value={report?.net_profit ?? 0} 
                        colorClass={report?.net_profit >= 0 ? 'text-bpn-blue' : 'text-bpn-red'}
                        loading={loading}
                    />
                </div>
                
                <div className="h-80">
                    {!loading && report && <Bar options={chartOptions} data={chartData} />}
                </div>
            </div>
        </div>
    );
};

export default ProfitLossReport;
