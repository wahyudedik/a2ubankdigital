import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function LoyaltyPointsPage({ auth }) {
    const [pointsData, setPointsData] = useState(null);
    const [rewards, setRewards] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showRedeemModal, setShowRedeemModal] = useState(false);
    const [selectedReward, setSelectedReward] = useState(null);
    const [redeemPoints, setRedeemPoints] = useState('');
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        fetchPointsData();
        fetchRewards();
    }, []);

    const fetchPointsData = async () => {
        try {
            const response = await fetch('/user/loyalty/points', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setPointsData(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch points:', error);
        } finally {
            setLoading(false);
        }
    };

    const fetchRewards = async () => {
        try {
            const response = await fetch('/user/loyalty/rewards', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setRewards(data.data.available_rewards);
            }
        } catch (error) {
            console.error('Failed to fetch rewards:', error);
        }
    };

    const handleRedeem = async (e) => {
        e.preventDefault();
        const points = parseInt(redeemPoints);

        if (points < selectedReward.min_points) {
            alert(`Minimal ${selectedReward.min_points.toLocaleString('id-ID')} poin`);
            return;
        }

        if (points > pointsData.current_balance) {
            alert('Poin tidak mencukupi');
            return;
        }

        setProcessing(true);
        try {
            const response = await fetch('/user/loyalty/redeem', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({
                    points: points,
                    reward_type: selectedReward.type
                })
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert(`Berhasil! Kode reward: ${data.data.reward_code}`);
                setShowRedeemModal(false);
                setRedeemPoints('');
                fetchPointsData();
            } else {
                alert(data.message || 'Gagal menukar poin');
            }
        } catch (error) {
            console.error('Redeem failed:', error);
            alert('Terjadi kesalahan saat menukar poin');
        } finally {
            setProcessing(false);
        }
    };

    const openRedeemModal = (reward) => {
        setSelectedReward(reward);
        setRedeemPoints(reward.min_points.toString());
        setShowRedeemModal(true);
    };

    const calculateRewardValue = (points, rate) => {
        return points * rate;
    };

    const formatCurrency = (value) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(value);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getRewardIcon = (type) => {
        switch (type) {
            case 'CASHBACK': return '💰';
            case 'DISCOUNT_VOUCHER': return '🎫';
            case 'GIFT_VOUCHER': return '🎁';
            default: return '⭐';
        }
    };

    if (loading) {
        return (
            <AuthenticatedLayout user={auth.user}>
                <Head title="Poin Loyalitas" />
                <div className="py-12 text-center">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p className="text-gray-600 mt-2">Memuat data...</p>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Poin Loyalitas" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Poin Loyalitas</h1>
                        <p className="text-sm text-gray-600 mt-1">Kumpulkan poin dan tukar dengan reward menarik</p>
                    </div>

                    {/* Points Summary */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div className="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                            <div className="text-sm opacity-90 mb-1">Saldo Poin</div>
                            <div className="text-3xl font-bold">{pointsData?.current_balance?.toLocaleString('id-ID') || 0}</div>
                            <div className="text-xs opacity-75 mt-2">⭐ Poin Aktif</div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-sm text-gray-600 mb-1">Total Diperoleh</div>
                            <div className="text-2xl font-bold text-green-600">
                                {pointsData?.total_earned?.toLocaleString('id-ID') || 0}
                            </div>
                            <div className="text-xs text-gray-500 mt-2">📈 Sepanjang Waktu</div>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-sm text-gray-600 mb-1">Total Ditukar</div>
                            <div className="text-2xl font-bold text-orange-600">
                                {pointsData?.total_redeemed?.toLocaleString('id-ID') || 0}
                            </div>
                            <div className="text-xs text-gray-500 mt-2">🎁 Reward Ditukar</div>
                        </div>
                    </div>

                    {/* Available Rewards */}
                    <div className="bg-white rounded-lg shadow mb-6">
                        <div className="p-6 border-b">
                            <h2 className="text-lg font-semibold">Tukar Poin</h2>
                            <p className="text-sm text-gray-600 mt-1">Pilih reward yang ingin Anda tukar</p>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {rewards.map(reward => (
                                    <div
                                        key={reward.type}
                                        className={`border-2 rounded-lg p-4 transition ${reward.is_available
                                                ? 'border-blue-200 hover:border-blue-400 cursor-pointer'
                                                : 'border-gray-200 opacity-50'
                                            }`}
                                        onClick={() => reward.is_available && openRedeemModal(reward)}
                                    >
                                        <div className="text-4xl mb-3">{getRewardIcon(reward.type)}</div>
                                        <h3 className="font-semibold text-gray-900 mb-1">{reward.name}</h3>
                                        <p className="text-xs text-gray-600 mb-3">{reward.description}</p>
                                        <div className="bg-gray-50 rounded p-2 mb-3">
                                            <div className="text-xs text-gray-600">Minimal Poin</div>
                                            <div className="font-bold text-blue-600">
                                                {reward.min_points.toLocaleString('id-ID')} poin
                                            </div>
                                            <div className="text-xs text-gray-500 mt-1">
                                                Rate: 1 poin = {formatCurrency(reward.rate)}
                                            </div>
                                        </div>
                                        {!reward.is_available && (
                                            <div className="text-xs text-red-600 font-medium">
                                                ⚠️ Poin tidak mencukupi
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Points History */}
                    <div className="bg-white rounded-lg shadow">
                        <div className="p-6 border-b">
                            <h2 className="text-lg font-semibold">Riwayat Poin</h2>
                        </div>
                        <div className="divide-y">
                            {pointsData?.points_history?.length === 0 ? (
                                <div className="p-8 text-center text-gray-500">
                                    Belum ada riwayat poin
                                </div>
                            ) : (
                                pointsData?.points_history?.map(history => (
                                    <div key={history.id} className="p-4 hover:bg-gray-50">
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <div className="font-medium text-gray-900">
                                                    {history.description}
                                                </div>
                                                <div className="text-xs text-gray-500 mt-1">
                                                    {formatDate(history.created_at)}
                                                </div>
                                            </div>
                                            <div className={`text-lg font-bold ${history.points > 0 ? 'text-green-600' : 'text-red-600'
                                                }`}>
                                                {history.points > 0 ? '+' : ''}{history.points.toLocaleString('id-ID')}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    {/* Info Box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <h4 className="font-medium text-blue-900 mb-2">ℹ️ Cara Mendapatkan Poin</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Setiap transaksi transfer = 1 poin per Rp 10.000</li>
                            <li>• Pembayaran tagihan = 5 poin per transaksi</li>
                            <li>• Pembelian produk digital = 3 poin per transaksi</li>
                            <li>• Poin berlaku selama 1 tahun sejak diperoleh</li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Redeem Modal */}
            {showRedeemModal && selectedReward && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div className="flex justify-between items-center p-6 border-b">
                            <h2 className="text-xl font-bold">Tukar Poin</h2>
                            <button
                                onClick={() => setShowRedeemModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleRedeem} className="p-6">
                            {/* Reward Info */}
                            <div className="bg-gray-50 rounded-lg p-4 mb-4 text-center">
                                <div className="text-5xl mb-2">{getRewardIcon(selectedReward.type)}</div>
                                <div className="font-semibold text-gray-900">{selectedReward.name}</div>
                                <div className="text-sm text-gray-600 mt-1">{selectedReward.description}</div>
                            </div>

                            {/* Points Input */}
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Jumlah Poin
                                </label>
                                <input
                                    type="number"
                                    value={redeemPoints}
                                    onChange={(e) => setRedeemPoints(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    min={selectedReward.min_points}
                                    max={pointsData.current_balance}
                                    required
                                />
                                <p className="text-xs text-gray-500 mt-1">
                                    Minimal: {selectedReward.min_points.toLocaleString('id-ID')} poin
                                </p>
                            </div>

                            {/* Reward Value */}
                            {redeemPoints && (
                                <div className="bg-blue-50 rounded-lg p-3 mb-4">
                                    <div className="text-sm text-gray-700">Nilai Reward</div>
                                    <div className="text-2xl font-bold text-blue-600">
                                        {formatCurrency(calculateRewardValue(parseInt(redeemPoints), selectedReward.rate))}
                                    </div>
                                </div>
                            )}

                            {/* Current Balance */}
                            <div className="text-sm text-gray-600 mb-4">
                                Saldo poin Anda: <span className="font-semibold">{pointsData.current_balance.toLocaleString('id-ID')}</span>
                            </div>

                            {/* Actions */}
                            <div className="flex gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowRedeemModal(false)}
                                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                    disabled={processing}
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                                    disabled={processing}
                                >
                                    {processing ? 'Memproses...' : 'Tukar'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
