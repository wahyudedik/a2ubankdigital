import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Button from '@/components/ui/Button';
import Input from '@/components/ui/Input';
import { ArrowLeft, PlusCircle, Lock, Unlock, Edit, Loader2 } from 'lucide-react';
import DebitCard from '@/components/customer/DebitCard';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
const ActionButton = ({ onClick, icon, children, className = '' }) => (<button onClick={onClick} className={`flex-1 flex items-center justify-center gap-2 py-3 text-sm font-semibold transition-colors ${className}`}>{icon}<span>{children}</span></button>);

const CardsPage = () => {
    const { cards: initialCards, accounts: initialAccounts } = usePage().props;
    const { loading, error, callApi } = useApi();
    const modal = useModal();
    const [isRequesting, setIsRequesting] = useState(false);
    const [selectedAccountId, setSelectedAccountId] = useState(String((initialAccounts || [])[0]?.id || ''));
    const [isLimitModalOpen, setLimitModalOpen] = useState(false);
    const [selectedCard, setSelectedCard] = useState(null);
    const [newLimit, setNewLimit] = useState('');

    const cards = initialCards || [];
    const accounts = initialAccounts || [];

    const handleRequestCard = async () => {
        const result = await callApi('/user/cards/request', 'POST', { card_type: 'DEBIT', delivery_address: 'Alamat pengiriman', reason: 'Pengajuan kartu baru' });
        if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); setIsRequesting(false); router.reload(); }
        else { modal.showAlert({ title: 'Gagal', message: error || result?.message || 'Terjadi kesalahan.', type: 'warning' }); }
    };

    const handleUpdateStatus = async (cardId, currentStatus) => {
        const newStatus = currentStatus === 'blocked' ? 'active' : 'blocked';
        const actionText = newStatus === 'active' ? 'membuka blokir' : 'memblokir';
        const confirmed = await modal.showConfirmation({ title: `Konfirmasi ${actionText} kartu`, message: `Apakah Anda yakin ingin ${actionText} kartu ini?`, confirmText: `Ya, ${actionText}` });
        if (confirmed) { const result = await callApi(`/user/cards/${cardId}/status`, 'PUT', { status: newStatus }); if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); router.reload(); } }
    };

    const openLimitModal = (card) => { setSelectedCard(card); setNewLimit(String(card.daily_limit)); setLimitModalOpen(true); };
    const handleSetLimit = async (e) => {
        e.preventDefault();
        const result = await callApi(`/user/cards/${selectedCard.id}/limit`, 'PUT', { daily_limit: parseInt(newLimit, 10) });
        if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); setLimitModalOpen(false); router.reload(); }
    };

    return (
        <div>
            <Link href="/profile" className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"><ArrowLeft size={20} /><h1 className="text-2xl font-bold text-gray-800">Manajemen Kartu</h1></Link>
            <div className="space-y-8">
                {cards.map(card => (
                    <div key={card.id} className="max-w-sm mx-auto">
                        <DebitCard card={card} />
                        <div className="bg-white -mt-2 rounded-b-xl shadow-lg flex justify-around items-center border-t">
                            <ActionButton onClick={() => openLimitModal(card)} icon={<Edit size={16} />} className="text-gray-600 hover:bg-gray-100 rounded-bl-xl">Limit</ActionButton>
                            <ActionButton onClick={() => handleUpdateStatus(card.id, card.status)} icon={card.status === 'blocked' ? <Unlock size={16} /> : <Lock size={16} />} className={card.status === 'blocked' ? 'text-green-600 hover:bg-green-50' : 'text-red-600 hover:bg-red-50 rounded-br-xl'}>{card.status === 'blocked' ? 'Buka Blokir' : 'Blokir'}</ActionButton>
                        </div>
                    </div>
                ))}
            </div>
            <div className="mt-10 max-w-sm mx-auto">
                {!isRequesting ? (
                    <Button onClick={() => setIsRequesting(true)} fullWidth className="py-2.5 bg-gray-700 hover:bg-gray-800"><PlusCircle size={20} className="mr-2" /> Ajukan Kartu Baru</Button>
                ) : (
                    <div className="bg-white p-4 rounded-lg shadow-md border">
                        <h3 className="font-semibold mb-2 text-center text-gray-800">Pilih Rekening untuk Kartu Baru</h3>
                        <select value={selectedAccountId} onChange={(e) => setSelectedAccountId(e.target.value)} className="w-full px-4 py-2 mb-4 text-gray-800 bg-gray-50 border border-gray-300 rounded-lg">
                            {accounts.map(acc => <option key={acc.id} value={String(acc.id)}>{acc.account_number} - {formatCurrency(acc.balance)}</option>)}
                        </select>
                        <div className="flex gap-2"><Button onClick={() => setIsRequesting(false)} className="bg-gray-200 text-gray-800 w-1/3">Batal</Button><Button onClick={handleRequestCard} fullWidth disabled={loading}>{loading ? 'Memproses...' : 'Kirim Pengajuan'}</Button></div>
                    </div>
                )}
            </div>
            {isLimitModalOpen && selectedCard && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
                    <div className="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
                        <h2 className="text-lg font-bold mb-4">Ubah Limit Harian</h2>
                        <form onSubmit={handleSetLimit}><Input name="new_limit" type="number" label="Limit Baru (Rp)" value={newLimit} onChange={(e) => setNewLimit(e.target.value)} required /><div className="mt-6 flex justify-end gap-2"><Button type="button" onClick={() => setLimitModalOpen(false)} className="bg-gray-200 text-gray-800">Batal</Button><Button type="submit" disabled={loading}>{loading ? 'Menyimpan...' : 'Simpan'}</Button></div></form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CardsPage;
