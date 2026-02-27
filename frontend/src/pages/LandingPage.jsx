import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { AppConfig } from '../config';
import Button from '../components/ui/Button';
import { Menu, X, BarChart2, CreditCard, ShieldCheck, ChevronDown } from 'lucide-react';
import useApi from '../hooks/useApi'; // <-- Impor useApi

// Komponen FAQ (tidak berubah)
const FaqItem = ({ question, answer }) => {
    const [isOpen, setIsOpen] = useState(false);
    return (
        <div className="bg-white p-4 rounded-lg border">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="w-full flex justify-between items-center text-left"
            >
                <h3 className="font-semibold">{question}</h3>
                <ChevronDown className={`transform transition-transform duration-300 ${isOpen ? 'rotate-180' : ''}`} />
            </button>
            <div className={`overflow-hidden transition-all duration-300 ease-in-out ${isOpen ? 'max-h-40 mt-2' : 'max-h-0'}`}>
                <p className="text-gray-600 text-sm pt-2 border-t">
                    {answer}
                </p>
            </div>
        </div>
    );
};

const LandingPage = () => {
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isImageVisible, setIsImageVisible] = useState(false);

    // --- PERBAIKAN UTAMA DI SINI ---
    const { callApi } = useApi();
    const [appLinks, setAppLinks] = useState({
        ios: '#',
        android: '#'
    });

    useEffect(() => {
        const fetchLinks = async () => {
            const result = await callApi('utility_get_public_config.php');
            if (result && result.status === 'success') {
                setAppLinks({
                    ios: result.data.app_download_link_ios,
                    android: result.data.app_download_link_android
                });
            }
        };
        fetchLinks();
    }, [callApi]);
    // --- AKHIR PERBAIKAN ---

    useEffect(() => {
        const timer = setTimeout(() => setIsImageVisible(true), 100);
        return () => clearTimeout(timer);
    }, []);

    const features = [
        {
            icon: <BarChart2 className="w-10 h-10 text-bpn-blue-800" />,
            title: "Prinsip Syariah",
            description: "Semua produk dan layanan kami dirancang sesuai dengan prinsip keuangan Syariah."
        },
        {
            icon: <CreditCard className="w-10 h-10 text-bpn-yellow-500" />,
            title: "Transaksi Mudah",
            description: "Nikmati kemudahan transfer, pembayaran, dan pembelian dalam satu genggaman."
        },
        {
            icon: <ShieldCheck className="w-10 h-10 text-bpn-red-500" />,
            title: "Aman & Terpercaya",
            description: "Keamanan data dan dana Anda adalah prioritas utama kami dengan teknologi terkini."
        }
    ];

    const faqs = [
        {
            question: "Bagaimana cara membuka akun?",
            answer: "Anda dapat membuka akun secara online melalui tombol 'Buka Akun' di atas. Prosesnya cepat, mudah, dan hanya membutuhkan beberapa menit."
        },
        {
            question: "Apakah data saya aman?",
            answer: "Kami menggunakan teknologi enkripsi terdepan dan sistem keamanan berlapis untuk memastikan semua data dan transaksi Anda aman bersama kami."
        },
        {
            question: "Apa saja produk yang ditawarkan?",
            answer: "Kami menawarkan berbagai produk berbasis syariah, mulai dari tabungan, deposito, hingga produk pembiayaan yang sesuai dengan kebutuhan Anda."
        }
    ];

    return (
        <div className="bg-gray-50 text-gray-800">
            {/* Header (tidak berubah) */}
            <header className="container mx-auto px-6 py-4 flex justify-between items-center sticky top-0 bg-white/80 backdrop-blur-md z-20">
                <Link to="/" className="flex items-center">
                    <img src={AppConfig.brand.logo} alt="A2U Bank Digital Logo" className="h-8" />
                </Link>
                <nav className="hidden md:flex items-center space-x-8 text-gray-600 font-medium">
                    <a href="#features" className="hover:text-bpn-blue-800 transition-colors">Fitur Unggulan</a>
                    <a href="#about" className="hover:text-bpn-blue-800 transition-colors">Tentang Kami</a>
                    <a href="#faq" className="hover:text-bpn-blue-800 transition-colors">FAQ</a>
                </nav>
                <div className="hidden md:flex items-center space-x-4">
                    <Link to="/login" className="text-gray-600 hover:text-bpn-blue-800 font-bold transition-colors">
                        Login
                    </Link>
                    <Link to="/register">
                        <Button>Buka Akun</Button>
                    </Link>
                </div>
                <div className="md:hidden">
                    <button onClick={() => setIsMenuOpen(!isMenuOpen)}>
                        {isMenuOpen ? <X size={24} /> : <Menu size={24} />}
                    </button>
                </div>
            </header>

            {/* Mobile Menu (tidak berubah) */}
            {isMenuOpen && (
                <div className="md:hidden bg-white shadow-lg">
                    <nav className="flex flex-col items-center space-y-4 py-4">
                        <a href="#features" onClick={() => setIsMenuOpen(false)}>Fitur</a>
                        <a href="#about" onClick={() => setIsMenuOpen(false)}>Tentang Kami</a>
                        <a href="#faq" onClick={() => setIsMenuOpen(false)}>FAQ</a>
                        <Link to="/login" className="w-full px-4"><Button fullWidth>Login</Button></Link>
                    </nav>
                </div>
            )}

            {/* Hero Section */}
            <main className="container mx-auto px-6 pt-16 pb-24 text-center">
                <h1 className="text-4xl md:text-6xl font-bold leading-tight mb-4 text-bpn-blue-900">Perbankan Syariah di Ujung Jari Anda</h1>
                <p className="text-lg md:text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    Kelola keuangan Anda dengan mudah, aman, dan sesuai prinsip syariah kapan pun dan di mana pun.
                </p>
                <div className="flex flex-col sm:flex-row justify-center items-center gap-4">
                    <Link to="/register">
                        <Button className="py-3 px-8 text-lg">Mulai Sekarang</Button>
                    </Link>
                </div>

                {/* --- PERBAIKAN UTAMA DI SINI --- */}
                <div className="mt-12 flex justify-center items-center gap-4">
                    <a href={appLinks.ios} target="_blank" rel="noopener noreferrer">
                        <img src="/app-store-badge.svg" alt="Download on the App Store" className="h-12" />
                    </a>
                    <a href={appLinks.android} target="_blank" rel="noopener noreferrer">
                        <img src="/google-play-badge.svg" alt="Get it on Google Play" className="h-12" />
                    </a>
                </div>
                {/* --- AKHIR PERBAIKAN --- */}
            </main>

            {/* Sisa halaman (tidak berubah)... */}
            <section className="container mx-auto px-6 mb-20">
                <div className={`transition-opacity duration-1000 ease-in ${isImageVisible ? 'opacity-100' : 'opacity-0'}`}>
                    <div className="bg-white rounded-2xl shadow-2xl p-2 sm:p-4 max-w-4xl mx-auto">
                        <div className="rounded-lg overflow-hidden">
                            <img
                                src="/app-mockup.png"
                                alt="A2U Bank Digital App Mockup"
                                className="w-full h-auto object-cover"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" className="py-20 bg-gray-100">
                <div className="container mx-auto px-6 text-center">
                    <h2 className="text-3xl font-bold mb-12 text-bpn-blue-900">Satu Aplikasi, Berbagai Kemudahan</h2>
                    <div className="grid md:grid-cols-3 gap-12">
                        {features.map(feature => (
                            <div key={feature.title}>
                                <div className="bg-white w-24 h-24 rounded-2xl mx-auto flex items-center justify-center mb-6 shadow-lg border">
                                    {feature.icon}
                                </div>
                                <h3 className="text-2xl font-bold mb-2">{feature.title}</h3>
                                <p className="text-gray-600">{feature.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section id="about" className="py-20">
                <div className="container mx-auto px-6 grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 className="text-3xl font-bold mb-4 text-bpn-blue-900">Membawa Layanan Perbankan Syariah Terbaik untuk Anda</h2>
                        <p className="text-gray-600 mb-4">
                            A2U Bank Digital berkomitmen untuk menyediakan solusi keuangan yang tidak hanya modern dan mudah diakses, tetapi juga berlandaskan pada nilai-nilai keadilan dan transparansi. Kami hadir untuk menjadi mitra terpercaya dalam setiap langkah perjalanan finansial Anda.
                        </p>
                    </div>
                    <div id="faq" className="space-y-4">
                        {faqs.map((faq, index) => (
                            <FaqItem key={index} question={faq.question} answer={faq.answer} />
                        ))}
                    </div>
                </div>
            </section>

            <footer className="py-12 bg-gray-800 text-white">
                <div className="container mx-auto px-6 text-center text-gray-400">
                    <img src="/a2u-logo.png" alt="A2U Bank Digital Logo" className="h-8 mx-auto mb-4" />
                    <p>&copy; {new Date().getFullYear()} A2U Bank Digital. Semua Hak Cipta Dilindungi.</p>
                </div>
            </footer>
        </div>
    );
};

export default LandingPage;
