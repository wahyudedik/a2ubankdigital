import React, { useState, useEffect } from 'react';
import useApi from '/src/hooks/useApi.js';
import { useModal } from '/src/contexts/ModalContext.jsx';
import Button from '/src/components/ui/Button.jsx';
import { UserCheck, Loader2 } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const ReportCard = ({ icon, title, children }) => (
    <div className="bg-white rounded-xl shadow-md border border-gray-200 flex flex-col h-full">
        <div className="p-4 border-b flex items-center gap-3">
            <div className="p-2 bg-purple-100 text-purple-700 rounded-lg">{icon}</div>
            <h2 className="text-lg font-bold text-gray-800">{title}</h2>
        </div>
        <div className="p-4 flex-grow">{children}</div>
    </div>
);

const TellerReport = () => {
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [tellers, setTellers] = useState([]);
    const [selectedTeller, setSelectedTeller] = useState('');
    const [report, setReport] = useState(null);

    useEffect(() => {
        const fetchTellers = async () => {
            const result = await callApi('admin_get_staff_list.php');
            if (result && result.status === 'success') {
                setTellers(result.data.filter(s => s.role_name === 'Teller' || s.role_name === 'Customer Service'));
            }
        };
        fetchTellers();
    }, [callApi]);

    const fetchReport = async () => {
        if (!selectedTeller) return;
        setReport(null);
        const result = await callApi(`admin_get_teller_report.php?teller_id=${selectedTeller}`);
        if (result && result.status === 'success') {
            setReport(result);
            if (result.data.length === 0) {
                modal.showAlert({
                    title: 'Informasi',
                    message: 'Tidak ditemukan data transaksi untuk teller ini pada tanggal yang dipilih.',
                    type: 'info'
                });
            }
        }
    };

    return (
        <ReportCard icon={<UserCheck size={24} />} title="Kinerja Teller">
            <div className="flex gap-2 items-end mb-4">
                <div className="flex-grow">
                    <label className="text-xs font-medium">Pilih Teller / CS</label>
                    <select value={selectedTeller} onChange={(e) => setSelectedTeller(e.target.value)} className="w-full p-2 border rounded-lg mt-1 text-sm">
                        <option value="">-- Pilih Staf --</option>
                        {tellers.map(t => <option key={t.id} value={t.id}>{t.full_name}</option>)}
                    </select>
                </div>
                <Button onClick={fetchReport} disabled={!selectedTeller || loading}>
                    {loading ? <Loader2 className="animate-spin" /> : 'Cari'}
                </Button>
            </div>
            {loading && !report && <div className="text-center text-sm text-gray-500">Memuat...</div>}
            {error && <p className="text-red-500 text-sm">{error}</p>}
            
            {report && report.summary && (
                 report.summary.transaction_count > 0 ? (
                    <div className="space-y-3">
                         <div className="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                            <span className="font-medium">Total Setoran</span>
                            <span className="font-bold">{formatCurrency(report.summary.total_deposit)}</span>
                        </div>
                        <div className="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                            <span className="font-medium">Total Penarikan</span>
                            <span className="font-bold">{formatCurrency(report.summary.total_withdrawal)}</span>
                        </div>
                        <div className="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                            <span className="font-medium">Jumlah Transaksi</span>
                            <span className="font-bold">{report.summary.transaction_count}</span>
                        </div>
                    </div>
                 ) : !loading && <p className="text-center text-sm text-gray-500 py-4">Tidak ada transaksi oleh staf ini.</p>
            )}
        </ReportCard>
    );
};

export default TellerReport;
