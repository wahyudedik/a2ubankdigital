import React from 'react';

/**
 * Chip EMV logam (SVG inline) dengan gradien emas/kuningan khas chip kartu.
 * ID gradien dibuat unik per kartu agar aman saat render banyak kartu.
 */
const ChipIcon = ({ uid }) => (
    <svg width="46" height="34" viewBox="0 0 46 34" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" className="shrink-0 drop-shadow-[0_1px_2px_rgba(0,0,0,0.45)]">
        <defs>
            <linearGradient id={`chip-grad-${uid}`} x1="0" y1="0" x2="46" y2="34" gradientUnits="userSpaceOnUse">
                <stop stopColor="#F8F0C4" />
                <stop offset="0.45" stopColor="#D9B85C" />
                <stop offset="1" stopColor="#9C7A2D" />
            </linearGradient>
        </defs>
        <rect x="0.75" y="0.75" width="44.5" height="32.5" rx="5.25" fill={`url(#chip-grad-${uid})`} stroke="rgba(0,0,0,0.35)" strokeWidth="1.5" />
        {/* Garis kontak khas chip EMV */}
        <path
            d="M15 1V33M31 1V33M1 11.5H15M31 11.5H45M1 22.5H15M31 22.5H45M15 17H31"
            stroke="rgba(85,60,12,0.55)"
            strokeWidth="1.4"
            strokeLinecap="round"
        />
        <rect x="15" y="11.5" width="16" height="11" rx="1.5" fill="rgba(255,255,255,0.20)" stroke="rgba(85,60,12,0.45)" strokeWidth="1" />
    </svg>
);

/** Ikon contactless / gelombang NFC (SVG inline) */
const ContactlessIcon = () => (
    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" aria-hidden="true" className="shrink-0 text-white/95 drop-shadow-[0_1px_2px_rgba(0,0,0,0.45)]">
        <path d="M6.5 9.2a4.2 4.2 0 0 1 0 5.6" />
        <path d="M10.2 6.2a8.8 8.8 0 0 1 0 11.6" />
        <path d="M13.9 3.2a13.4 13.4 0 0 1 0 17.6" />
    </svg>
);

/**
 * Kartu debit dengan desain mengikuti template public/card.png
 * (background navy circuit, logo A2U BANK sudah menyatu di gambar).
 *
 * Props:
 * - card: objek kartu dari Inertia props (card_number_masked, expiry_date, status, user.full_name, dll.)
 * - revealedNumber: (opsional) nomor kartu penuh hasil reveal — ditampilkan sementara di kartu
 */
const DebitCard = ({ card, revealedNumber = null }) => {
    if (!card) return null;

    const formatExpiryDate = (dateString) => {
        if (!dateString) return '--/--';
        const date = new Date(dateString);
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = String(date.getFullYear()).slice(-2);
        return `${month}/${year}`;
    };

    const status = (card.status || 'active').toLowerCase();
    const isBlocked = status === 'blocked';
    const isClosed = status === 'closed';

    // Nomor ditampilkan: hasil reveal (penuh) atau masked dari API
    const rawNumber = revealedNumber || card.card_number_masked || '****-****-****-****';
    const displayNumber = String(rawNumber).replace(/\s+/g, '-');

    const cardTypeLabel = card.card_type
        ? card.card_type.charAt(0).toUpperCase() + card.card_type.slice(1).toLowerCase()
        : 'Debit';

    return (
        <div className="relative w-full aspect-[1.586/1] rounded-2xl overflow-hidden shadow-xl ring-1 ring-white/15 select-none">
            {/* Layer background: template kartu A2U */}
            <div
                className={`absolute inset-0 transition ${isBlocked ? 'brightness-[0.62] saturate-[0.55]' : ''} ${isClosed ? 'grayscale brightness-[0.45]' : ''}`}
            >
                <img
                    src="/card.png"
                    alt=""
                    aria-hidden="true"
                    className="h-full w-full object-cover"
                    style={{ objectPosition: 'center 22%' }}
                />
            </div>

            {/* Gradient overlay agar teks tetap terbaca */}
            <div className="absolute inset-0 bg-gradient-to-b from-black/10 via-black/25 to-black/60" />

            {/* Tint status: blokir merah / tutup gelap */}
            {isBlocked && <div className="absolute inset-0 bg-red-950/35" />}
            {isClosed && <div className="absolute inset-0 bg-slate-950/45" />}

            {/* Badge status */}
            {(isBlocked || isClosed) && (
                <div
                    className={`absolute left-1/2 top-3 -translate-x-1/2 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] shadow-lg ${isBlocked ? 'bg-red-600/90 text-white' : 'bg-slate-700/90 text-slate-200'
                        }`}
                >
                    {isBlocked ? 'Diblokir' : 'Ditutup'}
                </div>
            )}

            {/* Konten kartu */}
            <div className="relative flex h-full flex-col justify-between p-4 sm:p-5">
                {/* Baris atas: label "Debit" di kanan (logo sudah ada di background) */}
                <div className="flex items-start justify-end">
                    <span className="text-xs sm:text-sm font-bold uppercase tracking-[0.22em] text-white/90 drop-shadow-[0_1px_2px_rgba(0,0,0,0.6)]">
                        {cardTypeLabel}
                    </span>
                </div>

                {/* Baris tengah: chip EMV (kiri) + contactless (kanan) */}
                <div className="flex items-center justify-between">
                    {/* <ChipIcon uid={card.id} /> */}
                    {/* <ContactlessIcon /> */}
                </div>

                {/* Nomor kartu — efek embossed silver/metallic.
                    Diposisikan absolut di ~60% tinggi kartu (turun dari 56% agar lega dari chip,
                    tetapi masih di atas baris "Pemegang Kartu / Berlaku s/d"). */}
                <p
                    className="absolute left-4 right-4 sm:left-5 sm:right-5 top-[60%] font-mono text-lg sm:text-xl font-bold tracking-[0.15em] text-white"
                    style={{
                        textShadow:
                            '0 1px 0 rgba(0,0,0,0.9), 0 2px 4px rgba(0,0,0,0.55), 0 0 1px rgba(255,255,255,0.45)',
                    }}
                >
                    {displayNumber}
                </p>

                {/* Baris bawah: pemegang kartu, masa berlaku, URL bank */}
                <div className="flex items-end justify-between gap-2 text-white">
                    <div className="min-w-0">
                        <p className="text-[10px] uppercase tracking-wider text-white/70">Pemegang Kartu</p>
                        <p className="truncate text-xs sm:text-sm font-semibold drop-shadow-[0_1px_2px_rgba(0,0,0,0.6)]">
                            {card.user?.full_name || '-'}
                        </p>
                    </div>
                    <div className="shrink-0 text-center">
                        <p className="text-[10px] uppercase tracking-wider text-white/70">Berlaku s/d</p>
                        <p className="text-xs sm:text-sm font-semibold drop-shadow-[0_1px_2px_rgba(0,0,0,0.6)]">
                            {formatExpiryDate(card.expiry_date)}
                        </p>
                    </div>
                    <div className="shrink-0 text-right">
                        <p className="text-[10px] sm:text-xs font-medium tracking-wide text-white/85 drop-shadow-[0_1px_2px_rgba(0,0,0,0.6)]">
                            a2ubank.my.id
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default DebitCard;
