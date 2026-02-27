import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import useApi from '../hooks/useApi';
import StatCardGrid from '../components/admin/StatCardGrid';
import CustomerGrowthChart from '../components/admin/reports/CustomerGrowthChart';
import DashboardActivityItem from '../components/admin/DashboardActivityItem';
import { Download, Upload, FileText, ChevronRight, DollarSign } from 'lucide-react';

const PendingTaskCard = ({ icon, count, title, link, isLoading }) => (
    <Link to={link} className="block p-4 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition-colors border">
        {isLoading ? (
            <div className="animate-pulse flex items-center">
                <div className="w-10 h-10 rounded-lg bg-gray-200 mr-4"></div>
                <div className="flex-1 space-y-2">
                    <div className="h-3 bg-gray-200 rounded w-3/4"></div>
                    <div className="h-5 bg-gray-200 rounded w-1/2"></div>
                </div>
            </div>
        ) : (
            <div className="flex items-center">
                <div className="p-2 rounded-lg bg-gray-100 text-gray-600 mr-4">
                    {icon}
                </div>
                <div className="flex-1">
                    <p className="text-sm text-gray-500">{title}</p>
                    <p className="text-xl font-bold text-gray-800">{count}</p>
                </div>
                <ChevronRight className="text-gray-400" />
            </div>
        )}
    </Link>
);


const AdminDashboardPage = () => {
    const { loading, error, callApi } = useApi();
    const [dashboardData, setDashboardData] = useState(null);

    const fetchDashboardData = useCallback(async () => {
        // API ini tidak perlu lagi mengirim data grafik, tapi kita tetap panggil untuk KPI dan tugas
        const result = await callApi('admin_get_dashboard_summary.php');
        if (result && result.status === 'success') {
            setDashboardData(result.data);
        }
    }, [callApi]);

    useEffect(() => {
        fetchDashboardData();
    }, [fetchDashboardData]);

    return (
        <div className="space-y-6">
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Dasbor Admin</h1>
            
            {error && <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg" role="alert">{error}</div>}

            <StatCardGrid isLoading={loading || !dashboardData} stats={dashboardData?.kpi} />
            
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <h3 className="text-lg font-semibold text-gray-800 mb-4">Pertumbuhan Nasabah (30 Hari Terakhir)</h3>
                    {/* PERBAIKAN: Panggil komponen grafik tanpa properti */}
                    <CustomerGrowthChart />
                </div>

                <div className="space-y-6">
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Tugas & Persetujuan</h3>
                        <div className="space-y-3">
                           <h4 className="text-xs font-bold text-gray-500 uppercase pt-2">PERSETUJUAN BARU</h4>
                           <PendingTaskCard 
                                icon={<Download size={20}/>} 
                                title="Permintaan Isi Saldo"
                                count={dashboardData?.tasks?.pendingTopups ?? 0}
                                link="/admin/topup-requests"
                                isLoading={loading || !dashboardData}
                           />
                           <PendingTaskCard 
                                icon={<Upload size={20}/>} 
                                title="Permintaan Penarikan"
                                count={dashboardData?.tasks?.pendingWithdrawals ?? 0}
                                link="/admin/withdrawal-requests"
                                isLoading={loading || !dashboardData}
                           />
                           <PendingTaskCard 
                                icon={<FileText size={20}/>} 
                                title="Pengajuan Pinjaman"
                                count={dashboardData?.tasks?.pendingLoans ?? 0}
                                link="/admin/loan-applications"
                                isLoading={loading || !dashboardData}
                           />
                           <h4 className="text-xs font-bold text-gray-500 uppercase pt-4">TUGAS PENCAIRAN DANA</h4>
                           <PendingTaskCard 
                                icon={<DollarSign size={20} className="text-blue-600"/>} 
                                title="Pencairan Pinjaman"
                                count={dashboardData?.tasks?.pendingLoanDisbursements ?? 0}
                                link="/admin/loan-applications"
                                isLoading={loading || !dashboardData}
                           />
                           <PendingTaskCard 
                                icon={<DollarSign size={20} className="text-green-600"/>} 
                                title="Pencairan Penarikan"
                                count={dashboardData?.tasks?.pendingWithdrawalDisbursements ?? 0}
                                link="/admin/withdrawal-requests"
                                isLoading={loading || !dashboardData}
                           />
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h3>
                        <div className="space-y-4">
                            {(loading || !dashboardData) ? (
                                Array(5).fill(0).map((_, i) => <DashboardActivityItem key={`loader-${i}`} isLoading={true} />)
                            ) : dashboardData.recentActivities?.length > 0 ? (
                                dashboardData.recentActivities.map(activity => (
                                    <DashboardActivityItem key={activity.id} activity={activity} />
                                ))
                            ) : (
                                <p className="text-sm text-center text-gray-500 py-4">Belum ada aktivitas.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AdminDashboardPage;

