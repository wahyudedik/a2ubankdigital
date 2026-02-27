import React from 'react';
import { Wifi, Lock, Unlock } from 'lucide-react';
import { AppConfig } from '../../config'; // Path sudah benar

// Komponen baru untuk visual kartu debit
const DebitCard = ({ card }) => {
    // Menentukan format tanggal "MM/YY"
    const expiry = card.expiry_date ? new Date(card.expiry_date).toLocaleDateString('id-ID', { month: '2-digit', year: '2-digit' }).replace('.', '/') : 'N/A';

    const isBlocked = card.status === 'BLOCKED';
    const cardGradient = isBlocked
        ? 'from-gray-500 to-gray-700'
        : 'from-bpn-blue to-bpn-blue-dark';

    return (
        <div className={`w-full max-w-sm mx-auto bg-gradient-to-br ${cardGradient} text-white rounded-2xl shadow-lg p-6 flex flex-col justify-between h-56 relative overflow-hidden transition-all duration-300`}>
            {/* Background pattern */}
            <div className="absolute top-0 left-0 w-full h-full opacity-10">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="p" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M20-20v80M-20 20h80" stroke="currentColor" strokeWidth="0.5" shapeRendering="crispEdges"></path></pattern></defs><rect width="100%" height="100%" fill="url(#p)"></rect></svg>
            </div>

            <div className="flex justify-between items-start z-10">
                <img src={AppConfig.brand.logoWhite} alt="A2U Bank Digital Logo" className="h-7" />
                <Wifi size={24} className="transform rotate-90" />
            </div>

            <div className="z-10">
                {/* Chip Kartu */}
                <div className="w-14 h-10 bg-gradient-to-br from-yellow-300 to-yellow-500 rounded-md mb-3 flex items-center justify-center shadow-inner">
                    <div className="w-12 h-8 bg-gradient-to-br from-yellow-200 to-yellow-400 rounded-sm"></div>
                </div>
                <p className="font-mono text-2xl tracking-widest">{card.card_number_masked.replace(/-/g, ' ')}</p>
            </div>

            <div className="flex justify-between items-end z-10">
                <div>
                    <p className="text-xs uppercase opacity-70">Pemegang Kartu</p>
                    <p className="font-medium tracking-wider text-lg">{card.full_name}</p>
                </div>
                <div>
                    <p className="text-xs uppercase opacity-70 text-right">Berlaku s/d</p>
                    <p className="font-medium font-mono">{expiry}</p>
                </div>
            </div>
            {isBlocked && (
                <div className="absolute inset-0 bg-black/40 flex items-center justify-center z-20 backdrop-blur-sm">
                    <div className="flex items-center gap-2 text-xl font-bold">
                        <Lock size={24} />
                        <span>TERBLOKIR</span>
                    </div>
                </div>
            )}
        </div>
    );
};

export default DebitCard;
