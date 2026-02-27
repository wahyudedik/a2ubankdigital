import React from 'react';
import Button from '../ui/Button.jsx';
import { X } from 'lucide-react';
import { AppConfig } from '../../config/index.js';

const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);

const TopUpRequestDetailModal = ({ request, onClose, onApprove, onReject, status }) => {
    // PERBAIKAN: Logika ini memastikan URL gambar selalu benar sesuai struktur Anda.
    // API base URL (contoh: https://bank.taskora.id/app) diubah menjadi domain utama (https://bank.taskora.id)
    const API_DOMAIN = AppConfig.api.baseUrl.replace('/app', '');

    // Path dari database (contoh: /uploads/proofs/file.png) digabungkan dengan domain utama.
    // Hasilnya adalah URL yang valid: https://bank.taskora.id/uploads/proofs/file.png
    const fullImageUrl = request.proof_of_payment_url.startsWith('http') 
        ? request.proof_of_payment_url 
        : `${API_DOMAIN}${request.proof_of_payment_url}`;

    return (
        <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 relative transform transition-transform duration-300 ease-out scale-100">
                <button onClick={onClose} className="absolute top-4 right-4 text-gray-500 hover:text-gray-800"><X size={24} /></button>
                <h2 className="text-2xl font-bold mb-4 text-gray-800">Detail Permintaan Isi Saldo</h2>
                <div className="space-y-2 text-sm mb-4 text-gray-600">
                    <p><strong>Nasabah:</strong> {request.customer_name}</p>
                    <p><strong>Jumlah:</strong> <span className="font-semibold text-gray-800">{formatCurrency(request.amount)}</span></p>
                    <p><strong>Metode:</strong> {request.payment_method}</p>
                    <p><strong>Tanggal:</strong> {new Date(request.created_at).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' })}</p>
                </div>
                <div>
                    <h3 className="font-semibold mb-2 text-gray-700">Bukti Pembayaran:</h3>
                    <a href={fullImageUrl} target="_blank" rel="noopener noreferrer" className="block border rounded-md overflow-hidden">
                        <img 
                            src={fullImageUrl} 
                            alt="Bukti Pembayaran" 
                            className="w-full h-auto max-h-80 object-contain bg-gray-100"
                            onError={(e) => { e.target.onerror = null; e.target.src="https://placehold.co/600x400/EEE/31343C?text=Gambar+Tidak+Ditemukan"; }}
                        />
                    </a>
                </div>
                <div className="mt-6 flex justify-end gap-4 border-t pt-4">
                    {status === 'PENDING' ? (
                        <>
                            <Button onClick={onReject} className="bg-red-600 hover:bg-red-700">Tolak</Button>
                            <Button onClick={onApprove}>Setujui</Button>
                        </>
                    ) : (
                        <Button onClick={onClose} className="bg-gray-200 text-gray-800 hover:bg-gray-300">Tutup</Button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default TopUpRequestDetailModal;