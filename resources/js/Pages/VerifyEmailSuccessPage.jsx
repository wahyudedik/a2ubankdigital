import { useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';

const VerifyEmailSuccessPage = () => {
    useEffect(() => {
        document.title = 'Verifikasi Berhasil - A2U Bank Digital';
    }, []);

    return (
        <>
            <Head title="Verifikasi Berhasil" />
            <div className="min-h-screen bg-bpn-gray flex items-center justify-center p-4">
                <div className="bg-white rounded-2xl shadow-lg max-w-md w-full p-8 text-center">
                    {/* Success Icon */}
                    <div className="w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
                        <svg
                            className="w-10 h-10 text-green-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2.5}
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>

                    {/* Title */}
                    <h1 className="text-2xl font-bold text-gray-900 mb-3">
                        Verifikasi Berhasil!
                    </h1>

                    {/* Description */}
                    <p className="text-gray-600 mb-2">
                        Email Anda telah berhasil diverifikasi.
                    </p>
                    <p className="text-gray-600 mb-8">
                        Akun Anda sudah aktif. Silakan masuk untuk mulai menggunakan layanan A2U Bank Digital.
                    </p>

                    {/* Login Button */}
                    <Link
                        href="/login"
                        className="block w-full bg-bpn-blue text-white font-semibold py-3 rounded-xl hover:bg-bpn-blue/90 transition-colors"
                    >
                        Masuk
                    </Link>

                    {/* Back to Home */}
                    <Link
                        href="/"
                        className="block w-full mt-3 text-gray-500 text-sm hover:text-gray-700 transition-colors"
                    >
                        Kembali ke Beranda
                    </Link>
                </div>
            </div>
        </>
    );
};

export default VerifyEmailSuccessPage;
