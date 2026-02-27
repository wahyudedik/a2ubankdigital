import React, { useState, useEffect, useCallback } from 'react';
import useApi from '/src/hooks/useApi.js';
import { Users } from 'lucide-react';

const ReportCard = ({ icon, title, children }) => (
    <div className="bg-white rounded-xl shadow-md border border-gray-200 flex flex-col h-full">
        <div className="p-4 border-b flex items-center gap-3">
            <div className="p-2 bg-blue-100 text-blue-700 rounded-lg">{icon}</div>
            <h2 className="text-lg font-bold text-gray-800">{title}</h2>
        </div>
        <div className="p-4 flex-grow">{children}</div>
    </div>
);

const AcquisitionReport = ({ dateFilter }) => {
    const { loading, error, callApi } = useApi();
    const [report, setReport] = useState([]);
    
    const fetchReport = useCallback(async (currentDates) => {
        const result = await callApi(`admin_get_marketing_report.php?start_date=${currentDates.start_date}&end_date=${currentDates.end_date}`);
        if (result && result.status === 'success') setReport(result.data);
    }, [callApi]);

    useEffect(() => {
        if (dateFilter) {
            fetchReport(dateFilter);
        }
    }, [dateFilter, fetchReport]);

    return (
        <ReportCard icon={<Users size={24} />} title="Kinerja Akuisisi Staf">
             {loading && <div className="text-center text-sm text-gray-500">Memuat...</div>}
             {error && <div className="text-center text-sm text-red-500">{error}</div>}
             {report.length > 0 ? (
                <ul className="space-y-2">
                    {report.map(item => (
                        <li key={item.marketing_name} className="flex justify-between items-center text-sm p-2 rounded-md hover:bg-gray-50">
                            <span>{item.marketing_name}</span>
                            <span className="font-bold">{item.new_customers} Nasabah</span>
                        </li>
                    ))}
                </ul>
             ) : !loading && <p className="text-center text-sm text-gray-500 py-4">Tidak ada data akuisisi pada periode ini.</p>}
        </ReportCard>
    );
};

export default AcquisitionReport;
