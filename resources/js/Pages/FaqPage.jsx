import React, { useState, useEffect } from 'react';
import CustomerLayout from '../Layouts/CustomerLayout';
import { Search, ChevronDown, ChevronUp, HelpCircle } from 'lucide-react';

export default function FaqPage() {
    const [faqs, setFaqs] = useState([]);
    const [filteredFaqs, setFilteredFaqs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('ALL');
    const [expandedId, setExpandedId] = useState(null);

    const categories = [
        { value: 'ALL', label: 'Semua' },
        { value: 'ACCOUNT', label: 'Akun' },
        { value: 'TRANSFER', label: 'Transfer' },
        { value: 'LOAN', label: 'Pinjaman' },
        { value: 'CARD', label: 'Kartu' },
        { value: 'SECURITY', label: 'Keamanan' },
        { value: 'DEPOSIT', label: 'Deposito' },
        { value: 'FEES', label: 'Biaya' },
        { value: 'TECHNICAL', label: 'Teknis' },
        { value: 'GENERAL', label: 'Umum' }
    ];

    useEffect(() => {
        fetchFaqs();
    }, []);

    useEffect(() => {
        filterFaqs();
    }, [faqs, searchQuery, selectedCategory]);

    const fetchFaqs = async () => {
        try {
            const response = await fetch('/ajax/user/faq');
            const data = await response.json();
            if (data.status === 'success') {
                setFaqs(data.data);
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            setLoading(false);
        }
    };

    const filterFaqs = () => {
        let filtered = faqs;

        if (selectedCategory !== 'ALL') {
            filtered = filtered.filter(faq => faq.category === selectedCategory);
        }

        if (searchQuery) {
            filtered = filtered.filter(faq =>
                faq.question.toLowerCase().includes(searchQuery.toLowerCase()) ||
                faq.answer.toLowerCase().includes(searchQuery.toLowerCase())
            );
        }

        setFilteredFaqs(filtered);
    };

    const toggleExpand = (id) => {
        setExpandedId(expandedId === id ? null : id);
    };

    return (
        <CustomerLayout>
            <div className="p-6 max-w-4xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">Pertanyaan yang Sering Diajukan (FAQ)</h1>
                    <p className="text-gray-600 mt-1">Temukan jawaban untuk pertanyaan Anda</p>
                </div>

                {/* Search & Filter */}
                <div className="bg-white rounded-lg shadow p-4 mb-6">
                    <div className="flex flex-col md:flex-row gap-4">
                        <div className="flex-1 relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={20} />
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Cari pertanyaan..."
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg"
                            />
                        </div>
                        <select
                            value={selectedCategory}
                            onChange={(e) => setSelectedCategory(e.target.value)}
                            className="px-4 py-2 border border-gray-300 rounded-lg"
                        >
                            {categories.map(cat => (
                                <option key={cat.value} value={cat.value}>{cat.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {loading ? (
                    <div className="text-center py-12">
                        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                        <p className="text-gray-600 mt-4">Memuat FAQ...</p>
                    </div>
                ) : filteredFaqs.length === 0 ? (
                    <div className="bg-white rounded-lg shadow p-12 text-center">
                        <HelpCircle size={64} className="mx-auto text-gray-400 mb-4" />
                        <h3 className="text-xl font-semibold text-gray-800 mb-2">Tidak Ada Hasil</h3>
                        <p className="text-gray-600">Coba kata kunci atau kategori lain</p>
                    </div>
                ) : (
                    <div className="space-y-3">
                        {filteredFaqs.map((faq) => (
                            <div key={faq.id} className="bg-white rounded-lg shadow">
                                <button
                                    onClick={() => toggleExpand(faq.id)}
                                    className="w-full px-6 py-4 flex justify-between items-center text-left hover:bg-gray-50"
                                >
                                    <span className="font-medium text-gray-900 pr-4">{faq.question}</span>
                                    {expandedId === faq.id ? (
                                        <ChevronUp className="text-blue-600 flex-shrink-0" size={20} />
                                    ) : (
                                        <ChevronDown className="text-gray-400 flex-shrink-0" size={20} />
                                    )}
                                </button>
                                {expandedId === faq.id && (
                                    <div className="px-6 pb-4 text-gray-700 border-t border-gray-100 pt-4">
                                        {faq.answer}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <div className="mt-8 bg-blue-50 rounded-lg p-6 text-center">
                    <p className="text-gray-700 mb-4">Tidak menemukan jawaban yang Anda cari?</p>
                    <button
                        onClick={() => window.location.href = '/tickets'}
                        className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700"
                    >
                        Hubungi Customer Service
                    </button>
                </div>
            </div>
        </CustomerLayout>
    );
}
