import React, { useState, useEffect, useCallback } from 'react';
import useApi from '/src/hooks/useApi.js';
import { Package } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const ReportCard = ({ icon, title, children }) => (
    <div className="bg-white rounded-xl shadow-md border border-gray-200 flex flex-col h-full">
        <div className="p-4 border-b flex items-center gap-3">
            <div className="p-2 bg-indigo-100 text-indigo-700 rounded-lg">{icon}</div>
            <h2 className="text-lg font-bold text-gray-800">{title}</h2>
        </div>
        <div className="p-4 flex-grow">{children}</div>
    </div>
);

const ProductPerformanceReport = () => {
    const { loading, error, callApi } = useApi();
    const [report, setReport] = useState(null);

    const fetchReport = useCallback(async () => {
        const result = await callApi('admin_get_product_performance_report.php');
        if (result && result.status === 'success') {
            setReport(result.data);
        }
    }, [callApi]);
    
    useEffect(() => {
        fetchReport();
    }, [fetchReport]);

    return (
        <ReportCard icon={<Package size={24} />} title="Kinerja Produk">
            {loading && <div className="text-center text-sm text-gray-500">Memuat...</div>}
            {error && <div className="text-center text-sm text-red-500">{error}</div>}
            {report && (
                <div className="space-y-6">
                    <div>
                        <h3 className="font-semibold text-gray-700 mb-2 text-sm">Produk Pinjaman</h3>
                        {report.loans.length > 0 ? (
                            <table className="w-full text-sm">
                                <tbody>
                                    {report.loans.map(p => (
                                        <tr key={p.product_name} className="border-b">
                                            <td className="py-2">{p.product_name}</td>
                                            <td className="py-2 text-right">{p.total_disbursed} pencairan</td>
                                            <td className="py-2 text-right font-semibold">{formatCurrency(p.total_amount)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : <p className="text-xs text-gray-500">Tidak ada data.</p>}
                    </div>
                     <div>
                        <h3 className="font-semibold text-gray-700 mb-2 text-sm">Produk Deposito</h3>
                         {report.deposits.length > 0 ? (
                            <table className="w-full text-sm">
                                <tbody>
                                    {report.deposits.map(p => (
                                        <tr key={p.product_name} className="border-b">
                                            <td className="py-2">{p.product_name}</td>
                                            <td className="py-2 text-right">{p.total_accounts} rekening</td>
                                            <td className="py-2 text-right font-semibold">{formatCurrency(p.total_balance)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : <p className="text-xs text-gray-500">Tidak ada data.</p>}
                    </div>
                </div>
            )}
        </ReportCard>
    );
};

export default ProductPerformanceReport;
