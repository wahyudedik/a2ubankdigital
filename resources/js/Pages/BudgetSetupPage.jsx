import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Edit, Trash2, Save, X, AlertTriangle, Check } from 'lucide-react';
import axios from 'axios';

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
};

const BudgetSetupPage = () => {
    const [categories, setCategories] = useState([]);
    const [budgets, setBudgets] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingBudget, setEditingBudget] = useState(null);

    const [currentMonth, setCurrentMonth] = useState(new Date().getMonth() + 1);
    const [currentYear, setCurrentYear] = useState(new Date().getFullYear());

    // Form state
    const [formData, setFormData] = useState({
        category_id: '',
        budget_amount: '',
        alert_threshold: 80,
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        fetchCategories();
        fetchBudgets();
    }, [currentMonth, currentYear]);

    const fetchCategories = async () => {
        try {
            const response = await axios.get('/ajax/user/expense/categories');
            setCategories(response.data.data);
        } catch (error) {
            console.error('Error fetching categories:', error);
        }
    };

    const fetchBudgets = async () => {
        setLoading(true);
        try {
            const response = await axios.get('/ajax/user/expense/budgets', {
                params: { year: currentYear, month: currentMonth }
            });
            setBudgets(response.data.data);
        } catch (error) {
            console.error('Error fetching budgets:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setFormErrors({});

        try {
            await axios.post('/ajax/user/expense/budgets', {
                ...formData,
                year: currentYear,
                month: currentMonth,
            });
            setShowModal(false);
            setEditingBudget(null);
            resetForm();
            fetchBudgets();
        } catch (error) {
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            }
        } finally {
            setSubmitting(false);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Apakah Anda yakin ingin menghapus budget ini?')) return;

        try {
            await axios.delete(`/ajax/user/expense/budgets/${id}`);
            fetchBudgets();
        } catch (error) {
            console.error('Error deleting budget:', error);
        }
    };

    const handleEdit = (budget) => {
        setEditingBudget(budget);
        setFormData({
            category_id: budget.category_id,
            budget_amount: budget.budget_amount,
            alert_threshold: budget.alert_threshold || 80,
        });
        setShowModal(true);
    };

    const resetForm = () => {
        setFormData({
            category_id: '',
            budget_amount: '',
            alert_threshold: 80,
        });
        setFormErrors({});
    };

    const getMonthName = (month) => {
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return months[month - 1];
    };

    const getBudgetForCategory = (categoryId) => {
        return budgets.find(b => b.category_id === categoryId);
    };

    const categoriesWithoutBudget = categories.filter(cat => !getBudgetForCategory(cat.id));

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Atur Budget</h1>
                    <p className="text-gray-500 text-sm">Tentukan batas pengeluaran per kategori</p>
                </div>
            </div>

            {/* Month Selector */}
            <div className="bg-white rounded-xl shadow-md p-4">
                <div className="flex justify-center items-center gap-4">
                    <button
                        onClick={() => {
                            if (currentMonth === 1) {
                                setCurrentMonth(12);
                                setCurrentYear(currentYear - 1);
                            } else {
                                setCurrentMonth(currentMonth - 1);
                            }
                        }}
                        className="p-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        ←
                    </button>
                    <div className="text-center">
                        <p className="font-semibold text-lg text-gray-800">{getMonthName(currentMonth)} {currentYear}</p>
                        <p className="text-sm text-gray-500">Total Budget: {formatCurrency(budgets.reduce((sum, b) => sum + parseFloat(b.budget_amount), 0))}</p>
                    </div>
                    <button
                        onClick={() => {
                            if (currentMonth === 12) {
                                setCurrentMonth(1);
                                setCurrentYear(currentYear + 1);
                            } else {
                                setCurrentMonth(currentMonth + 1);
                            }
                        }}
                        className="p-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        →
                    </button>
                </div>
            </div>

            {/* Budget List */}
            <div className="bg-white rounded-xl shadow-md">
                <div className="p-4 border-b border-gray-100">
                    <h3 className="font-semibold text-gray-800">Budget per Kategori</h3>
                </div>

                {loading ? (
                    <div className="p-8 text-center">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-bpn-blue mx-auto mb-4"></div>
                        <p className="text-gray-500">Memuat data...</p>
                    </div>
                ) : budgets.length === 0 ? (
                    <div className="p-8 text-center">
                        <AlertTriangle className="mx-auto text-gray-300 mb-2" size={48} />
                        <p className="text-gray-500">Belum ada budget yang diatur untuk bulan ini</p>
                        <button
                            onClick={() => setShowModal(true)}
                            className="mt-2 text-bpn-blue text-sm font-semibold hover:underline"
                        >
                            Atur Budget Sekarang
                        </button>
                    </div>
                ) : (
                    <div className="divide-y divide-gray-100">
                        {budgets.map((budget) => {
                            const spent = parseFloat(budget.total_spent) || 0;
                            const budgetAmount = parseFloat(budget.budget_amount) || 0;
                            const remaining = parseFloat(budget.remaining) || 0;
                            const usagePercentage = parseFloat(budget.usage_percentage) || 0;
                            const isOverBudget = spent > budgetAmount;
                            const shouldAlert = usagePercentage >= (budget.alert_threshold || 80);

                            return (
                                <div key={budget.id} className="p-4 hover:bg-gray-50 transition-colors">
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full flex items-center justify-center text-lg" style={{ backgroundColor: (budget.category?.color || '#95A5A6') + '20' }}>
                                                {budget.category?.icon || '📦'}
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-800">{budget.category?.name}</p>
                                                <p className="text-xs text-gray-500">
                                                    Alert: {budget.alert_threshold || 80}%
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <div className="text-right">
                                                <p className={`font-semibold ${isOverBudget ? 'text-red-600' : 'text-gray-800'}`}>
                                                    {formatCurrency(spent)} / {formatCurrency(budgetAmount)}
                                                </p>
                                                <p className={`text-xs ${isOverBudget ? 'text-red-500' : shouldAlert ? 'text-yellow-500' : 'text-gray-500'}`}>
                                                    {isOverBudget ? 'Melebihi budget!' : shouldAlert ? 'Mendekati batas' : `Sisa ${formatCurrency(remaining)}`}
                                                </p>
                                            </div>
                                            <div className="flex gap-1">
                                                <button
                                                    onClick={() => handleEdit(budget)}
                                                    className="p-1 text-gray-400 hover:text-blue-600 transition-colors"
                                                >
                                                    <Edit size={16} />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(budget.id)}
                                                    className="p-1 text-gray-400 hover:text-red-600 transition-colors"
                                                >
                                                    <Trash2 size={16} />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div className="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            className={`h-2 rounded-full ${isOverBudget ? 'bg-red-500' : shouldAlert ? 'bg-yellow-500' : 'bg-green-500'}`}
                                            style={{ width: `${Math.min(usagePercentage, 100)}%` }}
                                        ></div>
                                    </div>
                                    <div className="flex justify-between mt-1">
                                        <span className="text-xs text-gray-500">{usagePercentage}% terpakai</span>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* Add Budget Button */}
                {categoriesWithoutBudget.length > 0 && (
                    <div className="p-4 border-t border-gray-100">
                        <button
                            onClick={() => setShowModal(true)}
                            className="w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-500 hover:border-bpn-blue hover:text-bpn-blue transition-colors flex items-center justify-center gap-2"
                        >
                            <Plus size={18} />
                            Tambah Budget
                        </button>
                    </div>
                )}
            </div>

            {/* Tips */}
            <div className="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h4 className="font-semibold text-blue-800 mb-2">💡 Tips Budgeting</h4>
                <ul className="text-sm text-blue-700 space-y-1">
                    <li>• Atur budget realistis berdasarkan pengeluaran bulan lalu</li>
                    <li>• Gunakan aturan 50/30/20: 50% kebutuhan, 30% keinginan, 20% tabungan</li>
                    <li>• Review budget setiap minggu untuk tetap on track</li>
                    <li>• Naikkan budget secara bertahap jika sudah konsisten</li>
                </ul>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md">
                        <div className="flex justify-between items-center p-4 border-b border-gray-200">
                            <h3 className="font-semibold text-lg text-gray-800">
                                {editingBudget ? 'Edit Budget' : 'Tambah Budget'}
                            </h3>
                            <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600">
                                <X size={20} />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-4 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                                <select
                                    value={formData.category_id}
                                    onChange={(e) => setFormData({ ...formData, category_id: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                                    required
                                    disabled={editingBudget}
                                >
                                    <option value="">Pilih Kategori</option>
                                    {(editingBudget ? categories : categoriesWithoutBudget).map(cat => (
                                        <option key={cat.id} value={cat.id}>{cat.icon} {cat.name}</option>
                                    ))}
                                </select>
                                {formErrors.category_id && <p className="text-red-500 text-xs mt-1">{formErrors.category_id[0]}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Budget Bulanan (Rp) *</label>
                                <input
                                    type="number"
                                    value={formData.budget_amount}
                                    onChange={(e) => setFormData({ ...formData, budget_amount: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                                    placeholder="0"
                                    min="0"
                                    step="10000"
                                    required
                                />
                                {formErrors.budget_amount && <p className="text-red-500 text-xs mt-1">{formErrors.budget_amount[0]}</p>}
                                <p className="text-xs text-gray-500 mt-1">Masukkan angka kelipatan 10.000</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Alert Threshold (%)</label>
                                <input
                                    type="number"
                                    value={formData.alert_threshold}
                                    onChange={(e) => setFormData({ ...formData, alert_threshold: parseInt(e.target.value) || 80 })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                                    min="1"
                                    max="100"
                                />
                                <p className="text-xs text-gray-500 mt-1">Notifikasi akan muncul saat pengeluaran mencapai persentase ini</p>
                            </div>
                            <div className="flex gap-3 pt-4">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={submitting}
                                    className="flex-1 px-4 py-2 bg-bpn-blue text-white rounded-lg hover:bg-blue-800 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                                >
                                    {submitting ? 'Menyimpan...' : <><Save size={16} /> Simpan</>}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default BudgetSetupPage;
