import React, { useState, useEffect, useCallback } from 'react';
import useApi from '/src/hooks/useApi.js';
import { Pie } from 'react-chartjs-2';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { AppConfig } from '../../../config';

ChartJS.register(ArcElement, Tooltip, Legend);

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const AccountBalanceReport = () => {
    const { loading, error, callApi } = useApi();
    const [reportData, setReportData] = useState([]);

    const fetchReport = useCallback(async () => {
        const result = await callApi('admin_get_account_balance_report.php');
        if (result && result.status === 'success') {
            setReportData(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchReport();
    }, [fetchReport]);
    
    const chartData = {
        labels: reportData.map(d => d.account_type.replace('_', ' ')),
        datasets: [{
            data: reportData.map(d => d.total_balance),
            backgroundColor: [
                AppConfig.theme.colors.BPN_BLUE,
                AppConfig.theme.colors.BPN_YELLOW,
                AppConfig.theme.colors.BPN_RED,
                '#6b7280'
            ],
            borderColor: '#ffffff',
            borderWidth: 2,
        }]
    };

    const chartOptions = {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
        },
    };

    return (
        <div className="bg-white rounded-xl shadow-md border border-gray-200 p-4">
            <h2 className="text-lg font-bold text-gray-800 mb-4">Total Saldo per Jenis Akun</h2>
            {loading && <p>Memuat laporan...</p>}
            {error && <p className="text-red-500">{error}</p>}
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div className="max-w-xs mx-auto">
                    {reportData.length > 0 && <Pie data={chartData} options={chartOptions} />}
                </div>
                <div>
                    <table className="w-full text-left text-sm">
                        <thead className="border-b">
                            <tr>
                                <th className="py-2">Jenis Akun</th>
                                <th className="py-2 text-right">Jumlah Akun</th>
                                <th className="py-2 text-right">Total Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                        {reportData.map(row => (
                            <tr key={row.account_type}>
                                <td className="py-2 font-medium">{row.account_type.replace('_', ' ')}</td>
                                <td className="py-2 text-right">{Number(row.number_of_accounts).toLocaleString('id-ID')}</td>
                                <td className="py-2 text-right font-semibold">{formatCurrency(row.total_balance)}</td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default AccountBalanceReport;
