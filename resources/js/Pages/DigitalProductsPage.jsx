import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function DigitalProductsPage({ auth }) {
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedCategory, setSelectedCategory] = useState('ALL');
    const [showPurchaseModal, setShowPurchaseModal] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [destination, setDestination] = useState('');
    const [processing, setProcessing] = useState(false);

    const categories = [
        { id: 'ALL', name: 'Semua', icon: '🛒' },
        { id: 'PULSA', name: 'Pulsa', icon: '📱' },
        { id: 'DATA', name: 'Paket Data', icon: '📶' },
        { id: 'EWALLET', name: 'E-Wallet', icon: '💳' },
        { id: 'GAME', name: 'Game', icon: '🎮' }
    ];

    useEffect(() => {
        fetchProducts();
    }, [selectedCategory]);

    const fetchProducts = async () => {
        try {
            setLoading(true);
            const url = selectedCategory === 'ALL'
                ? '/user/digital-products'
                : `/user/digital-products?category=${selectedCategory}`;

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.status === 'success') {
                setProducts(data.data);
            }
        } catch (error) {
            console.error('Failed to fetch products:', error);
        } finally {
            setLoading(false);
        }
    };

    const handlePurchase = async (e) => {
        e.preventDefault();
        if (!destination.trim()) {
            alert('Mohon isi nomor tujuan');
            return;
        }

        setProcessing(true);
        try {
            const response = await fetch('/user/digital-products/purchase', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(row => row.startsWith('XSRF-TOKEN='))?.split('=')[1] || '')
                },
                body: JSON.stringify({
                    product_id: selectedProduct.id,
                    destination: destination
                })
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert('Pembelian berhasil!');
                setShowPurchaseModal(false);
                setDestination('');
                setSelectedProduct(null);
            } else {
                alert(data.message || 'Pembelian gagal');
            }
        } catch (error) {
            console.error('Purchase failed:', error);
            alert('Terjadi kesalahan saat melakukan pembelian');
        } finally {
            setProcessing(false);
        }
    };

    const openPurchaseModal = (product) => {
        setSelectedProduct(product);
        setShowPurchaseModal(true);
        setDestination('');
    };

    const formatPrice = (price) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(price);
    };

    const getDestinationLabel = (category) => {
        switch (category) {
            case 'PULSA':
            case 'DATA':
                return 'Nomor HP';
            case 'EWALLET':
                return 'Nomor E-Wallet';
            case 'GAME':
                return 'User ID / Game ID';
            default:
                return 'Nomor Tujuan';
        }
    };

    const getDestinationPlaceholder = (category) => {
        switch (category) {
            case 'PULSA':
            case 'DATA':
                return '08123456789';
            case 'EWALLET':
                return '08123456789';
            case 'GAME':
                return 'Masukkan User ID';
            default:
                return 'Masukkan nomor tujuan';
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Produk Digital" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Produk Digital</h1>
                        <p className="text-sm text-gray-600 mt-1">Beli pulsa, paket data, voucher game, dan lainnya</p>
                    </div>

                    {/* Category Filter */}
                    <div className="bg-white rounded-lg shadow mb-6 p-4">
                        <div className="flex gap-2 overflow-x-auto">
                            {categories.map(cat => (
                                <button
                                    key={cat.id}
                                    onClick={() => setSelectedCategory(cat.id)}
                                    className={`flex items-center gap-2 px-4 py-2 rounded-lg whitespace-nowrap transition ${selectedCategory === cat.id
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        }`}
                                >
                                    <span>{cat.icon}</span>
                                    <span className="font-medium">{cat.name}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Products Grid */}
                    {loading ? (
                        <div className="text-center py-12">
                            <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <p className="text-gray-600 mt-2">Memuat produk...</p>
                        </div>
                    ) : products.length === 0 ? (
                        <div className="bg-white rounded-lg shadow p-12 text-center">
                            <p className="text-gray-500">Tidak ada produk tersedia</p>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {products.map(product => (
                                <div key={product.id} className="bg-white rounded-lg shadow hover:shadow-lg transition p-4">
                                    <div className="flex items-start justify-between mb-3">
                                        <div className="flex-1">
                                            <div className="text-xs font-medium text-blue-600 mb-1">
                                                {product.provider}
                                            </div>
                                            <h3 className="font-semibold text-gray-900">{product.name}</h3>
                                        </div>
                                        <span className="text-2xl">
                                            {categories.find(c => c.id === product.category)?.icon || '📦'}
                                        </span>
                                    </div>

                                    <div className="mb-4">
                                        <div className="text-2xl font-bold text-gray-900">
                                            {formatPrice(product.price)}
                                        </div>
                                        {product.nominal && (
                                            <div className="text-xs text-gray-500">
                                                Nominal: {product.category === 'DATA'
                                                    ? `${product.nominal} MB`
                                                    : product.nominal.toLocaleString('id-ID')}
                                            </div>
                                        )}
                                    </div>

                                    <button
                                        onClick={() => openPurchaseModal(product)}
                                        disabled={!product.is_active}
                                        className={`w-full py-2 rounded-lg font-medium transition ${product.is_active
                                                ? 'bg-blue-600 text-white hover:bg-blue-700'
                                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                            }`}
                                    >
                                        {product.is_active ? 'Beli Sekarang' : 'Tidak Tersedia'}
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Purchase Modal */}
            {showPurchaseModal && selectedProduct && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div className="flex justify-between items-center p-6 border-b">
                            <h2 className="text-xl font-bold">Konfirmasi Pembelian</h2>
                            <button
                                onClick={() => setShowPurchaseModal(false)}
                                className="text-gray-400 hover:text-gray-600"
                            >
                                ✕
                            </button>
                        </div>

                        <form onSubmit={handlePurchase} className="p-6">
                            {/* Product Info */}
                            <div className="bg-gray-50 rounded-lg p-4 mb-4">
                                <div className="text-sm text-gray-600 mb-1">Produk</div>
                                <div className="font-semibold text-gray-900">{selectedProduct.name}</div>
                                <div className="text-lg font-bold text-blue-600 mt-2">
                                    {formatPrice(selectedProduct.price)}
                                </div>
                            </div>

                            {/* Destination Input */}
                            <div className="mb-4">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    {getDestinationLabel(selectedProduct.category)}
                                </label>
                                <input
                                    type="text"
                                    value={destination}
                                    onChange={(e) => setDestination(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder={getDestinationPlaceholder(selectedProduct.category)}
                                    required
                                />
                            </div>

                            {/* Info */}
                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                                <p className="text-xs text-blue-800">
                                    ℹ️ Pastikan nomor tujuan sudah benar. Transaksi tidak dapat dibatalkan setelah diproses.
                                </p>
                            </div>

                            {/* Actions */}
                            <div className="flex gap-3">
                                <button
                                    type="button"
                                    onClick={() => setShowPurchaseModal(false)}
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
                                    {processing ? 'Memproses...' : 'Beli'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
