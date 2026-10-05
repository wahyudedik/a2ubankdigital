import React, { useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PlusCircle, Trash2, Eye, EyeOff, UploadCloud, X, ImageIcon } from 'lucide-react';
import useApi from '@/hooks/useApi';
import { useModal } from '@/contexts/ModalContext.jsx';
import Button from '@/components/ui/Button';
import { getMultipartHeaders } from '@/utils/csrf';

const MAX_FILE_SIZE = 1024 * 1024; // 1 MB
const ACCEPTED_TYPES = ['image/png', 'image/jpeg'];

const AdminBannerSliderPage = () => {
    const { banners } = usePage().props;
    const { callApi } = useApi();
    const modal = useModal();
    const fileInputRef = useRef(null);

    const [selectedFiles, setSelectedFiles] = useState([]);
    const [previews, setPreviews] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const bannerList = Array.isArray(banners) ? banners : [];

    const handlePickFiles = (event) => {
        const files = Array.from(event.target.files || []);
        const valid = [];
        const invalidMessages = [];

        files.forEach((file) => {
            if (!ACCEPTED_TYPES.includes(file.type)) {
                invalidMessages.push(`"${file.name}" bukan format PNG/JPEG.`);
                return;
            }
            if (file.size > MAX_FILE_SIZE) {
                invalidMessages.push(`"${file.name}" melebihi batas 1 MB.`);
                return;
            }
            valid.push(file);
        });

        if (invalidMessages.length > 0) {
            modal.showAlert({ title: 'File Tidak Valid', message: invalidMessages.join(' '), type: 'warning' });
        }

        if (valid.length > 0) {
            setSelectedFiles((prev) => [...prev, ...valid]);
            setPreviews((prev) => [
                ...prev,
                ...valid.map((file) => ({ name: file.name, url: URL.createObjectURL(file) })),
            ]);
        }

        // Reset input agar file yang sama bisa dipilih ulang
        event.target.value = '';
    };

    const removeSelectedFile = (index) => {
        setSelectedFiles((prev) => prev.filter((_, i) => i !== index));
        setPreviews((prev) => {
            if (prev[index]) URL.revokeObjectURL(prev[index].url);
            return prev.filter((_, i) => i !== index);
        });
    };

    const clearPreviews = () => {
        previews.forEach((p) => URL.revokeObjectURL(p.url));
        setPreviews([]);
        setSelectedFiles([]);
    };

    const handleUpload = async () => {
        if (selectedFiles.length === 0) return;

        const confirmed = await modal.showConfirmation({
            title: 'Unggah Banner',
            message: `Unggah ${selectedFiles.length} banner sekarang?`,
            confirmText: 'Ya, Unggah',
            cancelText: 'Batal',
        });
        if (!confirmed) return;

        setUploading(true);
        try {
            const formData = new FormData();
            selectedFiles.forEach((file) => formData.append('images[]', file));

            const response = await fetch('/ajax/admin/banners', {
                method: 'POST',
                headers: getMultipartHeaders(),
                credentials: 'same-origin',
                body: formData,
            });

            const data = await response.json().catch(() => null);

            if (!response.ok) {
                let message = data?.message || 'Gagal mengunggah banner.';
                if (response.status === 422 && data?.errors) {
                    message = Object.values(data.errors).flat()[0] || message;
                }
                throw new Error(message);
            }

            if (data?.status === 'success') {
                modal.showAlert({ title: 'Berhasil', message: data.message || 'Banner berhasil diunggah.', type: 'success' });
                clearPreviews();
                router.reload({ only: ['banners'] });
            }
        } catch (error) {
            modal.showAlert({ title: 'Gagal', message: error.message || 'Gagal mengunggah banner.', type: 'error' });
        } finally {
            setUploading(false);
        }
    };

    const handleToggle = async (banner) => {
        setTogglingId(banner.id);
        const actionText = banner.is_active ? 'menyembunyikan' : 'menampilkan';
        const confirmed = await modal.showConfirmation({
            title: 'Konfirmasi',
            message: `Yakin ingin ${actionText} banner ini?`,
            confirmText: `Ya, ${actionText}`,
        });
        if (confirmed) {
            const result = await callApi(`admin/banners/${banner.id}/toggle`, 'PUT');
            if (result && result.status === 'success') {
                modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' });
                router.reload({ only: ['banners'] });
            } else {
                modal.showAlert({ title: 'Gagal', message: result?.message || 'Gagal mengubah status banner.', type: 'warning' });
            }
        }
        setTogglingId(null);
    };

    const handleDelete = async (banner) => {
        const confirmed = await modal.showConfirmation({
            title: 'Hapus Banner',
            message: `Yakin ingin menghapus banner "${banner.title || 'Tanpa judul'}"? Tindakan ini tidak dapat dibatalkan.`,
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
        });
        if (!confirmed) return;

        const result = await callApi(`admin/banners/${banner.id}`, 'DELETE');
        if (result && result.status === 'success') {
            modal.showAlert({ title: 'Berhasil', message: result.message || 'Banner berhasil dihapus.', type: 'success' });
            router.reload({ only: ['banners'] });
        } else {
            modal.showAlert({ title: 'Gagal', message: result?.message || 'Gagal menghapus banner.', type: 'warning' });
        }
    };

    return (
        <div>
            <div className="mb-6">
                <h1 className="text-2xl md:text-3xl font-bold text-gray-800">Banner Slider</h1>
                <p className="text-sm text-gray-500 mt-1">Atur banner yang ditampilkan di halaman utama website Anda.</p>
            </div>

            {/* Daftar banner */}
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
                {bannerList.length > 0 ? bannerList.map((banner) => (
                    <div key={banner.id} className="bg-white rounded-lg shadow-md overflow-hidden">
                        <div className="relative w-full bg-gray-100" style={{ aspectRatio: '3 / 1' }}>
                            {banner.image_url ? (
                                <img src={banner.image_url} alt={banner.title || 'Banner'} className="w-full h-full object-cover" />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center text-gray-400">
                                    <ImageIcon size={32} />
                                </div>
                            )}
                        </div>
                        <div className="p-4">
                            <p className="font-medium text-gray-800 truncate">{banner.title || 'Tanpa judul'}</p>
                            <p className="text-xs text-gray-500 mt-1">{banner.is_active ? 'Sedang ditampilkan' : 'Tidak ditampilkan'}</p>
                            <div className="flex items-center gap-2 mt-3">
                                <Button
                                    onClick={() => handleToggle(banner)}
                                    variant={banner.is_active ? 'secondary' : 'success'}
                                    className="py-1.5 px-3 text-xs flex items-center gap-1"
                                    isLoading={togglingId === banner.id}
                                >
                                    {banner.is_active ? <><EyeOff size={14} /> Sembunyikan</> : <><Eye size={14} /> Tampilkan</>}
                                </Button>
                                <Button
                                    onClick={() => handleDelete(banner)}
                                    variant="danger"
                                    className="py-1.5 px-3 text-xs flex items-center gap-1"
                                >
                                    <Trash2 size={14} /> Hapus
                                </Button>
                            </div>
                        </div>
                    </div>
                )) : (
                    <div className="col-span-full bg-white rounded-lg shadow-md p-8 text-center text-gray-500">
                        Belum ada banner. Unggah banner pertama Anda di bawah ini.
                    </div>
                )}
            </div>

            {/* Section upload */}
            <div className="bg-white rounded-lg shadow-md p-6">
                <h2 className="font-bold text-lg text-gray-800">Upload banner untuk website kamu di sini.</h2>
                <p className="text-sm text-gray-500 mt-1">
                    Pastikan banner yang Anda unggah memiliki rasio 3:1 dengan ukuran yang disarankan 1500 × 500 piksel,
                    menggunakan format PNG atau JPEG, serta ukuran file maksimum 1 MB.
                </p>

                <div
                    className="mt-4 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-bpn-blue transition-colors cursor-pointer"
                    onClick={() => fileInputRef.current?.click()}
                >
                    <UploadCloud size={40} className="mx-auto text-gray-400 mb-2" />
                    <Button
                        type="button"
                        onClick={(e) => { e.stopPropagation(); fileInputRef.current?.click(); }}
                        className="py-2 px-4 text-sm flex items-center gap-2 mx-auto"
                    >
                        <PlusCircle size={16} /> Tambah banner
                    </Button>
                    <p className="text-xs text-gray-400 mt-2">Klik untuk memilih lebih dari satu file sekaligus</p>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/png,image/jpeg"
                        multiple
                        className="hidden"
                        onChange={handlePickFiles}
                    />
                </div>

                {previews.length > 0 && (
                    <div className="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        {previews.map((preview, index) => (
                            <div key={index} className="relative border border-gray-200 rounded-lg overflow-hidden">
                                <div className="w-full bg-gray-100" style={{ aspectRatio: '3 / 1' }}>
                                    <img src={preview.url} alt={preview.name} className="w-full h-full object-cover" />
                                </div>
                                <p className="text-xs text-gray-600 px-2 py-1 truncate">{preview.name}</p>
                                <button
                                    onClick={() => removeSelectedFile(index)}
                                    className="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 hover:bg-red-700"
                                    title="Hapus dari daftar"
                                >
                                    <X size={12} />
                                </button>
                            </div>
                        ))}
                    </div>
                )}

                <div className="mt-4 flex justify-end">
                    <Button
                        onClick={handleUpload}
                        isLoading={uploading}
                        disabled={selectedFiles.length === 0 || uploading}
                        className="py-2 px-6 text-sm"
                    >
                        Simpan
                    </Button>
                </div>
            </div>
        </div>
    );
};

export default AdminBannerSliderPage;
