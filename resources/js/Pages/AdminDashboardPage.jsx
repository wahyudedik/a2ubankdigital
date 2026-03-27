import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import StatCardGrid from '@/components/admin/StatCardGrid';
import CustomerGrowthChart from '@/components/admin/reports/CustomerGrowthChart';
import DashboardActivityItem from '@/components/admin/DashboardActivityItem';
import { Download, Upload, FileText, ChevronRight, DollarSign } from 'lucide-react';

const PendingTaskCard = ({ icon, count, title, link }) => (
    <Link href={link} className="block p-4 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition-colors border">
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
    </Link>
);

const AdminDashboardPage = () => {
    const { kpi, tasks, recentActivities } = usePage().props;

    return (
        <div className="space-y-6">
            <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Dasbor Admin</h1>

            <StatCardGrid isLoading={false} stats={kpi} />

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
                    <h3 className="text-lg font-semibold text-gray-800 mb-4">Pertumbuhan Nasabah (30 Hari Terakhir)</h3>
                    <CustomerGrowthChart />
                </div>

                <div className="space-y-6">
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Tugas & Persetujuan</h3>
                        <div className="space-y-3">
                            <h4 className="text-xs font-bold text-gray-500 uppercase pt-2">PERSETUJUAN BARU</h4>
                            <PendingTaskCard
                                icon={<Download size={20} />}
                                title="Permintaan Isi Saldo"
                                count={tasks?.pendingTopups ?? 0}
                                link="/admin/topup-requests"
                            />
                            <PendingTaskCard
                                icon={<Upload size={20} />}
                                title="Permintaan Penarikan"
                                count={tasks?.pendingWithdrawals ?? 0}
                                link="/admin/withdrawal-requests"
                            />
                            <PendingTaskCard
                                icon={<FileText size={20} />}
                                title="Pengajuan Pinjaman"
                                count={tasks?.pendingLoans ?? 0}
                                link="/admin/loan-applications"
                            />
                            <h4 className="text-xs font-bold text-gray-500 uppercase pt-4">TUGAS PENCAIRAN DANA</h4>
                            <PendingTaskCard
                                icon={<DollarSign size={20} className="text-blue-600" />}
                                title="Pencairan Pinjaman"
                                count={tasks?.pendingLoanDisbursements ?? 0}
                                link="/admin/loan-applications"
                            />
                            <PendingTaskCard
                                icon={<DollarSign size={20} className="text-green-600" />}
                                title="Pencairan Penarikan"
                                count={tasks?.pendingWithdrawalDisbursements ?? 0}
                                link="/admin/withdrawal-requests"
                            />
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h3 className="text-lg font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h3>
                        <div className="space-y-4">
                            {recentActivities?.length > 0 ? (
                                recentActivities.map(activity => (
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
