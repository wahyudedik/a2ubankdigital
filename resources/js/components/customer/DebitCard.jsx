import React from 'react';

const DebitCard = ({ card }) => {
    if (!card) return null;

    return (
        <div className="bg-gradient-to-br from-bpn-blue to-bpn-blue-dark text-white rounded-xl p-6 shadow-lg">
            <div className="flex justify-between items-start mb-8">
                <span className="text-sm font-medium opacity-80">A2U Bank Digital</span>
                <span className="text-xs opacity-60">{card.card_type || 'DEBIT'}</span>
            </div>
            <p className="font-mono text-lg tracking-wider mb-4">
                {card.card_number || '**** **** **** ****'}
            </p>
            <div className="flex justify-between items-end">
                <div>
                    <p className="text-xs opacity-60">Pemegang Kartu</p>
                    <p className="text-sm font-medium">{card.holder_name || '-'}</p>
                </div>
                <div className="text-right">
                    <p className="text-xs opacity-60">Berlaku s/d</p>
                    <p className="text-sm font-medium">{card.expiry_date || '--/--'}</p>
                </div>
            </div>
        </div>
    );
};

export default DebitCard;
