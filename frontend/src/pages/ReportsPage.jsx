import React, { useState, useCallback } from 'react';
import { BarChart, DollarSign, Users, TrendingUp, UserCheck, AlertTriangle, Package, Activity } from 'lucide-react';
import Input from '../components/ui/Input';
import Button from '../components/ui/Button';

// Impor semua komponen laporan individual
import ProfitLossReport from '../components/admin/reports/ProfitLossReport';
import DailyReport from '../components/admin/reports/DailyReport';
import TellerReport from '../components/admin/reports/TellerReport';
import AcquisitionReport from '../components/admin/reports/AcquisitionReport';
import CustomerGrowthChart from '../components/admin/reports/CustomerGrowthChart';
import NplReport from '../components/admin/reports/NplReport';
import ProductPerformanceReport from '../components/admin/reports/ProductPerformanceReport';
import AccountBalanceReport from '../components/admin/reports/AccountBalanceReport';

const formatDate = (date) => new Date(date).toISOString().split('T')[0];

// Komponen Tab
const TabButton = ({ id, activeTab, setActiveTab, icon, children }) => (
    <button
        onClick={() => setActiveTab(id)}
        className={`flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-colors ${
            activeTab === id
                ? 'bg-bpn-blue text-white shadow'
                : 'text-gray-600 hover:bg-gray-200'
        }`}
    >
        {icon}
        {children}
    </button>
);

const ReportsPage = () => {
    const [activeTab, setActiveTab] = useState('financial');
    const [dates, setDates] = useState({
        start_date: formatDate(new Date(new Date().getFullYear(), new Date().getMonth(), 1)),
        end_date: formatDate(new Date())
    });
    const [dateFilter, setDateFilter] = useState(dates);

    const handleDateChange = (e) => {
        setDates(prev => ({ ...prev, [e.target.name]: e.target.value }));
    };

    const applyDateFilter = useCallback(() => {
        setDateFilter(dates);
    }, [dates]);

    return (
        <div>
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div className="flex items-center gap-3">
                    <div className="p-3 bg-bpn-blue text-white rounded-lg">
                        <BarChart size={28}/>
                    </div>
                    <div>
                        <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Pusat Laporan</h1>
                        <p className="text-gray-500">Analisis kinerja bisnis Anda secara mendalam.</p>
                    </div>
                </div>
                {/* Filter Tanggal Terpusat */}
                <div className="w-full md:w-auto flex flex-col sm:flex-row items-stretch gap-2 bg-white p-2 rounded-lg border">
                    <Input type="date" name="start_date" label="Dari" value={dates.start_date} onChange={handleDateChange} />
                    <Input type="date" name="end_date" label="Sampai" value={dates.end_date} onChange={handleDateChange} />
                    <Button onClick={applyDateFilter} className="h-full">Terapkan</Button>
                </div>
            </div>

            {/* Navigasi Tab */}
            <div className="flex items-center gap-2 mb-6 p-2 bg-gray-100 rounded-lg">
                <TabButton id="financial" activeTab={activeTab} setActiveTab={setActiveTab} icon={<DollarSign size={16}/>}>Finansial</TabButton>
                <TabButton id="operational" activeTab={activeTab} setActiveTab={setActiveTab} icon={<Activity size={16}/>}>Operasional</TabButton>
                <TabButton id="customer" activeTab={activeTab} setActiveTab={setActiveTab} icon={<Users size={16}/>}>Nasabah & Akuisisi</TabButton>
            </div>

            {/* Konten Tab */}
            <div>
                {activeTab === 'financial' && (
                    <div className="space-y-6">
                        <ProfitLossReport dateFilter={dateFilter} />
                        <AccountBalanceReport />
                    </div>
                )}

                {activeTab === 'operational' && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <DailyReport dateFilter={dateFilter} />
                        <TellerReport />
                        <ProductPerformanceReport />
                        <NplReport />
                    </div>
                )}
                
                {activeTab === 'customer' && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                         <div className="lg:col-span-2">
                             <CustomerGrowthChart dateFilter={dateFilter} />
                        </div>
                        <AcquisitionReport dateFilter={dateFilter} />
                    </div>
                )}
            </div>
        </div>
    );
};

export default ReportsPage;
