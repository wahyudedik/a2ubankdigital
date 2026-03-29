import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function GoalSavingsPage({ auth }) {
    const [goals, setGoals] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showDepositModal, setShowDepositModal] = useState(false);
    const [selectedGoal, setSelectedGoal] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Create form state
    const [formData, setFormData] = useState({
        goal_name: '',
        goal_amount: '',
        target_date: '',
        initial_deposit: '',
        autodebit_enabled: false,
        autodebit_day: '1',
        autodebit_amount: ''
    });

    // Deposit form state
    const [depositAmount, setDepositAmount] = useState('');

    useEffect(() => {
        fetchGoals();
    }, []);

    const fetchGoals = async () => {
        try {
            setLoading(true);
            const response = await fetch('/user/goal-savings', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setGoals(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch goals:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleCreate = async (e) => {
        e.preventDefault();
        setProcessing(true);

        try {
            const response = await fetch('/user/goal-savings', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify(formData)
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert('Tabungan berjangka berhasil dibuat!');
                setShowCreateModal(false);
                resetForm();
                fetchGoals();
            } else {
                alert(data.message || 'Gagal membuat tabungan berjangka');
            }
        } catch (error) {
            console.error('Create failed:', error);
            alert('Terjadi kesalahan');
        } finally {
            setProcessing(false);
        }
    };

    const handleDeposit = async (e) => {
        e.preventDefault();
        setProcessing(true);

        try {
            const response = await fetch(`/user/goal-savings/${selectedGoal.id}/deposit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({ amount: parseFloat(depositAmount) })
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert('Setoran berhasil!');
                setShowDepositModal(false);
                setDepositAmount('');
                fetchGoals();
            } else {
                alert(data.message || 'Setoran gagal');
            }
        } catch (error) {
            console.error('Deposit failed:', error);
            alert('Terjadi kesalahan');
        } finally {
            setProcessing(false);
        }
    };

    const handleDelete = async (goalId, goalName) => {
        if (!confirm(`Yakin ingin menutup tabungan "${goalName}"? Saldo akan dikembalikan ke rekening utama.`)) {
            return;
        }

        try {
            const response = await fetch(`/user/goal-savings/${goalId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                }
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert('Tabungan berhasil ditutup');
                fetchGoals();
            } else {
                alert(data.message || 'Gagal menutup tabungan');
            }
        } catch (error) {
            console.error('Delete failed:', error);
            alert('Terjadi kesalahan');
        }
    };

    const resetForm = () => {
        setFormData({
            goal_name: '',
            goal_amount: '',
            target_date: '',
            initial_deposit: '',
            autodebit_enabled: false,
            autodebit_day: '1',
            autodebit_amount: ''
        });
    };

    const openDepositModal = (goal) => {
        setSelectedGoal(goal);
        setShowDepositModal(true);
        setDepositAmount('');
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
            month: 'long',
            year: 'numeric'
        });
    };

    const getDaysRemainingText = (days) => {
        if (days < 0) return 'Melewati target';
        if (days === 0) return 'Hari ini';
        if (days === 1) return 'Besok';
        return `${days} hari lagi`;
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Tabungan Berjangka" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Tabungan Berjangka</h1>
                            <p className="text-sm text-gray-600 mt-1">Wujudkan impian Anda dengan menabung terencana</p>
                        </div>
                        <button
                            onClick={() => setShowCreateModal(true)}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            + Buat Tabungan Baru
                        </button>
                    </div>

                    {/* Goals List */}
                    {loading ? (
                        <div className="text-center py-12">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <p className="text-gray-600 mt-2">Memuat data...</p>
                        </div>
                    ) : goals.length === 0 ? (
                        <div className="bg-white rounded-lg shadow p-12 text-center">
                            <div className="text-6xl mb-4">🎯</div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Belum Ada Tabungan Berjangka</h3>
                            <p className="text-gray-600 mb-6">Mulai wujudkan impian Anda dengan membuat tabungan berjangka</p>
                            <button
                                onClick={() => setShowCreateModal(true)}
                                className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                Buat Sekarang
                            </button>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {goals.map(goal => (
                                <div key={goal.id} className="bg-white rounded-lg shadow-lg overflow-hidden">
                                    {/* Header */}
                                    <div className={`p-6 ${goal.is_achieved ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-blue-500 to-blue-600'} text-white`}>
                                        <div className="flex justify-between items-start mb-4">
                                            <div className="flex-1">
                                                <h3 className="text-xl font-bold mb-1">{goal.goal_name}</h3>
                                                <p className="text-sm opacity-90">Target: {formatDate(goal.target_date)}</p>
                                            </div>
                                            <div className="text-3xl">
                                                {goal.is_achieved ? '🎉' : '🎯'}
                                            </div>
                                        </div>

                                        {/* Progress Bar */}
                                        <div className="mb-2">
                                            <div className="flex justify-between text-sm mb-1">
                                                <span>Progress</span>
                                                <span className="font-semibold">{goal.progress_percentage.toFixed(1)}%</span>
                                            </div>
                                            <div className="w-full bg-white bg-opacity-30 rounded-full h-3">
                                                <div
                                                    className="bg-white h-3 rounded-full transition-all duration-500"
                                                    style={{ width: `${Math.min(goal.progress_percentage, 100)}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Body */}
                                    <div className="p-6">
                                        {/* Amounts */}
                                        <div className="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <div className="text-xs text-gray-600 mb-1">Saldo Saat Ini</div>
                                                <div className="text-lg font-bold text-gray-900">
                                                    {formatCurrency(goal.current_balance)}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-gray-600 mb-1">Target</div>
                                                <div className="text-lg font-bold text-blue-600">
                                                    {formatCurrency(goal.goal_amount)}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Remaining */}
                                        {!goal.is_achieved && (
                                            <div className="bg-gray-50 rounded-lg p-3 mb-4">
                                                <div className="flex justify-between items-center">
                                                    <div>
                                                        <div className="text-xs text-gray-600">Kekurangan</div>
                                                        <div className="font-semibold text-gray-900">
                                                            {formatCurrency(goal.remaining_amount)}
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="text-xs text-gray-600">Waktu Tersisa</div>
                                                        <div className="font-semibold text-gray-900">
                                                            {getDaysRemainingText(goal.days_remaining)}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Autodebit Info */}
                                        {goal.autodebit_enabled && (
                                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                                <div className="flex items-center gap-2 text-sm text-blue-800">
                                                    <span>🔄</span>
                                                    <span>
                                                        Autodebit: {formatCurrency(goal.autodebit_amount)} setiap tanggal {goal.autodebit_day}
                                                    </span>
                                                </div>
                                            </div>
                                        )}

                                        {/* Actions */}
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => openDepositModal(goal)}
                                                className="flex-1 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                                                disabled={goal.is_achieved}
                                            >
                                                💰 Setor
                                            </button>
                                            <button
                                                onClick={() => handleDelete(goal.id, goal.goal_name)}
                                                className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Info Box */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
                        <h4 className="font-medium text-blue-900 mb-2">ℹ️ Tentang Tabungan Berjangka</h4>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• Tetapkan target dan tanggal pencapaian</li>
                            <li>• Aktifkan autodebit untuk menabung otomatis setiap bulan</li>
                            <li>• Tidak ada biaya admin atau penalti penarikan</li>
                            <li>• Saldo dapat ditarik kapan saja</li>
                        </ul>
                    </div>
                </div>
            </div>

            {/* Create Modal */}
            {showCreateModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 overflow-y-auto">
                    <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full my-8">
                        <div className="flex justify-between items-center p-6 border-b">
                            <h2 className="text-xl font-bold">Buat Tabungan Berjangka</h2>
                            <button
                                onClick={() => setShowCreateModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleCreate} className="p-6">
                            <div className="space-y-4">
                                {/* Goal Name */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Nama Tujuan
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.goal_name}
                                        onChange={(e) => setFormData({ ...formData, goal_name: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Contoh: Liburan ke Bali, Beli Laptop, dll"
                                        required
                                    />
                                </div>

                                {/* Goal Amount & Target Date */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Target Jumlah
                                        </label>
                                        <input
                                            type="number"
                                            value={formData.goal_amount}
                                            onChange={(e) => setFormData({ ...formData, goal_amount: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="Minimal 100.000"
                                            min="100000"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Target Tanggal
                                        </label>
                                        <input
                                            type="date"
                                            value={formData.target_date}
                                            onChange={(e) => setFormData({ ...formData, target_date: e.target.value })}
                                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            min={new Date().toISOString().split('T')[0]}
                                            required
                                        />
                                    </div>
                                </div>

                                {/* Initial Deposit */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Setoran Awal
                                    </label>
                                    <input
                                        type="number"
                                        value={formData.initial_deposit}
                                        onChange={(e) => setFormData({ ...formData, initial_deposit: e.target.value })}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Minimal 10.000"
                                        min="10000"
                                        required
                                    />
                                </div>

                                {/* Autodebit */}
                                <div className="border-t pt-4">
                                    <div className="flex items-center mb-4">
                                        <input
                                            type="checkbox"
                                            id="autodebit"
                                            checked={formData.autodebit_enabled}
                                            onChange={(e) => setFormData({ ...formData, autodebit_enabled: e.target.checked })}
                                            className="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                        />
                                        <label htmlFor="autodebit" className="ml-2 text-sm font-medium text-gray-700">
                                            Aktifkan Autodebit (Setoran Otomatis)
                                        </label>
                                    </div>

                                    {formData.autodebit_enabled && (
                                        <div className="grid grid-cols-2 gap-4 pl-6">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Tanggal Setiap Bulan
                                                </label>
                                                <select
                                                    value={formData.autodebit_day}
                                                    onChange={(e) => setFormData({ ...formData, autodebit_day: e.target.value })}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                >
                                                    {Array.from({ length: 28 }, (_, i) => i + 1).map(day => (
                                                        <option key={day} value={day}>Tanggal {day}</option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Jumlah Autodebit
                                                </label>
                                                <input
                                                    type="number"
                                                    value={formData.autodebit_amount}
                                                    onChange={(e) => setFormData({ ...formData, autodebit_amount: e.target.value })}
                                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    placeholder="Minimal 10.000"
                                                    min="10000"
                                                    required={formData.autodebit_enabled}
                                                />
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex gap-3 mt-6">
                                <button
                                    type="button"
                                    onClick={() => setShowCreateModal(false)}
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
                                    {processing ? 'Memproses...' : 'Buat Tabungan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Deposit Modal */}
            {showDepositModal && selectedGoal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div className="flex justify-between items-center p-6 border-b">
                            <h2 className="text-xl font-bold">Setor Tabungan</h2>
                            <button
                                onClick={() => setShowDepositModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handleDeposit} className="p-6">
                            {/* Goal Info */}
                            <div className="bg-gray-50 rounded-lg p-4 mb-4">
                                <div className="font-semibold text-gray-900 mb-2">{selectedGoal.goal_name}</div>
                                <div className="text-sm text-gray-600">
                                    Saldo: {formatCurrency(selectedGoal.current_balance)} / {formatCurrency(selectedGoal.goal_amount)}
                                </div>
                            </div>

                            {/* Amount Input */}
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Jumlah Setoran
                                </label>
                                <input
                                    type="number"
                                    value={depositAmount}
                                    onChange={(e) => setDepositAmount(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Minimal 10.000"
                                    min="10000"
                                    required
                                />
                            </div>

                            {/* Actions */}
                            <div className="flex gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowDepositModal(false)}
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
                                    {processing ? 'Memproses...' : 'Setor'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
