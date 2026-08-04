import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Plus, Search, Filter, Trash2, Edit, Calendar, X, ChevronDown } from 'lucide-react';
import axios from 'axios';

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
};

const ExpenseListPage = () => {
    const [records, setRecords] = useState([]);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingRecord, setEditingRecord] = useState(null);
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1 });

    // Filters
    const [search, setSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [showFilters, setShowFilters] = useState(false);

    // Form state
    const [formData, setFormData] = useState({
        category_id: '',
        amount: '',
        description: '',
        expense_date: new Date().toISOString().split('T')[0],
    });
    const [formErrors, setFormErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        fetchCategories();
        fetchRecords();
    }, [search, selectedCategory, startDate, endDate, pagination.current_page]);

    const fetchCategories = async () => {
        try {
            const response = await axios.get('/ajax/user/expense/categories');
            setCategories(response.data.data);
        } catch (error) {
            console.error('Error fetching categories:', error);
        }
    };

    const fetchRecords = async (page = 1) => {
        setLoading(true);
        try {
            const params = {
                page,
                per_page: 20,
                sort_by: 'expense_date',
                sort_order: 'desc',
            };
            if (search) params.search = search;
            if (selectedCategory) params.category_id = selectedCategory;
            if (startDate) params.start_date = startDate;
            if (endDate) params.end_date = endDate;

            const response = await axios.get('/ajax/user/expense/records', { params });
            setRecords(response.data.data.data);
            setPagination({
                current_page: response.data.data.current_page,
                last_page: response.data.data.last_page,
            });
        } catch (error) {
            console.error('Error fetching records:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setFormErrors({});

        try {
            if (editingRecord) {
                await axios.put(`/ajax/user/expense/records/${editingRecord.id}`, formData);
            } else {
                await axios.post('/ajax/user/expense/records', formData);
            }
            setShowModal(false);
            setEditingRecord(null);
            resetForm();
            fetchRecords();
        } catch (error) {
            if (error.response?.data?.errors) {
                setFormErrors(error.response.data.errors);
            }
        } finally {
            setSubmitting(false);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Apakah Anda yakin ingin menghapus catatan ini?')) return;

        try {
            await axios.delete(`/ajax/user/expense/records/${id}`);
            fetchRecords();
        } catch (error) {
            console.error('Error deleting record:', error);
        }
    };

    const handleEdit = (record) => {
        setEditingRecord(record);
        setFormData({
            category_id: record.category_id,
            amount: record.amount,
            description: record.description || '',
            expense_date: record.expense_date,
        });
        setShowModal(true);
    };

    const resetForm = () => {
        setFormData({
            category_id: '',
            amount: '',
            description: '',
            expense_date: new Date().toISOString().split('T')[0],
        });
        setFormErrors({});
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedCategory('');
        setStartDate('');
        setEndDate('');
    };

    const getCategoryById = (id) => categories.find(c => c.id === id);

    return (
        <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Catatan Pengeluaran</h1>
                    <p className="text-gray-500 text-sm">Kelola semua pengeluaran pribadi Anda</p>
                </div>
                <button
                    onClick={() => {
                        resetForm();
                        setEditingRecord(null);
                        setShowModal(true);
                    }}
                    className="bg-bpn-blue text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-800 transition-colors"
                >
                    <Plus size={18} />
                    Tambah
                </button>
            </div>

            {/* Search & Filter */}
            <div className="bg-white rounded-xl shadow-md p-4">
                <div className="flex gap-3">
                    <div className="flex-1 relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={18} />
                        <input
                            type="text"
                            placeholder="Cari deskripsi..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                        />
                    </div>
                    <button
                        onClick={() => setShowFilters(!showFilters)}
                        className={`px-4 py-2 rounded-lg flex items-center gap-2 transition-colors ${showFilters ? 'bg-bpn-blue text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                    >
                        <Filter size={18} />
                        Filter
                    </button>
                </div>

                {/* Filter Options */}
                {showFilters && (
                    <div className="mt-4 pt-4 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                            <select
                                value={selectedCategory}
                                onChange={(e) => setSelectedCategory(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                            >
                                <option value="">Semua Kategori</option>
                                {categories.map(cat => (
                                    <option key={cat.id} value={cat.id}>{cat.icon} {cat.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                            <input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                            <input
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                            />
                        </div>
                        <div className="md:col-span-3">
                            <button onClick={clearFilters} className="text-bpn-blue text-sm font-medium hover:underline">
                                Hapus Semua Filter
                            </button>
                        </div>
                    </div>
                )}
            </div>

            {/* Records List */}
            <div className="bg-white rounded-xl shadow-md">
                {loading ? (
                    <div className="p-8 text-center">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-bpn-blue mx-auto mb-4"></div>
                        <p className="text-gray-500">Memuat data...</p>
                    </div>
                ) : records.length === 0 ? (
                    <div className="p-8 text-center">
                        <Calendar className="mx-auto text-gray-300 mb-2" size={48} />
                        <p className="text-gray-500">Belum ada catatan pengeluaran</p>
                        <button
                            onClick={() => setShowModal(true)}
                            className="mt-2 text-bpn-blue text-sm font-semibold hover:underline"
                        >
                            Tambah Sekarang
                        </button>
                    </div>
                ) : (
                    <>
                        <div className="divide-y divide-gray-100">
                            {records.map((record) => (
                                <div key={record.id} className="p-4 hover:bg-gray-50 transition-colors">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full flex items-center justify-center text-lg" style={{ backgroundColor: getCategoryById(record.category_id)?.color + '20' }}>
                                                {getCategoryById(record.category_id)?.icon || '📦'}
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-800">{record.description || getCategoryById(record.category_id)?.name}</p>
                                                <p className="text-xs text-gray-500">
                                                    {getCategoryById(record.category_id)?.name} • {new Date(record.expense_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                                                    {record.is_auto_imported && <span className="ml-2 text-blue-500">(Auto)</span>}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <p className="font-semibold text-gray-800">-{formatCurrency(record.amount)}</p>
                                            <div className="flex gap-1">
                                                <button
                                                    onClick={() => handleEdit(record)}
                                                    className="p-1 text-gray-400 hover:text-blue-600 transition-colors"
                                                >
                                                    <Edit size={16} />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(record.id)}
                                                    className="p-1 text-gray-400 hover:text-red-600 transition-colors"
                                                >
                                                    <Trash2 size={16} />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Pagination */}
                        {pagination.last_page > 1 && (
                            <div className="p-4 border-t border-gray-100 flex justify-center gap-2">
                                <button
                                    onClick={() => fetchRecords(pagination.current_page - 1)}
                                    disabled={pagination.current_page === 1}
                                    className="px-3 py-1 rounded-lg border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                >
                                    ← Prev
                                </button>
                                <span className="px-3 py-1 text-sm text-gray-600">
                                    Halaman {pagination.current_page} dari {pagination.last_page}
                                </span>
                                <button
                                    onClick={() => fetchRecords(pagination.current_page + 1)}
                                    disabled={pagination.current_page === pagination.last_page}
                                    className="px-3 py-1 rounded-lg border border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                                >
                                    Next →
                                </button>
                            </div>
                        )}
                    </>
                )}
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-md">
                        <div className="flex justify-between items-center p-4 border-b border-gray-200">
                            <h3 className="font-semibold text-lg text-gray-800">
                                {editingRecord ? 'Edit Pengeluaran' : 'Tambah Pengeluaran'}
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
                                >
                                    <option value="">Pilih Kategori</option>
                                    {categories.map(cat => (
                                        <option key={cat.id} value={cat.id}>{cat.icon} {cat.name}</option>
                                    ))}
                                </select>
                                {formErrors.category_id && <p className="text-red-500 text-xs mt-1">{formErrors.category_id[0]}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Jumlah (Rp) *</label>
                                <input
                                    type="number"
                                    value={formData.amount}
                                    onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                                    placeholder="0"
                                    min="0.01"
                                    step="0.01"
                                    required
                                />
                                {formErrors.amount && <p className="text-red-500 text-xs mt-1">{formErrors.amount[0]}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                                <input
                                    type="text"
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                                    placeholder="Contoh: Makan siang di kantor"
                                    maxLength="500"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Tanggal *</label>
                                <input
                                    type="date"
                                    value={formData.expense_date}
                                    onChange={(e) => setFormData({ ...formData, expense_date: e.target.value })}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-bpn-blue focus:border-transparent"
                                    max={new Date().toISOString().split('T')[0]}
                                    required
                                />
                                {formErrors.expense_date && <p className="text-red-500 text-xs mt-1">{formErrors.expense_date[0]}</p>}
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
                                    className="flex-1 px-4 py-2 bg-bpn-blue text-white rounded-lg hover:bg-blue-800 transition-colors disabled:opacity-50"
                                >
                                    {submitting ? 'Menyimpan...' : 'Simpan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ExpenseListPage;
