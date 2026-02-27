import React from 'react';
import { formatDistanceToNow } from 'date-fns';
import { id } from 'date-fns/locale';
import { ArrowDown, ArrowUp, ArrowRightLeft, PiggyBank, CreditCard, Briefcase } from 'lucide-react';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

// --- KOMPONEN BARU UNTUK IKON DINAMIS ---
const ActivityIcon = ({ type }) => {
    const iconProps = { size: 20, className: "text-gray-600" };
    let icon;

    switch (type) {
        case 'SETOR_TUNAI':
            icon = <ArrowDown {...iconProps} />;
            break;
        case 'TARIK_TUNAI':
        case 'PENARIKAN_DANA':
            icon = <ArrowUp {...iconProps} />;
            break;
        case 'TRANSFER_INTERNAL':
        case 'TRANSFER_EKSTERNAL':
            icon = <ArrowRightLeft {...iconProps} />;
            break;
        case 'BAYAR_CICILAN':
        case 'PENCAIRAN_PINJAMAN':
            icon = <PiggyBank {...iconProps} />;
            break;
        case 'PEMBUKAAN_DEPOSITO':
        case 'PENCAIRAN_DEPOSITO':
            icon = <CreditCard {...iconProps} />;
            break;
        default:
            icon = <Briefcase {...iconProps} />;
    }

    return (
        <div className="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 mr-4 flex items-center justify-center">
            {icon}
        </div>
    );
};


const DashboardActivityItem = ({ activity, isLoading }) => {
    if (isLoading || !activity) {
        return (
            <div className="animate-pulse flex items-center space-x-3">
                <div className="w-10 h-10 rounded-lg bg-gray-200"></div>
                <div className="flex-1 space-y-2">
                    <div className="h-3 bg-gray-200 rounded w-4/5"></div>
                    <div className="h-3 bg-gray-200 rounded w-2/5"></div>
                </div>
            </div>
        );
    }

    const timeAgo = formatDistanceToNow(new Date(activity.created_at), { addSuffix: true, locale: id });
    
    // Mengubah nama transaksi agar lebih mudah dibaca
    const activityText = activity.transaction_type.toLowerCase().replace(/_/g, ' ');

    return (
        <div className="flex items-center">
            {/* --- PERBAIKAN: Mengganti div statis dengan komponen ikon dinamis --- */}
            <ActivityIcon type={activity.transaction_type} />
            <div className="flex-grow">
                <p className="text-sm text-gray-800">
                    <span className="font-semibold">{activity.full_name}</span> melakukan {activityText}
                </p>
                <p className="text-xs text-gray-500">{timeAgo}</p>
            </div>
            <div className="text-sm font-semibold text-gray-900">
                {formatCurrency(activity.amount)}
            </div>
        </div>
    );
};

export default DashboardActivityItem;

