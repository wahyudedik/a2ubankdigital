import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { ChevronRight, User, KeyRound, Bell, LogOut, Users, CreditCard, Lock as LockIcon, Banknote, BellRing, BellOff, Loader2, AlertTriangle, Camera } from 'lucide-react';
import { useNotification } from '@/contexts/NotificationContext.jsx';
import { useModal } from '@/contexts/ModalContext.jsx';
import { AppConfig } from '@/config';
import axios from 'axios';

const ProfilePage = () => {
    const { auth } = usePage().props;
    const user = auth?.user || {};
    const modal = useModal();
    const { notificationStatus, subscribeToNotifications } = useNotification();
    const [isSubscribing, setIsSubscribing] = useState(false);

    const handleSubscribe = async () => {
        setIsSubscribing(true);
        if (subscribeToNotifications) {
            await subscribeToNotifications();
        }
        setIsSubscribing(false);
    };

    const handleLogout = () => {
        localStorage.removeItem('authUser');
        router.post('/logout');
    };

    const handlePictureChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        // Validate size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            modal.showAlert({
                title: "Gagal",
                message: "Ukuran foto maksimal 2MB.",
                type: "error"
            });
            return;
        }

        const formData = new FormData();
        formData.append('profile_picture', file);

        axios.post('/ajax/user/profile/picture', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then((response) => {
            if (response.data && response.data.status === 'success') {
                modal.showAlert({
                    title: "Berhasil",
                    message: "Foto profil berhasil diperbarui.",
                    type: "success"
                });
                router.reload({ only: ['auth'] });
            } else {
                modal.showAlert({
                    title: "Gagal",
                    message: response.data?.message || "Gagal memperbarui foto profil.",
                    type: "error"
                });
            }
        })
        .catch((error) => {
            const errorMsg = error.response?.data?.errors?.profile_picture?.[0]
                || error.response?.data?.message 
                || "Gagal memperbarui foto profil.";
            modal.showAlert({
                title: "Gagal",
                message: errorMsg,
                type: "error"
            });
        });
    };

    const menuItems = [
        { icon: <User />, text: 'Informasi Pribadi', path: '/profile/info' },
        { icon: <CreditCard />, text: 'Manajemen Kartu', path: '/profile/cards' },
        { icon: <Banknote />, text: 'Rekening Penarikan', path: '/profile/withdrawal-accounts' },
        { icon: <Users />, text: 'Daftar Penerima', path: '/profile/beneficiaries' },
        { icon: <KeyRound />, text: 'Ubah Password', path: '/profile/change-password' },
        { icon: <LockIcon />, text: 'Ubah PIN Transaksi', path: '/profile/change-pin' },
        { icon: <Bell />, text: 'Riwayat Notifikasi', path: '/notifications' },
    ];

    const renderNotificationButton = () => {
        switch (notificationStatus) {
            case 'loading':
                return (
                    <div className="flex items-center p-4 text-gray-500">
                        <Loader2 className="animate-spin" />
                        <span className="ml-4 font-medium">Mengecek status notifikasi...</span>
                    </div>
                );
            case 'granted':
                return (
                    <div className="flex items-center p-4 text-green-800 bg-green-50">
                        <BellRing className="text-green-600" />
                        <span className="ml-4 font-medium">Notifikasi Push Aktif</span>
                    </div>
                );
            case 'denied':
                return (
                    <div className="flex items-center p-4 text-bpn-red bg-red-50">
                        <BellOff className="text-red-600" />
                        <span className="ml-4 font-medium">Notifikasi Diblokir oleh Browser</span>
                    </div>
                );
            case 'unsupported':
                return (
                    <div className="flex items-center p-4 text-gray-500 bg-gray-100">
                        <BellOff />
                        <span className="ml-4 font-medium">Browser Tidak Mendukung Notifikasi</span>
                    </div>
                );
            case 'error_checking':
                return (
                    <div className="flex items-center p-4 text-bpn-yellow bg-yellow-50">
                        <AlertTriangle className="text-yellow-600" />
                        <span className="ml-4 font-medium">Gagal memuat status notifikasi.</span>
                    </div>
                );
            case 'prompt':
            default:
                return (
                    <button onClick={handleSubscribe} disabled={isSubscribing} className="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors disabled:opacity-50">
                        <div className="flex items-center">
                            <div className="text-bpn-blue"><BellRing /></div>
                            <span className="ml-4 text-gray-700 font-medium">Aktifkan Notifikasi Push</span>
                        </div>
                        {isSubscribing ? <Loader2 className="animate-spin text-gray-400" /> : <ChevronRight className="text-gray-400" />}
                    </button>
                );
        }
    };

    return (
        <div className="p-4">
            <div className="flex flex-col items-center mb-8">
                <div className="relative mb-4 group">
                    {user.profile_picture_path ? (
                        <img 
                            src={user.profile_picture_path} 
                            alt={user.fullName} 
                            className="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md"
                        />
                    ) : (
                        <div className={`w-24 h-24 rounded-full ${AppConfig.theme.bgPrimary} text-white flex items-center justify-center font-bold text-4xl shadow-md`}>
                            {user.fullName ? user.fullName.charAt(0) : '?'}
                        </div>
                    )}
                    <label className="absolute bottom-0 right-0 bg-bpn-blue hover:bg-bpn-blue-dark text-white p-2 rounded-full cursor-pointer shadow-md hover:scale-110 transition-all duration-200">
                        <Camera className="w-4 h-4" />
                        <input 
                            type="file" 
                            accept="image/png, image/jpeg, image/jpg" 
                            className="hidden" 
                            onChange={handlePictureChange} 
                        />
                    </label>
                </div>
                <h1 className="text-2xl font-bold text-gray-800">{user.fullName}</h1>
                <p className="text-gray-500">{user.email}</p>
            </div>

            <div className="bg-white rounded-lg shadow-md divide-y">
                {menuItems.map(item => (
                    <Link href={item.path} key={item.text} className="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                        <div className="flex items-center">
                            <div className="text-bpn-blue">{item.icon}</div>
                            <span className="ml-4 text-gray-700 font-medium">{item.text}</span>
                        </div>
                        <ChevronRight className="text-gray-400" />
                    </Link>
                ))}
                {renderNotificationButton()}
            </div>

            <div className="mt-8">
                <button
                    onClick={handleLogout}
                    className="w-full flex items-center justify-center p-4 bg-white rounded-lg shadow-md text-bpn-red font-semibold hover:bg-red-50 transition-colors"
                >
                    <LogOut className="mr-3" />
                    Logout
                </button>
            </div>
        </div>
    );
};

export default ProfilePage;
