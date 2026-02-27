import React, { useState, useEffect, useCallback } from 'react';
import useApi from '../../../hooks/useApi.js';
import { Calendar } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const ReportCard = ({ icon, title, children }) => (
    <div className="bg-white rounded-xl shadow-md border border-gray-200 flex flex-col h-full">
        <div className="p-4 border-b flex items-center gap-3">
            <div className="p-2 bg-bpn-yellow text-white rounded-lg">{icon}</div>
            <h2 className="text-lg font-bold text-gray-800">{title}</h2>
        </div>
        <div className="p-4 flex-grow">{children}</div>
    </div>
);

const DailyReport = ({ dateFilter }) => {
    const { loading, error, callApi } = useApi();
    const [report, setReport] = useState(null);

    const fetchReport = useCallback(async (selectedDate) => {
        const result = await callApi(`admin_get_daily_report.php?date=${selectedDate}`);
        if (result && result.status === 'success') setReport(result.data);
    }, [callApi]);

    useEffect(() => {
        // Hanya ambil tanggal akhir dari filter untuk laporan harian
        if (dateFilter && dateFilter.end_date) {
            fetchReport(dateFilter.end_date);
        }
    }, [dateFilter, fetchReport]);
    
    return (
        <ReportCard icon={<Calendar size={24} />} title="Ringkasan Harian">
            <p className="text-sm text-gray-500 mb-4">Menampilkan data untuk tanggal: <strong>{dateFilter.end_date}</strong></p>
            {loading && <div className="text-center text-sm text-gray-500">Memuat...</div>}
            {error && <div className="text-center text-sm text-red-500">{error}</div>}
            {report ? (
                <div className="space-y-3">
                    <div className="flex justify-between items-center bg-green-50 p-3 rounded-lg">
                        <span className="font-medium text-green-800">Total Pemasukan</span>
                        <span className="font-bold text-green-900">{formatCurrency(report.summary.total_credit)}</span>
                    </div>
                    <div className="flex justify-between items-center bg-red-50 p-3 rounded-lg">
                        <span className="font-medium text-red-800">Total Pengeluaran</span>
                        <span className="font-bold text-red-900">{formatCurrency(report.summary.total_debit)}</span>
                    </div>
                    <div className="border-t mt-4 pt-4">
                         <h4 className="font-semibold text-sm mb-2">Rincian Transaksi:</h4>
                         <table className="w-full text-sm">
                            <tbody>
                            {Object.entries(report.details).length > 0 ? Object.entries(report.details).map(([type, data]) => (
                                <tr key={type}>
                                    <td className="py-1">{type.replace(/_/g, ' ')}</td>
                                    <td className="py-1 text-right">{data.count} trx</td>
                                    <td className="py-1 text-right font-semibold">{formatCurrency(data.amount)}</td>
                                </tr>
                            )) : <tr><td colSpan="3" className="text-center text-gray-500 py-4">Tidak ada transaksi.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                </div>
            ) : !loading && <p className="text-center text-gray-500 py-4">Tidak ada data untuk tanggal ini.</p>}
        </ReportCard>
    );
};

export default DailyReport;
