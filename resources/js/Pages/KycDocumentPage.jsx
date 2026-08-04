import React, { useState, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import { ChevronLeft, UploadCloud, CheckCircle, XCircle, Clock, AlertTriangle, Info, Camera, CreditCard } from 'lucide-react';
import { useModal } from '@/contexts/ModalContext.jsx';
import axios from 'axios';
import { AppConfig } from '@/config';

const KTP_MIN_WIDTH = 600;
const KTP_MIN_HEIGHT = 400;
const SELFIE_MIN_WIDTH = 400;
const SELFIE_MIN_HEIGHT = 400;

const validateImageDimensions = (file, minWidth, minHeight) => {
    return new Promise((resolve) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            URL.revokeObjectURL(url);
            if (img.width < minWidth || img.height < minHeight) {
                resolve({ valid: false, width: img.width, height: img.height });
            } else {
                resolve({ valid: true, width: img.width, height: img.height });
            }
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve({ valid: false, width: 0, height: 0 });
        };
        img.src = url;
    });
};

const StatusBadge = ({ status }) => {
    const config = {
        PENDING: { icon: Clock, color: 'text-yellow-600', bg: 'bg-yellow-50 border-yellow-200', label: 'Menunggu Verifikasi' },
        VERIFIED: { icon: CheckCircle, color: 'text-green-600', bg: 'bg-green-50 border-green-200', label: 'Terverifikasi' },
        REJECTED: { icon: XCircle, color: 'text-red-600', bg: 'bg-red-50 border-red-200', label: 'Ditolak' },
    };
    const c = config[status] || config.PENDING;
    const Icon = c.icon;
    return (
        <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border ${c.bg} ${c.color}`}>
            <Icon className="w-4 h-4" />
            {c.label}
        </span>
    );
};

const ImageUploadCard = ({ label, icon: Icon, currentPath, preview, onChange, inputRef, guide }) => (
    <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div className="p-4 border-b border-gray-100">
            <div className="flex items-center gap-2 mb-1">
                <Icon className="w-5 h-5 text-blue-600" />
                <h3 className="font-semibold text-gray-900">{label}</h3>
            </div>
        </div>
        <div className="p-4">
            {/* Current/Preview Image */}
            <div className="mb-3">
                {preview ? (
                    <div className="relative">
                        <img src={preview} alt={label} className="w-full h-48 object-contain rounded-lg bg-gray-50 border border-gray-200" />
                        <span className="absolute top-2 right-2 bg-blue-600 text-white text-xs px-2 py-1 rounded-full">Baru</span>
                    </div>
                ) : currentPath ? (
                    <img src={`${AppConfig.assetUrl}/${currentPath}`} alt={label} className="w-full h-48 object-contain rounded-lg bg-gray-50 border border-gray-200" />
                ) : (
                    <div className="w-full h-48 flex flex-col items-center justify-center rounded-lg bg-gray-50 border-2 border-dashed border-gray-300">
                        <UploadCloud className="w-10 h-10 text-gray-400 mb-2" />
                        <p className="text-sm text-gray-500">Belum ada foto</p>
                    </div>
                )}
            </div>

            {/* Upload Button */}
            <input
                ref={inputRef}
                type="file"
                accept="image/png, image/jpeg"
                className="hidden"
                onChange={onChange}
            />
            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                className={`w-full py-2.5 px-4 rounded-lg border-2 border-dashed text-sm font-medium transition-colors ${currentPath
                        ? 'border-orange-300 text-orange-700 bg-orange-50 hover:bg-orange-100'
                        : 'border-blue-300 text-blue-700 bg-blue-50 hover:bg-blue-100'
                    }`}
            >
                <UploadCloud className="w-4 h-4 inline mr-2" />
                {currentPath ? 'Ganti Foto' : 'Upload Foto'}
            </button>

            {/* Guide */}
            <div className="mt-3 p-3 bg-gray-50 rounded-lg">
                <div className="flex items-start gap-2">
                    <Info className="w-4 h-4 text-gray-500 mt-0.5 flex-shrink-0" />
                    <div className="text-xs text-gray-600 space-y-1">
                        {guide.map((tip, i) => (
                            <p key={i}>• {tip}</p>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    </div>
);

const KycDocumentPage = () => {
    const { customerProfile } = usePage().props;
    const modal = useModal();
    const ktpInputRef = useRef(null);
    const selfieInputRef = useRef(null);

    const [ktpFile, setKtpFile] = useState(null);
    const [selfieFile, setSelfieFile] = useState(null);
    const [ktpPreview, setKtpPreview] = useState(null);
    const [selfiePreview, setSelfiePreview] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    const handleKtpChange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            modal.showAlert({ title: 'Format Tidak Sesuai', message: 'Hanya file JPG dan PNG yang diperbolehkan.', type: 'error' });
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            modal.showAlert({ title: 'Ukuran Terlalu Besar', message: 'Ukuran file maksimal 2MB.', type: 'error' });
            return;
        }

        const dim = await validateImageDimensions(file, KTP_MIN_WIDTH, KTP_MIN_HEIGHT);
        if (!dim.valid) {
            modal.showAlert({
                title: 'Resolusi Terlalu Rendah',
                message: `Dimensi foto KTP minimal ${KTP_MIN_WIDTH}x${KTP_MIN_HEIGHT} px. Saat ini ${dim.width}x${dim.height} px. Pastikan foto diambil dengan resolusi tinggi dan tidak terpotong.`,
                type: 'error'
            });
            return;
        }

        setKtpFile(file);
        setKtpPreview(URL.createObjectURL(file));
    };

    const handleSelfieChange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            modal.showAlert({ title: 'Format Tidak Sesuai', message: 'Hanya file JPG dan PNG yang diperbolehkan.', type: 'error' });
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            modal.showAlert({ title: 'Ukuran Terlalu Besar', message: 'Ukuran file maksimal 2MB.', type: 'error' });
            return;
        }

        const dim = await validateImageDimensions(file, SELFIE_MIN_WIDTH, SELFIE_MIN_HEIGHT);
        if (!dim.valid) {
            modal.showAlert({
                title: 'Resolusi Terlalu Rendah',
                message: `Dimensi foto swafoto minimal ${SELFIE_MIN_WIDTH}x${SELFIE_MIN_HEIGHT} px. Saat ini ${dim.width}x${dim.height} px. Pastikan wajah terlihat jelas dan tidak terpotong.`,
                type: 'error'
            });
            return;
        }

        setSelfieFile(file);
        setSelfiePreview(URL.createObjectURL(file));
    };

    const handleSubmit = async () => {
        if (!ktpFile && !selfieFile) {
            modal.showAlert({ title: 'Tidak Ada Perubahan', message: 'Pilih minimal satu foto untuk diunggah.', type: 'warning' });
            return;
        }

        const confirmed = await modal.showConfirmation({
            title: 'Kirim Dokumen KYC?',
            message: 'Dokumen yang dikirim akan diverifikasi oleh admin. Status KYC akan berubah menjadi "Menunggu Verifikasi". Lanjutkan?',
            confirmText: 'Kirim Sekarang',
            cancelText: 'Batal',
        });

        if (!confirmed) return;

        setSubmitting(true);
        try {
            const formData = new FormData();
            if (ktpFile) formData.append('ktp_image', ktpFile);
            if (selfieFile) formData.append('selfie_image', selfieFile);

            const response = await axios.post('/ajax/user/kyc/update', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.data.status === 'success') {
                modal.showAlert({
                    title: 'Berhasil Dikirim!',
                    message: 'Dokumen KYC berhasil diunggah. Status verifikasi akan diproses oleh admin.',
                    type: 'success'
                });
                // Reset previews
                setKtpFile(null);
                setSelfieFile(null);
                setKtpPreview(null);
                setSelfiePreview(null);
                // Reload page data
                router.reload({ only: ['customerProfile'] });
            }
        } catch (error) {
            const message = error.response?.data?.message || 'Gagal mengunggah dokumen KYC.';
            modal.showAlert({ title: 'Gagal', message, type: 'error' });
        } finally {
            setSubmitting(false);
        }
    };

    const kycStatus = customerProfile?.kyc_status || 'PENDING';
    const ktpPath = customerProfile?.ktp_image_path;
    const selfiePath = customerProfile?.selfie_image_path;

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <div className="bg-white border-b border-gray-200 px-4 py-3 sticky top-0 z-10">
                <div className="flex items-center gap-3">
                    <button onClick={() => router.back()} className="p-1 rounded-lg hover:bg-gray-100">
                        <ChevronLeft className="w-5 h-5 text-gray-700" />
                    </button>
                    <div>
                        <h1 className="text-lg font-bold text-gray-900">Dokumen KYC</h1>
                        <p className="text-xs text-gray-500">Kelola dokumen verifikasi identitas Anda</p>
                    </div>
                </div>
            </div>

            <div className="p-4 space-y-4 max-w-lg mx-auto">
                {/* Status */}
                <div className="bg-white rounded-xl border border-gray-200 p-4">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-gray-700">Status Verifikasi</span>
                        <StatusBadge status={kycStatus} />
                    </div>
                    {kycStatus === 'REJECTED' && customerProfile?.verification_notes && (
                        <div className="mt-3 p-3 bg-red-50 rounded-lg border border-red-200">
                            <div className="flex items-start gap-2">
                                <AlertTriangle className="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p className="text-xs font-medium text-red-800">Catatan Penolakan:</p>
                                    <p className="text-xs text-red-700 mt-1">{customerProfile.verification_notes}</p>
                                </div>
                            </div>
                        </div>
                    )}
                    {kycStatus === 'VERIFIED' && (
                        <p className="mt-2 text-xs text-green-700 bg-green-50 p-2 rounded-lg border border-green-200">
                            ✅ Dokumen KYC Anda sudah terverifikasi. Jika ingin mengganti foto, upload foto baru dan status akan berubah menjadi "Menunggu Verifikasi".
                        </p>
                    )}
                    {kycStatus === 'PENDING' && (
                        <p className="mt-2 text-xs text-yellow-700 bg-yellow-50 p-2 rounded-lg border border-yellow-200">
                            ⏳ Dokumen Anda sedang menunggu verifikasi oleh admin. Proses biasanya memakan waktu 1×24 jam.
                        </p>
                    )}
                </div>

                {/* Upload Cards */}
                <ImageUploadCard
                    label="Foto KTP"
                    icon={CreditCard}
                    currentPath={ktpPath}
                    preview={ktpPreview}
                    onChange={handleKtpChange}
                    inputRef={ktpInputRef}
                    guide={[
                        'Foto KTP harus jelas dan terbaca',
                        'Seluruh KTP terlihat dalam satu frame',
                        'Minimal 600×400 px, format JPG/PNG, maks 2MB',
                        'Tidak buram, tidak terpotong, pencahayaan cukup',
                        'NIK dan nama harus terbaca dengan jelas',
                    ]}
                />

                <ImageUploadCard
                    label="Swafoto (Selfie)"
                    icon={Camera}
                    currentPath={selfiePath}
                    preview={selfiePreview}
                    onChange={handleSelfieChange}
                    inputRef={selfieInputRef}
                    guide={[
                        'Wajah terlihat jelas dan menghadap kamera',
                        'Latar belakang polos / kosong',
                        'Minimal 400×400 px, format JPG/PNG, maks 2MB',
                        'Tidak menggunakan filter atau kacamata',
                        'Pastikan pencahayaan cukup (tidak gelap)',
                    ]}
                />

                {/* Submit Button */}
                {(ktpFile || selfieFile) && (
                    <button
                        onClick={handleSubmit}
                        disabled={submitting}
                        className="w-full py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                    >
                        {submitting ? (
                            <>
                                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                Mengirim...
                            </>
                        ) : (
                            <>
                                <UploadCloud className="w-5 h-5" />
                                Kirim Dokumen
                            </>
                        )}
                    </button>
                )}

                {/* Info */}
                <div className="text-center text-xs text-gray-400 pb-4">
                    <p>Dokumen hanya digunakan untuk verifikasi identitas.</p>
                    <p>Data Anda dilindungi sesuai kebijakan privasi bank.</p>
                </div>
            </div>
        </div>
    );
};

export default KycDocumentPage;
