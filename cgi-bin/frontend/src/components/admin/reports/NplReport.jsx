import React, { useState, useEffect, useCallback } from 'react';
import useApi from '/src/hooks/useApi.js';
import Input from '/src/components/ui/Input.jsx';
import Button from '/src/components/ui/Button.jsx';
import { AlertTriangle, Loader2 } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const ReportCard = ({ icon, title, children }) => (
    <div className="bg-white rounded-xl shadow-md border border-gray-200 flex flex-col h-full">
        <div className="p-4 border-b flex items-center gap-3">
            <div className="p-2 bg-red-100 text-red-700 rounded-lg">{icon}</div>
            <h2 className="text-lg font-bold text-gray-800">{title}</h2>
        </div>
        <div className="p-4 flex-grow">{children}</div>
    </div>
);

const NplReport = () => {
    const { loading, error, callApi } = useApi();
    const [daysOverdue, setDaysOverdue] = useState('30');
    const [report, setReport] = useState([]);
    
    const fetchReport = useCallback(async () => {
        const result = await callApi(`admin_get_npl_report.php?days_overdue=${daysOverdue}`);
        if (result && result.status === 'success') {
            setReport(result.data);
        }
    }, [callApi, daysOverdue]);

    // Fetch on initial load
    useEffect(() => {
        fetchReport();
    }, []);

    const handleSearch = (e) => {
        e.preventDefault();
        fetchReport();
    };

    return (
        <ReportCard icon={<AlertTriangle size={24} />} title="Pinjaman Bermasalah (NPL)">
            <form onSubmit={handleSearch} className="flex items-center gap-2 mb-4">
                <label className="text-sm">Tunggakan {'>='}</label>
                <Input type="number" value={daysOverdue} onChange={e => setDaysOverdue(e.target.value)} className="w-20 text-sm p-1" />
                <label className="text-sm">hari</label>
                <Button type="submit" disabled={loading} className="py-1 px-3 text-sm ml-auto">
                    {loading ? <Loader2 className="animate-spin" /> : 'Cari'}
                </Button>
            </form>
            {loading && <div className="text-center text-sm text-gray-500">Memuat...</div>}
            {error && <p className="text-red-500 text-sm">{error}</p>}
            {report.length > 0 ? (
                <ul className="space-y-2 text-sm max-h-48 overflow-y-auto pr-2">
                    {report.map((item, index) => (
                         <li key={index} className="flex justify-between items-center p-2 rounded-md hover:bg-gray-50">
                            <div>
                                <p className="font-semibold">{item.full_name}</p>
                                <p className="text-xs text-gray-500">{item.phone_number}</p>
                            </div>
                            <div className="text-right">
                                <p className="font-bold text-red-600">{formatCurrency(item.installment_amount)}</p>
                                <p className="text-xs text-red-500">{item.overdue_days} hari</p>
                            </div>
                        </li>
                    ))}
                </ul>
            ) : !loading && <p className="text-center text-sm text-gray-500 py-4">Tidak ada pinjaman bermasalah.</p>}
        </ReportCard>
    );
};

export default NplReport;
