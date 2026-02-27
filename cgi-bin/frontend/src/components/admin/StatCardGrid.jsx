import React from 'react';
import { DollarSign, Users, Landmark, PiggyBank } from 'lucide-react';
import { AppConfig } from '../../config';

const formatCurrency = (amount) => {
    if (amount == null) return 'Rp 0';
    // Format ringkas untuk nilai besar
    if (amount >= 1000000000) {
        return `Rp ${(amount / 1000000000).toFixed(2)} Miliar`;
    }
    if (amount >= 1000000) {
        return `Rp ${(amount / 1000000).toFixed(1)} Juta`;
    }
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
}

const StatCard = ({ icon, title, value, isLoading }) => (
    <div className="bg-white p-6 rounded-lg shadow-md">
        {isLoading ? (
            <div className="animate-pulse flex space-x-4">
                <div className="rounded-full bg-gray-200 h-12 w-12"></div>
                <div className="flex-1 space-y-3 py-1">
                    <div className="h-2 bg-gray-200 rounded"></div>
                    <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                </div>
            </div>
        ) : (
            <div className="flex items-center">
                <div className={`p-3 rounded-full bg-blue-100 ${AppConfig.theme.textPrimary} mr-4`}>
                    {icon}
                </div>
                <div>
                    <p className="text-sm font-medium text-gray-500">{title}</p>
                    <p className="text-2xl font-bold text-gray-800">{value}</p>
                </div>
            </div>
        )}
    </div>
);

const StatCardGrid = ({ isLoading, stats }) => {
    const statCardsData = [
        { icon: <DollarSign/>, title: "Pendapatan Biaya (Bln Ini)", value: stats ? formatCurrency(stats.fee_revenue_monthly) : 'Rp 0' },
        { icon: <Landmark/>, title: "Total Dana Nasabah", value: stats ? formatCurrency(stats.total_customer_funds) : 'Rp 0' },
        { icon: <PiggyBank/>, title: "Portofolio Pinjaman Aktif", value: stats ? formatCurrency(stats.outstanding_loan_portfolio) : 'Rp 0' },
        { icon: <Users/>, title: "Nasabah Baru (Bln Ini)", value: stats ? (stats.new_customers_monthly || 0).toLocaleString('id-ID') : '0' }
    ];

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {statCardsData.map((card, index) => (
                <StatCard 
                    key={index} 
                    icon={card.icon} 
                    title={card.title} 
                    value={card.value} 
                    isLoading={isLoading || !stats} 
                />
            ))}
        </div>
    );
};

export default StatCardGrid;

